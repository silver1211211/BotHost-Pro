<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function getBalance(User $user): string
    {
        return number_format((float) ($user->wallet_balance ?? 0), 2, '.', '');
    }

    public function debit(User $user, float|string $amount, string $description, ?string $referenceType = null, ?int $referenceId = null, array $metadata = []): WalletTransaction
    {
        return $this->move($user, 'debit', $amount, $description, $referenceType, $referenceId, $metadata);
    }

    public function credit(User $user, float|string $amount, string $description, ?string $referenceType = null, ?int $referenceId = null, array $metadata = []): WalletTransaction
    {
        return $this->move($user, 'credit', $amount, $description, $referenceType, $referenceId, $metadata);
    }

    private function move(User $user, string $type, float|string $amount, string $description, ?string $referenceType, ?int $referenceId, array $metadata): WalletTransaction
    {
        $amount = $this->money($amount);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['wallet' => 'Amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($user, $type, $amount, $description, $referenceType, $referenceId, $metadata): WalletTransaction {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $before = $this->money($locked->wallet_balance ?? 0);
            $after = $type === 'credit' ? $before + $amount : $before - $amount;

            if ($after < 0) {
                throw ValidationException::withMessages(['wallet' => 'Insufficient wallet balance.']);
            }

            $locked->forceFill(['wallet_balance' => $this->format($after), 'wallet_currency' => $locked->wallet_currency ?: 'USD'])->save();

            return WalletTransaction::create([
                'user_id' => $locked->id,
                'type' => $type === 'credit' ? 'credit' : 'purchase',
                'amount' => $this->format($amount),
                'balance_before' => $this->format($before),
                'balance_after' => $this->format($after),
                'currency' => $locked->wallet_currency ?: 'USD',
                'status' => 'completed',
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'metadata' => $metadata,
            ]);
        });
    }

    private function money(float|string $amount): float
    {
        return round((float) $amount, 2);
    }

    private function format(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
