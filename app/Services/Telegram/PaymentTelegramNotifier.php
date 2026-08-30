<?php

namespace App\Services\Telegram;

use App\Models\Deposit;
use App\Models\Order;
use App\Models\PaymentSubmission;
use App\Models\SofizPayTransaction;
use App\Services\Orders\DeliveryProgress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PaymentTelegramNotifier
{
    public function enabled(): bool
    {
        return filled(config('telegram.payment_bot_token'))
            && filled(config('telegram.payment_admin_chat_id'));
    }

    /**
     * @return array<string, mixed>
     */
    public function sendPaymentReview(PaymentSubmission $submission): array
    {
        if (! $this->enabled()) {
            Log::warning('Payment Telegram review skipped (disabled or missing config).', [
                'submission_id' => $submission->id,
            ]);

            return [];
        }

        $submission->loadMissing(['user', 'service']);
        $chatId = (string) config('telegram.payment_admin_chat_id');
        $caption = $this->buildCcpCaption($submission);
        $keyboard = $this->reviewKeyboard($submission);
        $absolutePath = $submission->proof_path
            ? Storage::disk('public')->path($submission->proof_path)
            : null;

        if ($absolutePath && is_file($absolutePath)) {
            $isImage = $this->isImagePath($submission->proof_path);
            $field = $isImage ? 'photo' : 'document';
            $method = $isImage ? 'sendPhoto' : 'sendDocument';
            $contents = file_get_contents($absolutePath);

            if ($contents === false) {
                throw new RuntimeException('Could not read receipt file for Telegram upload.');
            }

            return $this->call($method, [
                'chat_id' => $chatId,
                'caption' => $caption,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($keyboard, JSON_UNESCAPED_UNICODE),
            ], [
                'name' => $field,
                'contents' => $contents,
                'filename' => basename($absolutePath),
            ]);
        }

        return $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $caption."\n\n⚠️ Receipt file missing on disk.",
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard,
            'disable_web_page_preview' => true,
        ]);
    }

    public function notifyWalletCheckout(Order $order, string $status = 'placed'): void
    {
        if (! $this->enabled()) {
            return;
        }

        if ($order->telegram_message_id) {
            $this->updateWalletOrderMessage($order, $status === 'failed' ? 'failed' : ($order->status?->value ?? 'processing'));

            return;
        }

        try {
            $order->loadMissing(['user.wallet', 'service']);
            $text = $this->buildWalletOrderMessage($order, $status === 'failed' ? 'failed' : 'placed');
            $result = $this->call('sendMessage', [
                'chat_id' => (string) config('telegram.payment_admin_chat_id'),
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($result) {
                $order->update([
                    'telegram_chat_id' => (string) ($result['chat']['id'] ?? config('telegram.payment_admin_chat_id')),
                    'telegram_message_id' => isset($result['message_id']) ? (string) $result['message_id'] : null,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Wallet checkout Telegram notify failed.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function updateWalletOrderMessage(Order $order, string $deliveryStatus): void
    {
        if (! $this->enabled()) {
            return;
        }

        $text = $this->buildWalletOrderMessage($order, $deliveryStatus);

        if ($order->telegram_chat_id && $order->telegram_message_id) {
            try {
                $this->editMessageText($order->telegram_chat_id, $order->telegram_message_id, $text);

                return;
            } catch (\Throwable $exception) {
                Log::warning('Wallet order Telegram edit failed; sending fallback.', [
                    'order_id' => $order->id,
                    'status' => $deliveryStatus,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        try {
            $this->call('sendMessage', [
                'chat_id' => (string) config('telegram.payment_admin_chat_id'),
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Wallet order Telegram fallback notify failed.', [
                'order_id' => $order->id,
                'status' => $deliveryStatus,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    protected function buildWalletOrderMessage(Order $order, string $deliveryStatus): string
    {
        $user = $order->user;
        $service = $order->service;
        $amount = number_format((float) $order->charge_dzd, 2).' DA';
        $balance = number_format((float) ($user?->wallet?->balance ?? 0), 2).' DA';
        $delivery = DeliveryProgress::fromOrder($order);

        $title = match ($deliveryStatus) {
            'failed' => '❌ <b>Wallet checkout failed</b>',
            'completed' => '✅ <b>Wallet order completed</b>',
            'partial' => '⚠️ <b>Wallet order partial</b>',
            'canceled', 'refunded' => '⚠️ <b>Wallet order '.$deliveryStatus.'</b>',
            'placed' => '💼 <b>Wallet checkout placed</b>',
            default => '📦 <b>Wallet order update</b>',
        };

        $statusLine = match ($deliveryStatus) {
            'failed' => 'Status: <b>Failed</b>',
            'completed' => 'Delivery: <b>Completed</b>',
            'partial' => 'Delivery: <b>Partial</b>',
            'canceled' => 'Delivery: <b>Canceled</b>',
            'refunded' => 'Delivery: <b>Refunded</b>',
            'placed' => 'Status: <b>Placed — sent to provider</b>',
            'processing', 'in_progress', 'pending' => 'Delivery: <b>'.e(ucfirst(str_replace('_', ' ', $deliveryStatus))).'</b>',
            default => 'Delivery: <b>'.e(ucfirst(str_replace('_', ' ', $deliveryStatus))).'</b>',
        };

        $lines = [
            $title,
            'User: '.e($user?->name ?? 'Unknown').' (#'.$order->user_id.')',
            'Email: '.e($user?->email ?? '—'),
            'Service: '.e($service?->name ?? ('#'.$order->service_id)),
            'Qty: '.number_format((int) $order->quantity),
            'Amount: <b>'.$amount.'</b>',
            'Target: '.e($order->link),
            'Order: #'.$order->id,
            'Method: Wallet',
            'Balance after charge: <b>'.$balance.'</b>',
        ];

        if ($deliveryStatus !== 'failed' && $deliveryStatus !== 'placed') {
            $lines[] = 'Progress: <b>'.$delivery->percent.'%</b> · '.$delivery->label;
        }

        if ($deliveryStatus === 'failed' && $order->error_message) {
            $lines[] = 'Reason: '.e($order->error_message);
        }

        $lines[] = $statusLine;

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendDepositReview(Deposit $deposit): array
    {
        if (! $this->enabled()) {
            Log::warning('Deposit Telegram review skipped (disabled or missing config).', [
                'deposit_id' => $deposit->id,
            ]);

            return [];
        }

        $deposit->loadMissing(['user', 'wallet']);
        $chatId = (string) config('telegram.payment_admin_chat_id');
        $caption = $this->buildDepositCaption($deposit);
        $keyboard = $this->depositReviewKeyboard($deposit);
        $absolutePath = $deposit->proof_path
            ? Storage::disk('public')->path($deposit->proof_path)
            : null;

        if ($absolutePath && is_file($absolutePath)) {
            $isImage = $this->isImagePath($deposit->proof_path);
            $field = $isImage ? 'photo' : 'document';
            $method = $isImage ? 'sendPhoto' : 'sendDocument';
            $contents = file_get_contents($absolutePath);

            if ($contents === false) {
                throw new RuntimeException('Could not read deposit receipt file for Telegram upload.');
            }

            return $this->call($method, [
                'chat_id' => $chatId,
                'caption' => $caption,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($keyboard, JSON_UNESCAPED_UNICODE),
            ], [
                'name' => $field,
                'contents' => $contents,
                'filename' => basename($absolutePath),
            ]);
        }

        return $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $caption."\n\n⚠️ Receipt file missing on disk.",
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard,
            'disable_web_page_preview' => true,
        ]);
    }

    public function notifyPending(SofizPayTransaction $transaction): void
    {
        if (! $this->enabled()) {
            return;
        }

        if ($transaction->telegram_message_id) {
            return;
        }

        try {
            $transaction->loadMissing(['user', 'deposit', 'order.service']);
            $text = $this->buildSofizPayMessage($transaction, 'pending');
            $result = $this->call('sendMessage', [
                'chat_id' => (string) config('telegram.payment_admin_chat_id'),
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($result) {
                $transaction->update([
                    'telegram_chat_id' => (string) ($result['chat']['id'] ?? config('telegram.payment_admin_chat_id')),
                    'telegram_message_id' => isset($result['message_id']) ? (string) $result['message_id'] : null,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('SofizPay Telegram pending notify failed.', [
                'transaction_id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function notifyCompleted(SofizPayTransaction $transaction): void
    {
        if (! $this->enabled()) {
            return;
        }

        $transaction->loadMissing(['user.wallet', 'deposit', 'order.service']);
        $this->updateSofizPayMessage($transaction, 'completed');
    }

    public function notifyFailed(SofizPayTransaction $transaction): void
    {
        if (! $this->enabled()) {
            return;
        }

        $transaction->loadMissing(['user', 'deposit', 'order.service']);
        $this->updateSofizPayMessage($transaction, 'failed');
    }

    protected function updateSofizPayMessage(SofizPayTransaction $transaction, string $status): void
    {
        $text = $this->buildSofizPayMessage($transaction, $status);

        if ($transaction->telegram_chat_id && $transaction->telegram_message_id) {
            try {
                $this->editMessageText(
                    $transaction->telegram_chat_id,
                    $transaction->telegram_message_id,
                    $text,
                );

                return;
            } catch (\Throwable $exception) {
                Log::warning('SofizPay Telegram message edit failed; sending fallback.', [
                    'transaction_id' => $transaction->id,
                    'status' => $status,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        try {
            $this->call('sendMessage', [
                'chat_id' => (string) config('telegram.payment_admin_chat_id'),
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('SofizPay Telegram fallback notify failed.', [
                'transaction_id' => $transaction->id,
                'status' => $status,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    protected function buildSofizPayMessage(SofizPayTransaction $transaction, string $status): string
    {
        $user = $transaction->user;
        $amount = number_format((float) $transaction->amount_dzd, 2).' DA';
        $isTopup = $transaction->purpose->value === 'topup';
        $meta = is_array($transaction->checkout_meta) ? $transaction->checkout_meta : [];

        $title = match ($status) {
            'completed' => $isTopup ? '💳 <b>Wallet top-up completed</b>' : '✅ <b>Checkout payment completed</b>',
            'failed' => $isTopup ? '❌ <b>Wallet top-up failed</b>' : '❌ <b>Checkout payment failed</b>',
            default => $isTopup ? '💳 <b>Wallet top-up (Algérie Post)</b>' : '🛒 <b>Checkout order (Algérie Post)</b>',
        };

        $statusLine = match ($status) {
            'completed' => 'Status: <b>Completed</b>',
            'failed' => 'Status: <b>Failed</b>',
            default => 'Status: <b>Pending — awaiting payment</b>',
        };

        $lines = [
            $title,
            'User: '.e($user?->name ?? 'Unknown').' (#'.$transaction->user_id.')',
            'Email: '.e($user?->email ?? '—'),
            'Amount: <b>'.$amount.'</b>',
            'Method: Algérie Post',
            'Invoice: '.e($transaction->invoice_id),
        ];

        if (! $isTopup) {
            $serviceName = $transaction->order?->service?->name
                ?? ($meta['service_name'] ?? '—');
            $lines[] = 'Service: '.e($serviceName);
            $lines[] = 'Qty: '.number_format((int) ($meta['quantity'] ?? 0));
            $lines[] = 'Target: '.e((string) ($meta['link'] ?? '—'));

            if ($transaction->order_id) {
                $lines[] = 'Order: #'.$transaction->order_id;
            }
        }

        if ($isTopup && $status === 'completed') {
            $balance = number_format((float) ($user?->wallet?->balance ?? 0), 2).' DA';
            $lines[] = 'Balance: <b>'.$balance.'</b>';
        }

        if ($status === 'failed') {
            $lines[] = 'Reason: '.e($transaction->failure_reason ?? 'Unknown');
        }

        $lines[] = $statusLine;

        return implode("\n", $lines);
    }

    public function editMessageText(?string $chatId, ?string $messageId, string $text, ?array $replyMarkup = null): void
    {
        if (! $chatId || ! $messageId) {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => (int) $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $this->call('editMessageText', $payload);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text, bool $showAlert = false): void
    {
        $this->call('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    public function editMessageCaption(?string $chatId, ?string $messageId, string $caption, ?array $replyMarkup = null): void
    {
        if (! $chatId || ! $messageId) {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => (int) $messageId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        try {
            $this->call('editMessageCaption', $payload);
        } catch (RuntimeException) {
            $this->editMessageText($chatId, $messageId, $caption, $replyMarkup ?? ['inline_keyboard' => []]);
        }
    }

    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        $payload = [
            'url' => $url,
            'allowed_updates' => ['callback_query', 'message'],
            'drop_pending_updates' => false,
        ];

        if ($secretToken) {
            $payload['secret_token'] = $secretToken;
        }

        return $this->call('setWebhook', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function call(string $method, array $payload = [], ?array $attach = null): array
    {
        $token = config('telegram.payment_bot_token');

        if (! $token) {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN_PAYMENT is not configured.');
        }

        $request = Http::timeout(60)->acceptJson();

        if ($attach) {
            $response = $request
                ->attach(
                    $attach['name'],
                    $attach['contents'],
                    $attach['filename'] ?? $attach['name'],
                )
                ->post($this->endpoint($method), $payload);
        } else {
            $response = $request->asJson()->post($this->endpoint($method), $payload);
        }

        $json = $response->json() ?? [];

        if (! $response->successful() || ($json['ok'] ?? false) !== true) {
            $description = is_array($json) ? ($json['description'] ?? $response->body()) : $response->body();
            throw new RuntimeException('Payment Telegram API error: '.$description);
        }

        return is_array($json['result'] ?? null) ? $json['result'] : ['raw' => $json['result'] ?? null];
    }

    protected function endpoint(string $method): string
    {
        return 'https://api.telegram.org/bot'.config('telegram.payment_bot_token').'/'.$method;
    }

    protected function buildCcpCaption(PaymentSubmission $submission): string
    {
        $user = $submission->user;
        $service = $submission->service;
        $amount = number_format((float) $submission->amount_dzd, 2).' DA';
        $ref = $submission->payer_reference ?: '—';

        return implode("\n", [
            '<b>CCP / BaridiMob receipt</b>',
            'Submission #'.$submission->id,
            'User: '.e($user?->name ?? 'Unknown').' (#'.$submission->user_id.')',
            'Email: '.e($user?->email ?? '—'),
            'Service: '.e($service?->name ?? ('#'.$submission->service_id)),
            'Qty: '.number_format((int) $submission->quantity),
            'Amount: <b>'.$amount.'</b>',
            'Target: '.e($submission->link),
            'Reference: '.e($ref),
            'Status: <b>'.$submission->status->value.'</b>',
        ]);
    }

    /**
     * @return array{inline_keyboard: array<int, array<int, array<string, string>>>}
     */
    protected function reviewKeyboard(PaymentSubmission $submission): array
    {
        $rows = [
            [
                ['text' => '✅ Accept', 'callback_data' => 'pay:accept:'.$submission->id],
                ['text' => '❌ Decline', 'callback_data' => 'pay:decline:'.$submission->id],
            ],
        ];

        $url = $submission->proofPublicUrl();
        if ($url) {
            if (! str_starts_with($url, 'http')) {
                $url = rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
            }

            $rows[] = [
                ['text' => '👁 View receipt', 'url' => $url],
            ];
        }

        return ['inline_keyboard' => $rows];
    }

    protected function buildDepositCaption(Deposit $deposit): string
    {
        $user = $deposit->user;
        $amount = number_format((float) $deposit->amount_dzd, 2).' DA';
        $wired = $deposit->wired_amount_dzd
            ? number_format((float) $deposit->wired_amount_dzd, 2).' DA'
            : '—';
        $ref = $deposit->provider_reference ?: '—';

        return implode("\n", [
            '<b>CCP / BaridiMob wallet top-up</b>',
            'Deposit #'.$deposit->id,
            'User: '.e($user?->name ?? 'Unknown').' (#'.$deposit->user_id.')',
            'Email: '.e($user?->email ?? '—'),
            'Top-up amount: <b>'.$amount.'</b>',
            'Wired amount: '.$wired,
            'Reference: '.e($ref),
            'Status: <b>'.$deposit->status->value.'</b>',
        ]);
    }

    /**
     * @return array{inline_keyboard: array<int, array<int, array<string, string>>>}
     */
    protected function depositReviewKeyboard(Deposit $deposit): array
    {
        $rows = [
            [
                ['text' => '✅ Accept', 'callback_data' => 'dep:accept:'.$deposit->id],
                ['text' => '❌ Decline', 'callback_data' => 'dep:decline:'.$deposit->id],
            ],
        ];

        $url = $deposit->proofPublicUrl();
        if ($url) {
            if (! str_starts_with($url, 'http')) {
                $url = rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
            }

            $rows[] = [
                ['text' => '👁 View receipt', 'url' => $url],
            ];
        }

        return ['inline_keyboard' => $rows];
    }

    protected function isImagePath(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
