<?php

namespace App\Services\Telegram;

use App\Models\CatalogSyncEvent;
use App\Models\PaymentSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TelegramClient
{
    public function enabled(): bool
    {
        return (bool) config('telegram.enabled')
            && filled(config('telegram.bot_token'))
            && filled(config('telegram.admin_chat_id'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function call(string $method, array $payload = [], ?array $attach = null): array
    {
        $token = config('telegram.bot_token');

        if (! $token) {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
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
            throw new RuntimeException('Telegram API error: '.$description);
        }

        return is_array($json['result'] ?? null) ? $json['result'] : ['raw' => $json['result'] ?? null];
    }

    public function sendPaymentReview(PaymentSubmission $submission): array
    {
        if (! $this->enabled()) {
            Log::warning('Telegram payment review skipped (disabled or missing config).', [
                'submission_id' => $submission->id,
            ]);

            return [];
        }

        $submission->loadMissing(['user', 'service']);
        $chatId = (string) config('telegram.admin_chat_id');
        $caption = $this->buildCaption($submission);
        $keyboard = $this->reviewKeyboard($submission);
        $absolutePath = Storage::disk('public')->path($submission->proof_path);
        $isImage = $this->isImagePath($submission->proof_path);

        if (is_file($absolutePath)) {
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

    /**
     * @param  Collection<int, CatalogSyncEvent>  $events
     */
    public function notifyCatalogSyncEvents(Collection $events): void
    {
        if ($events->isEmpty()) {
            return;
        }

        if (! $this->enabled()) {
            CatalogSyncEvent::query()
                ->whereIn('id', $events->pluck('id'))
                ->update(['status' => CatalogSyncEvent::STATUS_SKIPPED]);

            Log::info('Catalog sync alerts stored (Telegram not configured).', [
                'count' => $events->count(),
            ]);

            return;
        }

        $chatId = (string) config('telegram.admin_chat_id');
        $lines = [];

        foreach ($events as $event) {
            $lines[] = match ($event->event_type) {
                CatalogSyncEvent::TYPE_NAME_CHANGED => implode("\n", [
                    '✏️ <b>Name change</b> · service #'.$event->service_id.' (BP '.$event->external_id.')',
                    'Old: '.e($event->old_value ?? '—'),
                    'New: '.e($event->new_value ?? '—'),
                ]),
                CatalogSyncEvent::TYPE_NEW_PROVIDER_SERVICE => implode("\n", [
                    '🆕 <b>New package skipped</b> (not imported) · BP '.$event->external_id,
                    e($event->new_value ?? '—'),
                ]),
                default => '• '.e($event->event_type),
            };
        }

        $chunks = array_chunk($lines, 15);

        foreach ($chunks as $index => $chunk) {
            $prefix = $index === 0 ? "<b>Catalog sync alerts</b>\n\n" : "<b>Catalog sync alerts (cont.)</b>\n\n";
            $this->call('sendMessage', [
                'chat_id' => $chatId,
                'text' => $prefix.implode("\n\n", $chunk),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        }

        CatalogSyncEvent::query()
            ->whereIn('id', $events->pluck('id'))
            ->update([
                'status' => CatalogSyncEvent::STATUS_NOTIFIED,
                'notified_at' => now(),
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

    protected function endpoint(string $method): string
    {
        return 'https://api.telegram.org/bot'.config('telegram.bot_token').'/'.$method;
    }

    protected function buildCaption(PaymentSubmission $submission): string
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

        $url = $this->absoluteProofUrl($submission);
        if ($url) {
            $rows[] = [
                ['text' => '👁 View receipt', 'url' => $url],
            ];
        }

        return ['inline_keyboard' => $rows];
    }

    public function absoluteProofUrl(PaymentSubmission $submission): ?string
    {
        $url = $submission->proofPublicUrl();
        if (! $url) {
            return null;
        }

        if (! str_starts_with($url, 'http')) {
            return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
        }

        return $url;
    }

    protected function isImagePath(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
