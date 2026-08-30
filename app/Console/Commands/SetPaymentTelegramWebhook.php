<?php

namespace App\Console\Commands;

use App\Services\Telegram\PaymentTelegramNotifier;
use Illuminate\Console\Command;
use Throwable;

class SetPaymentTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-payment-webhook {--url= : Full webhook URL (defaults to APP_URL/api/v1/telegram/payment-webhook)}';

    protected $description = 'Register the payment Telegram bot webhook URL with secret token header';

    public function handle(PaymentTelegramNotifier $telegram): int
    {
        if (! filled(config('telegram.payment_bot_token'))) {
            $this->error('TELEGRAM_BOT_TOKEN_PAYMENT is empty.');

            return self::FAILURE;
        }

        $url = $this->option('url') ?: rtrim((string) config('app.url'), '/').'/api/v1/telegram/payment-webhook';
        $secret = config('telegram.payment_webhook_secret');

        try {
            $result = $telegram->setWebhook($url, $secret ? (string) $secret : null);
            $this->info('Payment webhook set to: '.$url);
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $secret) {
            $this->warn('TELEGRAM_WEBHOOK_SECRET_PAYMENT is empty — set it for production.');
        }

        return self::SUCCESS;
    }
}
