<?php

namespace App\Services\SofizPay;

use App\Enums\DepositStatus;
use App\Enums\SofizPayTransactionPurpose;
use App\Enums\SofizPayTransactionStatus;
use App\Models\Deposit;
use App\Models\Service;
use App\Models\SofizPayTransaction;
use App\Models\User;
use App\Services\Checkout\CheckoutPolicy;
use App\Services\Orders\OrderService;
use App\Services\Pricing\PricingService;
use App\Services\Telegram\PaymentTelegramNotifier;
use App\Services\Wallet\WalletService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SofizPayPaymentService
{
    public function __construct(
        private readonly SofizPayClient $client,
        private readonly PricingService $pricing,
        private readonly CheckoutPolicy $checkoutPolicy,
        private readonly OrderService $orders,
        private readonly WalletService $wallets,
        private readonly PaymentTelegramNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{transaction: SofizPayTransaction, payment_url: string}
     */
    public function initTopup(User $user, array $data): array
    {
        $this->assertEnabled();

        $amount = number_format((float) ($data['amount_dzd'] ?? 0), 2, '.', '');

        if ((float) $amount <= 0) {
            throw new InvalidArgumentException(__('api.deposits.amount_gt_zero'));
        }

        $this->checkoutPolicy->assertMinimumTopup($amount);
        $phone = PhoneNumber::normalize((string) ($data['phone'] ?? $user->phone ?? ''));
        $this->persistPhone($user, $phone);

        $wallet = $this->wallets->forUser($user);
        $invoiceId = $this->makeInvoiceId('TOP', $user->id);

        $deposit = Deposit::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount_dzd' => $amount,
            'method' => 'algerie_post',
            'status' => DepositStatus::Pending,
            'provider_reference' => $invoiceId,
        ]);

        $transaction = SofizPayTransaction::query()->create([
            'user_id' => $user->id,
            'purpose' => SofizPayTransactionPurpose::Topup,
            'invoice_id' => $invoiceId,
            'amount_dzd' => $amount,
            'status' => SofizPayTransactionStatus::Pending,
            'deposit_id' => $deposit->id,
        ]);

        return $this->initGatewayPayment($user, $transaction, $phone, $this->topupReturnUrl(), $this->topupMemo($amount));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{transaction: SofizPayTransaction, payment_url: string}
     */
    public function initCheckout(User $user, array $data): array
    {
        $this->assertEnabled();

        $service = Service::query()->findOrFail((int) $data['service_id']);

        if (! $service->is_active) {
            throw new InvalidArgumentException(__('api.orders.service_not_available'));
        }

        $quantity = (int) ($data['quantity'] ?? 0);
        $link = trim((string) ($data['link'] ?? ''));

        if ($quantity < $service->min || $quantity > $service->max) {
            throw new InvalidArgumentException(__('api.orders.quantity_between', [
                'min' => $service->min,
                'max' => $service->max,
            ]));
        }

        if ($link === '') {
            throw new InvalidArgumentException(__('api.orders.link_required'));
        }

        $this->orders->assertTargetAvailableForOrder($user, $link);

        $service->validateComments($data['comments'] ?? null, $quantity);

        $quote = $this->pricing->quote($service, $quantity);
        $this->checkoutPolicy->assertMinimumCheckout($quote);

        $phone = PhoneNumber::normalize((string) ($data['phone'] ?? $user->phone ?? ''));
        $this->persistPhone($user, $phone);

        $amount = number_format((float) $quote->charge_dzd, 2, '.', '');
        $invoiceId = $this->makeInvoiceId('CHK', $user->id);
        $idempotencyKey = (string) ($data['idempotency_key'] ?? Str::uuid());

        $checkoutMeta = [
            'service_id' => $service->id,
            'link' => $link,
            'quantity' => $quantity,
            'is_repeat' => (bool) ($data['is_repeat'] ?? false),
            'idempotency_key' => $idempotencyKey,
            'country' => $data['country'] ?? null,
            'quality' => $data['quality'] ?? null,
            'platform_slug' => $data['platform_slug'] ?? null,
            'category_slug' => $data['category_slug'] ?? null,
            'comments' => isset($data['comments']) && trim((string) $data['comments']) !== ''
                ? trim((string) $data['comments'])
                : null,
            'quoted_charge_dzd' => $quote->charge_dzd,
            'pricing' => $quote->toArray(),
            'service_name' => $service->name,
        ];

        $transaction = SofizPayTransaction::query()->create([
            'user_id' => $user->id,
            'purpose' => SofizPayTransactionPurpose::Checkout,
            'invoice_id' => $invoiceId,
            'amount_dzd' => $amount,
            'status' => SofizPayTransactionStatus::Pending,
            'checkout_meta' => $checkoutMeta,
        ]);

        return $this->initGatewayPayment($user, $transaction, $phone, $this->checkoutReturnUrl(), $this->checkoutMemo($service->name));
    }

    /**
     * @param  array<string, mixed>|null  $callbackPayload
     */
    public function verifyAndFulfill(?string $invoiceId = null, ?string $cibTransactionId = null, ?array $callbackPayload = null): SofizPayTransaction
    {
        $transaction = $this->resolveTransaction($invoiceId, $cibTransactionId);

        if ($callbackPayload !== null) {
            $transaction->forceFill([
                'raw_callback_payload' => array_merge(
                    is_array($transaction->raw_callback_payload) ? $transaction->raw_callback_payload : [],
                    $callbackPayload,
                ),
            ])->save();
        }

        if ($transaction->isCompleted()) {
            return $transaction->fresh(['user', 'deposit', 'order']);
        }

        if (! $transaction->cib_transaction_id) {
            throw new InvalidArgumentException(__('api.sofizpay.missing_cib_transaction'));
        }

        try {
            $verify = $this->client->checkTransaction($transaction->cib_transaction_id);
        } catch (SofizPayException $exception) {
            $this->markFailed($transaction, $exception->getMessage());

            throw $exception;
        }

        if (! $this->isVerifiedSuccess($verify, $transaction)) {
            $reason = $this->failureReason($verify);
            $this->markFailed($transaction, $reason, $verify);
            $this->notifier->notifyFailed($transaction->fresh(['user', 'deposit', 'order']));

            throw new InvalidArgumentException($reason);
        }

        return DB::transaction(function () use ($transaction, $verify) {
            /** @var SofizPayTransaction $locked */
            $locked = SofizPayTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCompleted()) {
                return $locked->fresh(['user', 'deposit', 'order']);
            }

            $locked->forceFill([
                'raw_verify_response' => $verify,
                'verified_at' => now(),
            ])->save();

            if ($locked->purpose === SofizPayTransactionPurpose::Topup) {
                $this->fulfillTopup($locked);
            } else {
                $locked->loadMissing('user');
                $this->fulfillCheckout($locked);
            }

            $locked->forceFill([
                'status' => SofizPayTransactionStatus::Completed,
                'completed_at' => now(),
                'failure_reason' => null,
            ])->save();

            $fresh = $locked->fresh(['user.wallet', 'deposit', 'order.service']);
            $this->notifier->notifyCompleted($fresh);

            return $fresh;
        });
    }

    public function statusForUser(User $user, string $invoiceId): SofizPayTransaction
    {
        $transaction = SofizPayTransaction::query()
            ->where('invoice_id', $invoiceId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($transaction->isPending() && $transaction->cib_transaction_id) {
            try {
                return $this->verifyAndFulfill($transaction->invoice_id);
            } catch (\Throwable $exception) {
                Log::info('SofizPay status poll pending', [
                    'invoice_id' => $invoiceId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $transaction->fresh(['user', 'deposit', 'order']);
    }

    /**
     * @return array{transaction: SofizPayTransaction, payment_url: string}
     */
    protected function initGatewayPayment(
        User $user,
        SofizPayTransaction $transaction,
        string $phone,
        string $returnUrl,
        string $memo,
    ): array {
        $returnUrlWithInvoice = $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'invoice_id='.urlencode($transaction->invoice_id);

        try {
            $response = $this->client->createTransaction([
                'amount' => (float) $transaction->amount_dzd,
                'full_name' => $user->name,
                'phone' => $phone,
                'email' => $user->email,
                'return_url' => $returnUrlWithInvoice,
                'webhook_url' => $this->webhookUrl(),
                'invoice_id' => $transaction->invoice_id,
                'language' => config('sofizpay.default_language', 'ar'),
                'memo' => $this->truncateMemo($memo),
            ]);
        } catch (SofizPayException $exception) {
            $transaction->update([
                'status' => SofizPayTransactionStatus::Failed,
                'failure_reason' => $exception->getMessage(),
                'raw_init_response' => $exception->response,
            ]);

            throw $exception;
        }

        $paymentUrl = (string) ($response['payment_url'] ?? $response['cib_response']['formUrl'] ?? '');

        if ($paymentUrl === '') {
            throw new SofizPayException('SofizPay did not return a payment URL.', $response);
        }

        $transaction->update([
            'sofizpay_transaction_id' => $response['transaction_id'] ?? null,
            'cib_transaction_id' => (string) ($response['cib_transaction_id'] ?? ''),
            'payment_url' => $paymentUrl,
            'raw_init_response' => $response,
        ]);

        return [
            'transaction' => $transaction->fresh(),
            'payment_url' => $paymentUrl,
        ];
    }

    protected function fulfillTopup(SofizPayTransaction $transaction): void
    {
        $deposit = $transaction->deposit;

        if (! $deposit) {
            throw new InvalidArgumentException('Deposit missing for SofizPay top-up.');
        }

        if ($deposit->status === DepositStatus::Completed) {
            return;
        }

        $this->wallets->creditDeposit($deposit);
        $deposit->update(['status' => DepositStatus::Completed]);
    }

    protected function fulfillCheckout(SofizPayTransaction $transaction): void
    {
        if ($transaction->order_id) {
            return;
        }

        $meta = is_array($transaction->checkout_meta) ? $transaction->checkout_meta : [];
        $service = Service::query()->findOrFail((int) ($meta['service_id'] ?? 0));

        $order = $this->orders->place($transaction->user, $service, [
            'link' => $meta['link'] ?? '',
            'quantity' => (int) ($meta['quantity'] ?? 0),
            'is_repeat' => (bool) ($meta['is_repeat'] ?? false),
            'idempotency_key' => $meta['idempotency_key'] ?? Str::uuid()->toString(),
            'country' => $meta['country'] ?? null,
            'quality' => $meta['quality'] ?? null,
            'comments' => $meta['comments'] ?? null,
            'expected_charge_dzd' => $meta['quoted_charge_dzd'] ?? null,
            'meta' => array_merge($meta, [
                'payment_method' => 'algerie_post',
                'sofizpay_transaction_id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
            ]),
        ]);

        $transaction->update(['order_id' => $order->id]);
    }

    /**
     * @param  array<string, mixed>  $verify
     */
    protected function isVerifiedSuccess(array $verify, SofizPayTransaction $transaction): bool
    {
        if (isset($verify['error']) && is_string($verify['error'])) {
            return false;
        }

        $orderStatus = (int) ($verify['orderStatus'] ?? 0);
        $errorCode = (int) ($verify['errorCode'] ?? -1);

        if ($orderStatus !== 2 || $errorCode !== 0) {
            return false;
        }

        $paidAmount = number_format((float) ($verify['Amount'] ?? 0), 2, '.', '');
        $expected = number_format((float) $transaction->amount_dzd, 2, '.', '');

        if (abs((float) $paidAmount - (float) $expected) > 0.01) {
            return false;
        }

        $destination = (string) ($verify['destination_account'] ?? '');
        $merchant = (string) config('sofizpay.merchant_account');

        return $destination === '' || hash_equals($merchant, $destination);
    }

    /**
     * @param  array<string, mixed>  $verify
     */
    protected function failureReason(array $verify): string
    {
        if (isset($verify['error']) && is_string($verify['error'])) {
            return $verify['error'];
        }

        return (string) ($verify['errorMessage'] ?? $verify['actionCodeDescription'] ?? __('api.sofizpay.payment_not_successful'));
    }

  /**
     * @param  array<string, mixed>|null  $verify
     */
    protected function markFailed(SofizPayTransaction $transaction, string $reason, ?array $verify = null): void
    {
        if ($transaction->isCompleted()) {
            return;
        }

        $transaction->update([
            'status' => SofizPayTransactionStatus::Failed,
            'failure_reason' => mb_substr($reason, 0, 255),
            'raw_verify_response' => $verify ?? $transaction->raw_verify_response,
        ]);
    }

    protected function resolveTransaction(?string $invoiceId, ?string $cibTransactionId): SofizPayTransaction
    {
        if ($invoiceId) {
            return SofizPayTransaction::query()->where('invoice_id', $invoiceId)->firstOrFail();
        }

        if ($cibTransactionId) {
            return SofizPayTransaction::query()->where('cib_transaction_id', $cibTransactionId)->firstOrFail();
        }

        throw new InvalidArgumentException(__('api.sofizpay.missing_reference'));
    }

    protected function assertEnabled(): void
    {
        if (! $this->client->enabled()) {
            throw new InvalidArgumentException(__('api.sofizpay.disabled'));
        }
    }

    protected function makeInvoiceId(string $prefix, int $userId): string
    {
        return sprintf('SP-%s-%d-%s', $prefix, $userId, Str::lower(Str::random(8)));
    }

    protected function persistPhone(User $user, string $phone): void
    {
        if ($user->phone !== $phone) {
            $user->forceFill(['phone' => $phone])->save();
        }
    }

    protected function checkoutReturnUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/checkout/algerie-post/return';
    }

    protected function topupReturnUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/dashboard/billing/return';
    }

    protected function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/api/v1/sofizpay/webhook';
    }

    protected function checkoutMemo(string $serviceName): string
    {
        return 'Order: '.$serviceName;
    }

    protected function topupMemo(string $amount): string
    {
        return 'Top-up '.$amount.' DA';
    }

    protected function truncateMemo(string $memo): string
    {
        return mb_strimwidth($memo, 0, 28, '…');
    }
}
