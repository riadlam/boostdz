<?php

namespace App\Services\Deposits;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use App\Services\Checkout\CheckoutPolicy;
use App\Services\Wallet\WalletService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class DepositService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly CheckoutPolicy $checkoutPolicy,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, ?UploadedFile $proof = null): Deposit
    {
        $method = (string) ($data['method'] ?? '');
        $amount = number_format((float) ($data['amount_dzd'] ?? 0), 2, '.', '');

        if ((float) $amount <= 0) {
            throw new InvalidArgumentException(__('api.deposits.amount_gt_zero'));
        }

        $this->checkoutPolicy->assertMinimumTopup($amount);

        if ($method !== 'ccp') {
            throw new InvalidArgumentException(__('api.deposits.invalid_method'));
        }

        $wallet = $this->wallets->forUser($user);
        $proofPath = null;

        if (! $proof) {
            throw new InvalidArgumentException(__('api.deposits.ccp_proof_required'));
        }

        $proofPath = $proof->store('deposits/'.$user->id, 'public');

        $deposit = Deposit::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount_dzd' => $amount,
            'method' => $method,
            'status' => DepositStatus::Pending,
            'proof_path' => $proofPath,
            'wired_amount_dzd' => isset($data['wired_amount_dzd'])
                ? number_format((float) $data['wired_amount_dzd'], 2, '.', '')
                : null,
            'provider_reference' => $data['provider_reference'] ?? null,
        ]);

        return $deposit->fresh(['wallet']);
    }

    public function approve(Deposit $deposit, User $admin, ?string $note = null): Deposit
    {
        if ($deposit->status !== DepositStatus::Pending) {
            throw new InvalidArgumentException(__('api.deposits.pending_only_approve'));
        }

        $deposit->update([
            'status' => DepositStatus::Approved,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_note' => $note,
        ]);

        $this->wallets->creditDeposit($deposit);

        $deposit->update(['status' => DepositStatus::Completed]);

        return $deposit->fresh(['wallet', 'reviewer']);
    }

    public function reject(Deposit $deposit, User $admin, ?string $note = null): Deposit
    {
        if ($deposit->status !== DepositStatus::Pending) {
            throw new InvalidArgumentException(__('api.deposits.pending_only_reject'));
        }

        if ($deposit->proof_path) {
            Storage::disk('public')->delete($deposit->proof_path);
        }

        $deposit->update([
            'status' => DepositStatus::Rejected,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_note' => $note,
        ]);

        return $deposit->fresh(['wallet', 'reviewer']);
    }
}
