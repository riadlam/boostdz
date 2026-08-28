<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;
use Throwable;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {--url= : Full webhook URL (defaults to APP_URL/api/v1/telegram/webhook)}';

    protected $description = 'Register the Telegram bot webhook URL with secret token header';

    public function handle(TelegramClient $telegram): int
    {
        if (! filled(config('telegram.bot_token'))) {
            $this->error('TELEGRAM_BOT_TOKEN is empty.');

            return self::FAILURE;
        }

        $url = $this->option('url') ?: rtrim((string) config('app.url'), '/').'/api/v1/telegram/webhook';
        $secret = config('telegram.webhook_secret');

        try {
            $result = $telegram->setWebhook($url, $secret ? (string) $secret : null);
            $this->info('Webhook set to: '.$url);
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $secret) {
            $this->warn('TELEGRAM_WEBHOOK_SECRET is empty — set it for production.');
        }

        $this->comment('Ensure public storage is linked: php artisan storage:link');

        return self::SUCCESS;
    }
}
