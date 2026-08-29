<?php

namespace App\Services\Payments;

use App\Enums\PaymentSubmissionStatus;
use App\Models\PaymentSubmission;
use App\Models\Service;
use App\Models\User;
use App\Services\Checkout\CheckoutPolicy;
use App\Services\Orders\OrderService;
use App\Services\Pricing\PricingService;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentSubmissionService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly TelegramClient $telegram,
        private readonly PricingService $pricing,
        private readonly CheckoutPolicy $checkoutPolicy,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitCcpReceipt(User $user, array $data, UploadedFile $receipt): PaymentSubmission
    {
        $service = Service::query()->findOrFail((int) $data['service_id']);

        if (! $service->is_active) {
            throw new InvalidArgumentException(__('api.orders.service_not_available'));
        }

        $quantity = (int) ($data['quantity'] ?? 0);
        $link = trim((string) ($data['link'] ?? ''));
        $amount = number_format((float) ($data['amount_dzd'] ?? 0), 2, '.', '');

        if ($quantity < $service->min || $quantity > $service->max) {
            throw new InvalidArgumentException(__('api.orders.quantity_between', [
                'min' => $service->min,
                'max' => $service->max,
            ]));
        }

        if ($link === '') {
            throw new InvalidArgumentException(__('api.orders.link_required'));
        }

        $service->validateComments($data['comments'] ?? null, $quantity);

        if ((float) $amount <= 0) {
            throw new InvalidArgumentException(__('api.deposits.amount_gt_zero'));
        }

        $idempotencyKey = (string) ($data['idempotency_key'] ?? Str::uuid());
        $existing = PaymentSubmission::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return $existing->load(['service', 'user', 'order']);
        }

        $proofPath = $receipt->store('payments/'.$user->id, 'public');
        $quote = $this->pricing->quote($service, $quantity);
        $this->checkoutPolicy->assertMinimumCheckout($quote);

        $submission = PaymentSubmission::query()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'payment_method' => 'ccp_baridimob',
            'link' => $link,
            'quantity' => $quantity,
            'is_repeat' => (bool) ($data['is_repeat'] ?? false),
            'idempotency_key' => $idempotencyKey,
            'payload_meta' => [
                'country' => $data['country'] ?? null,
                'quality' => $data['quality'] ?? null,
                'platform_slug' => $data['platform_slug'] ?? null,
                'category_slug' => $data['category_slug'] ?? null,
                'comments' => isset($data['comments']) && trim((string) $data['comments']) !== ''
                    ? trim((string) $data['comments'])
                    : null,
                'quoted_charge_dzd' => $quote->charge_dzd,
                'pricing' => $quote->toArray(),
            ],
            'amount_dzd' => $amount,
            'payer_reference' => $data['reference'] ?? null,
            'proof_path' => $proofPath,
            'status' => PaymentSubmissionStatus::Pending,
        ]);

        // Local / until webhook is live: place order immediately after upload.
        if (config('telegram.auto_accept')) {
            $submission = $this->accept($submission->fresh(['user', 'service']));

            if ($submission->status === PaymentSubmissionStatus::Approved) {
                $submission->update([
                    'admin_note' => 'Auto-accepted (TELEGRAM_AUTO_ACCEPT=true)',
                ]);
            }

            return $submission->fresh(['service', 'user', 'order']);
        }

        try {
            $result = $this->telegram->sendPaymentReview($submission->fresh(['user', 'service']));
            if ($result) {
                $submission->update([
                    'telegram_chat_id' => (string) ($result['chat']['id'] ?? config('telegram.admin_chat_id')),
                    'telegram_message_id' => isset($result['message_id']) ? (string) $result['message_id'] : null,
                ]);
            }
        } catch (\Throwable $exception) {
            $submission->update([
                'admin_note' => 'Telegram notify failed: '.$exception->getMessage(),
            ]);
        }

        return $submission->fresh(['service', 'user', 'order']);
    }

    public function accept(PaymentSubmission $submission): PaymentSubmission
    {
        if (! $submission->isPending()) {
            return $submission->fresh(['service', 'user', 'order']);
        }

        return DB::transaction(function () use ($submission) {
            /** @var PaymentSubmission $locked */
            $locked = PaymentSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                return $locked->fresh(['service', 'user', 'order']);
            }

            $service = Service::query()->findOrFail($locked->service_id);
            $meta = is_array($locked->payload_meta) ? $locked->payload_meta : [];

            $order = $this->orders->place($locked->user, $service, [
                'link' => $locked->link,
                'quantity' => $locked->quantity,
                'is_repeat' => $locked->is_repeat,
                'idempotency_key' => $locked->idempotency_key,
                'country' => $meta['country'] ?? null,
                'quality' => $meta['quality'] ?? null,
                'comments' => $meta['comments'] ?? null,
                'expected_charge_dzd' => $meta['quoted_charge_dzd'] ?? null,
                'meta' => array_merge($meta, [
                    'payment_method' => $locked->payment_method,
                    'payment_submission_id' => $locked->id,
                ]),
            ]);

            if ($order->status?->value === 'failed') {
                $locked->update([
                    'status' => PaymentSubmissionStatus::Failed,
                    'order_id' => $order->id,
                    'reviewed_at' => now(),
                    'admin_note' => $order->error_message ?: __('api.orders.provider_rejected'),
                ]);
            } else {
                $locked->update([
                    'status' => PaymentSubmissionStatus::Approved,
                    'order_id' => $order->id,
                    'reviewed_at' => now(),
                    'admin_note' => null,
                ]);
            }

            return $locked->fresh(['service', 'user', 'order']);
        });
    }

    public function decline(PaymentSubmission $submission, ?string $note = null): PaymentSubmission
    {
        if (! $submission->isPending()) {
            return $submission->fresh(['service', 'user', 'order']);
        }

        $submission->update([
            'status' => PaymentSubmissionStatus::Declined,
            'reviewed_at' => now(),
            'admin_note' => $note,
        ]);

        return $submission->fresh(['service', 'user', 'order']);
    }
}
