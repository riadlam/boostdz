<?php

namespace App\Services\Wallet;

use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletService
{
    public function forUser(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'currency' => 'DZD',
            'balance' => 0,
            'locked_balance' => 0,
        ]);
    }

    public function credit(
        Wallet $wallet,
        string $amountDzd,
        WalletTransactionType $type,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $meta = null,
    ): WalletTransaction {
        return $this->apply($wallet, $amountDzd, $type, $description, $referenceType, $referenceId, $meta);
    }

    public function debit(
        Wallet $wallet,
        string $amountDzd,
        WalletTransactionType $type,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $meta = null,
    ): WalletTransaction {
        $amount = (float) $amountDzd;

        if ($amount <= 0) {
            throw new InvalidArgumentException(__('api.wallet.debit_positive'));
        }

        if ((float) $wallet->availableBalance() < $amount) {
            throw new InvalidArgumentException(__('api.wallet.insufficient_balance'));
        }

        return $this->apply($wallet, '-'.$amountDzd, $type, $description, $referenceType, $referenceId, $meta);
    }

    protected function apply(
        Wallet $wallet,
        string $signedAmount,
        WalletTransactionType $type,
        ?string $description,
        ?string $referenceType,
        ?int $referenceId,
        ?array $meta,
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $signedAmount, $type, $description, $referenceType, $referenceId, $meta) {
            /** @var Wallet $locked */
            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $before = (float) $locked->balance;
            $after = round($before + (float) $signedAmount, 2);

            if ($after < 0) {
                throw new InvalidArgumentException(__('api.wallet.insufficient_balance'));
            }

            $locked->update(['balance' => number_format($after, 2, '.', '')]);

            return WalletTransaction::query()->create([
                'wallet_id' => $locked->id,
                'user_id' => $locked->user_id,
                'type' => $type,
                'amount_dzd' => $signedAmount,
                'balance_before' => number_format($before, 2, '.', ''),
                'balance_after' => number_format($after, 2, '.', ''),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'meta' => $meta,
            ]);
        });
    }

    public function creditDeposit(Deposit $deposit): WalletTransaction
    {
        $wallet = $deposit->wallet;

        return $this->credit(
            $wallet,
            (string) $deposit->amount_dzd,
            WalletTransactionType::Deposit,
            'Deposit via '.$deposit->method,
            'deposit',
            $deposit->id,
        );
    }

    public function chargeOrder(Order $order): WalletTransaction
    {
        $wallet = $this->forUser($order->user);

        return $this->debit(
            $wallet,
            (string) $order->charge_dzd,
            WalletTransactionType::OrderCharge,
            'Order #'.$order->id,
            'order',
            $order->id,
        );
    }
}
