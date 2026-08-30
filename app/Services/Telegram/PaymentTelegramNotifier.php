<?php

namespace App\Services\Telegram;

use App\Models\PaymentSubmission;
use App\Models\SofizPayTransaction;
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

    public function notifyCompleted(SofizPayTransaction $transaction): void
    {
        if (! $this->enabled()) {
            return;
        }

        $transaction->loadMissing(['user.wallet', 'deposit', 'order.service']);
        $user = $transaction->user;
        $amount = number_format((float) $transaction->amount_dzd, 2).' DA';

        if ($transaction->purpose->value === 'topup') {
            $balance = number_format((float) ($user?->wallet?->balance ?? 0), 2).' DA';
            $lines = [
                '💳 <b>Wallet top-up completed</b>',
                'User: '.e($user?->name ?? 'Unknown').' (#'.$transaction->user_id.')',
                'Amount: <b>'.$amount.'</b>',
                'Method: Edahabiya / CIB',
                'Invoice: '.e($transaction->invoice_id),
                'Balance: <b>'.$balance.'</b>',
            ];
        } else {
            $service = $transaction->order?->service;
            $lines = [
                '✅ <b>Checkout payment completed</b>',
                'User: '.e($user?->name ?? 'Unknown').' (#'.$transaction->user_id.')',
                'Amount: <b>'.$amount.'</b>',
                'Service: '.e($service?->name ?? ($transaction->checkout_meta['service_name'] ?? '—')),
                'Order: #'.($transaction->order_id ?? '—'),
                'Invoice: '.e($transaction->invoice_id),
            ];
        }

        $this->call('sendMessage', [
            'chat_id' => (string) config('telegram.payment_admin_chat_id'),
            'text' => implode("\n", $lines),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
    }

    public function notifyFailed(SofizPayTransaction $transaction): void
    {
        if (! $this->enabled()) {
            return;
        }

        $transaction->loadMissing(['user']);
        $amount = number_format((float) $transaction->amount_dzd, 2).' DA';
        $purpose = $transaction->purpose->value === 'topup' ? 'Top-up' : 'Checkout';

        $this->call('sendMessage', [
            'chat_id' => (string) config('telegram.payment_admin_chat_id'),
            'text' => implode("\n", [
                '❌ <b>'.$purpose.' payment failed</b>',
                'User: '.e($transaction->user?->name ?? 'Unknown').' (#'.$transaction->user_id.')',
                'Amount: <b>'.$amount.'</b>',
                'Invoice: '.e($transaction->invoice_id),
                'Reason: '.e($transaction->failure_reason ?? 'Unknown'),
            ]),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
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
            $this->call('editMessageText', [
                'chat_id' => $chatId,
                'message_id' => (int) $messageId,
                'text' => $caption,
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup ?? ['inline_keyboard' => []],
                'disable_web_page_preview' => true,
            ]);
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

    protected function isImagePath(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
