<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DepositStatus;
use App\Enums\PaymentSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\PaymentSubmission;
use App\Services\Deposits\DepositService;
use App\Services\Payments\PaymentSubmissionService;
use App\Services\Telegram\PaymentTelegramNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentTelegramWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentSubmissionService $payments,
        private readonly DepositService $deposits,
        private readonly PaymentTelegramNotifier $telegram,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $configuredSecret = (string) config('telegram.payment_webhook_secret');
        $incomingSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($configuredSecret !== '' && ! hash_equals($configuredSecret, $incomingSecret)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        $callback = $payload['callback_query'] ?? null;
        if (! is_array($callback)) {
            return response()->json(['ok' => true]);
        }

        $data = (string) ($callback['data'] ?? '');
        $callbackId = (string) ($callback['id'] ?? '');
        $message = is_array($callback['message'] ?? null) ? $callback['message'] : [];
        $chatId = isset($message['chat']['id']) ? (string) $message['chat']['id'] : null;
        $messageId = isset($message['message_id']) ? (string) $message['message_id'] : null;

        if (preg_match('/^pay:(accept|decline):(\d+)$/', $data, $matches)) {
            return $this->handlePaymentSubmissionCallback(
                $matches[1],
                (int) $matches[2],
                $callbackId,
                $chatId,
                $messageId,
            );
        }

        if (preg_match('/^dep:(accept|decline):(\d+)$/', $data, $matches)) {
            return $this->handleDepositCallback(
                $matches[1],
                (int) $matches[2],
                $callbackId,
                $chatId,
                $messageId,
            );
        }

        if ($callbackId) {
            $this->telegram->answerCallbackQuery($callbackId, 'Unknown action');
        }

        return response()->json(['ok' => true]);
    }

    protected function handlePaymentSubmissionCallback(
        string $action,
        int $submissionId,
        string $callbackId,
        ?string $chatId,
        ?string $messageId,
    ): JsonResponse {
        $submission = PaymentSubmission::query()->with(['user', 'service', 'order'])->find($submissionId);

        if (! $submission) {
            if ($callbackId) {
                $this->telegram->answerCallbackQuery($callbackId, 'Submission not found', true);
            }

            return response()->json(['ok' => true]);
        }

        try {
            if ($action === 'accept') {
                $submission = $this->payments->accept($submission);
                $statusLine = $submission->status === PaymentSubmissionStatus::Approved
                    ? '✅ Approved · Order #'.($submission->order_id ?? '—')
                    : '⚠️ Accept attempted · '.$submission->status->value.($submission->admin_note ? ' · '.$submission->admin_note : '');
                if ($callbackId) {
                    $this->telegram->answerCallbackQuery($callbackId, $submission->status === PaymentSubmissionStatus::Approved ? 'Order placed' : 'Order failed');
                }
            } else {
                $submission = $this->payments->decline($submission, 'Declined from Telegram');
                $statusLine = '❌ Declined';
                if ($callbackId) {
                    $this->telegram->answerCallbackQuery($callbackId, 'Payment declined');
                }
            }

            $caption = $this->reviewedPaymentCaption($submission, $statusLine);
            $this->telegram->editMessageCaption($chatId ?: $submission->telegram_chat_id, $messageId ?: $submission->telegram_message_id, $caption, [
                'inline_keyboard' => $this->viewOnlyPaymentKeyboard($submission),
            ]);
        } catch (Throwable $exception) {
            Log::error('Payment Telegram callback failed', [
                'submission_id' => $submissionId,
                'error' => $exception->getMessage(),
            ]);

            if ($callbackId) {
                try {
                    $this->telegram->answerCallbackQuery($callbackId, 'Error: '.$exception->getMessage(), true);
                } catch (Throwable) {
                    // ignore
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    protected function handleDepositCallback(
        string $action,
        int $depositId,
        string $callbackId,
        ?string $chatId,
        ?string $messageId,
    ): JsonResponse {
        $deposit = Deposit::query()->with(['user', 'wallet'])->find($depositId);

        if (! $deposit) {
            if ($callbackId) {
                $this->telegram->answerCallbackQuery($callbackId, 'Deposit not found', true);
            }

            return response()->json(['ok' => true]);
        }

        try {
            if ($action === 'accept') {
                $deposit = $this->deposits->approve($deposit, null, 'Approved from Telegram');
                $statusLine = $deposit->status === DepositStatus::Completed
                    ? '✅ Approved · Wallet credited'
                    : '⚠️ Accept attempted · '.$deposit->status->value;
                if ($callbackId) {
                    $this->telegram->answerCallbackQuery($callbackId, 'Wallet credited');
                }
            } else {
                $deposit = $this->deposits->reject($deposit, null, 'Declined from Telegram');
                $statusLine = '❌ Declined';
                if ($callbackId) {
                    $this->telegram->answerCallbackQuery($callbackId, 'Top-up declined');
                }
            }

            $caption = $this->reviewedDepositCaption($deposit, $statusLine);
            $this->telegram->editMessageCaption($chatId ?: $deposit->telegram_chat_id, $messageId ?: $deposit->telegram_message_id, $caption, [
                'inline_keyboard' => $this->viewOnlyDepositKeyboard($deposit),
            ]);
        } catch (Throwable $exception) {
            Log::error('Deposit Telegram callback failed', [
                'deposit_id' => $depositId,
                'error' => $exception->getMessage(),
            ]);

            if ($callbackId) {
                try {
                    $this->telegram->answerCallbackQuery($callbackId, 'Error: '.$exception->getMessage(), true);
                } catch (Throwable) {
                    // ignore
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    protected function reviewedPaymentCaption(PaymentSubmission $submission, string $statusLine): string
    {
        $user = $submission->user;
        $service = $submission->service;
        $amount = number_format((float) $submission->amount_dzd, 2).' DA';

        return implode("\n", [
            '<b>CCP / BaridiMob receipt</b>',
            'Submission #'.$submission->id,
            'User: '.e($user?->name ?? 'Unknown').' (#'.$submission->user_id.')',
            'Service: '.e($service?->name ?? ('#'.$submission->service_id)),
            'Qty: '.number_format((int) $submission->quantity),
            'Amount: <b>'.$amount.'</b>',
            'Target: '.e($submission->link),
            $statusLine,
        ]);
    }

    protected function reviewedDepositCaption(Deposit $deposit, string $statusLine): string
    {
        $user = $deposit->user;
        $amount = number_format((float) $deposit->amount_dzd, 2).' DA';
        $wired = $deposit->wired_amount_dzd
            ? number_format((float) $deposit->wired_amount_dzd, 2).' DA'
            : '—';

        return implode("\n", [
            '<b>CCP / BaridiMob wallet top-up</b>',
            'Deposit #'.$deposit->id,
            'User: '.e($user?->name ?? 'Unknown').' (#'.$deposit->user_id.')',
            'Email: '.e($user?->email ?? '—'),
            'Top-up amount: <b>'.$amount.'</b>',
            'Wired amount: '.$wired,
            $statusLine,
        ]);
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    protected function viewOnlyPaymentKeyboard(PaymentSubmission $submission): array
    {
        $url = $submission->proofPublicUrl();
        if (! $url) {
            return [];
        }

        if (! str_starts_with($url, 'http')) {
            $url = rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
        }

        return [
            [['text' => '👁 View receipt', 'url' => $url]],
        ];
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    protected function viewOnlyDepositKeyboard(Deposit $deposit): array
    {
        $url = $deposit->proofPublicUrl();
        if (! $url) {
            return [];
        }

        if (! str_starts_with($url, 'http')) {
            $url = rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
        }

        return [
            [['text' => '👁 View receipt', 'url' => $url]],
        ];
    }
}
