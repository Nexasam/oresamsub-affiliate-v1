<?php

namespace App\Services\Wallet;

use App\Models\Affiliate;
use App\Models\AffiliateSettlementLedgerEntry;
use App\Models\AffiliateSettlementWallet;
use App\Models\ParentAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AffiliateSettlementWalletService
{
    public function reserve(Affiliate $affiliate, string $amount, string $purchaseReference, string $actorType, int $actorId): AffiliateSettlementWallet
    {
        return $this->transition($affiliate, $amount, $purchaseReference, 'reserve', 'purchase_reservation', $actorType, $actorId);
    }

    public function capture(Affiliate $affiliate, string $amount, string $purchaseReference, string $actorType, int $actorId): AffiliateSettlementWallet
    {
        return $this->transition($affiliate, $amount, $purchaseReference, 'capture', 'purchase_capture', $actorType, $actorId);
    }

    public function release(Affiliate $affiliate, string $amount, string $purchaseReference, string $actorType, int $actorId): AffiliateSettlementWallet
    {
        return $this->transition($affiliate, $amount, $purchaseReference, 'release', 'reservation_release', $actorType, $actorId);
    }

    public function refund(Affiliate $affiliate, string $amount, string $purchaseReference, string $actorType, int $actorId): AffiliateSettlementWallet
    {
        return $this->transition($affiliate, $amount, $purchaseReference, 'refund', 'refund', $actorType, $actorId);
    }

    public function credit(
        Affiliate $affiliate,
        ParentAdmin $actor,
        string $amount,
        string $reference,
        string $reason,
    ): AffiliateSettlementWallet {
        if (! $affiliate->parent_business_id || (int) $affiliate->parent_business_id !== (int) $actor->parent_business_id) {
            $this->fail('affiliate', 'You cannot fund an affiliate outside your parent business.');
        }

        $amount = $this->normalizePositiveMoney($amount);
        $reference = trim($reference);
        $reason = trim($reason);

        if ($reference === '') {
            $this->fail('reference', 'A funding reference is required.');
        }

        if ($reason === '') {
            $this->fail('reason', 'A funding reason is required.');
        }

        return DB::transaction(function () use ($affiliate, $actor, $amount, $reference, $reason) {
            if (AffiliateSettlementLedgerEntry::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('reference', $reference)
                ->exists()) {
                $this->fail('reference', 'This settlement funding reference has already been used.');
            }

            AffiliateSettlementWallet::query()->firstOrCreate(
                ['affiliate_id' => $affiliate->id],
                [
                    'parent_business_id' => $affiliate->parent_business_id,
                    'currency' => 'NGN',
                    'available_balance' => '0.00',
                    'reserved_balance' => '0.00',
                    'status' => 'active',
                ],
            );

            $wallet = AffiliateSettlementWallet::query()
                ->where('affiliate_id', $affiliate->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $wallet->parent_business_id !== (int) $affiliate->parent_business_id) {
                $this->fail('affiliate', 'The affiliate settlement wallet belongs to another parent business.');
            }

            if ($wallet->status !== 'active') {
                $this->fail('wallet', 'The affiliate settlement wallet is not active.');
            }

            $before = $wallet->available_balance;
            $after = $this->addMoney($before, $amount);
            $wallet->forceFill(['available_balance' => $after])->save();

            $wallet->ledgerEntries()->create([
                'parent_business_id' => $affiliate->parent_business_id,
                'affiliate_id' => $affiliate->id,
                'entry_type' => 'manual_credit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference' => $reference,
                'actor_type' => 'parent_admin',
                'actor_id' => $actor->id,
                'reason' => $reason,
            ]);

            return $wallet->fresh();
        });
    }

    private function transition(
        Affiliate $affiliate,
        string $amount,
        string $purchaseReference,
        string $action,
        string $entryType,
        string $actorType,
        int $actorId,
    ): AffiliateSettlementWallet {
        if (! $affiliate->parent_business_id) {
            $this->fail('affiliate', 'The affiliate must belong to a parent business.');
        }
        $amount = $this->normalizePositiveMoney($amount);
        $purchaseReference = trim($purchaseReference);
        if ($purchaseReference === '') {
            $this->fail('reference', 'A purchase reference is required.');
        }
        $reference = $this->actionReference($purchaseReference, $action);

        return DB::transaction(function () use ($affiliate, $amount, $purchaseReference, $reference, $action, $entryType, $actorType, $actorId) {
            $existing = AffiliateSettlementLedgerEntry::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('reference', $reference)
                ->first();
            if ($existing) {
                if ($existing->amount !== $amount || (int) $existing->affiliate_id !== (int) $affiliate->id) {
                    $this->fail('reference', 'This settlement action reference was already used with different details.');
                }

                return AffiliateSettlementWallet::query()->findOrFail($existing->affiliate_settlement_wallet_id);
            }

            $wallet = AffiliateSettlementWallet::query()
                ->where('affiliate_id', $affiliate->id)
                ->lockForUpdate()
                ->first();
            if (! $wallet || (int) $wallet->parent_business_id !== (int) $affiliate->parent_business_id) {
                $this->fail('wallet', 'The affiliate settlement wallet is not configured.');
            }
            if ($wallet->status !== 'active') {
                $this->fail('wallet', 'The affiliate settlement wallet is not active.');
            }

            $reserveEntry = AffiliateSettlementLedgerEntry::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('reference', $this->actionReference($purchaseReference, 'reserve'))
                ->first();
            $captureEntry = AffiliateSettlementLedgerEntry::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('reference', $this->actionReference($purchaseReference, 'capture'))
                ->first();
            $releaseEntry = AffiliateSettlementLedgerEntry::query()
                ->where('parent_business_id', $affiliate->parent_business_id)
                ->where('reference', $this->actionReference($purchaseReference, 'release'))
                ->first();

            if (in_array($action, ['capture', 'release'], true) && $reserveEntry?->amount !== $amount) {
                $this->fail('amount', 'The settlement amount must exactly match the original reservation.');
            }
            if ($action === 'refund' && $captureEntry?->amount !== $amount) {
                $this->fail('amount', 'The settlement refund must exactly match the captured amount.');
            }

            $availableBefore = $this->toCents($wallet->available_balance);
            $reservedBefore = $this->toCents($wallet->reserved_balance);
            $cents = $this->toCents($amount);
            $availableAfter = $availableBefore;
            $reservedAfter = $reservedBefore;

            if ($action === 'reserve') {
                if ($availableBefore < $cents) {
                    $this->fail('wallet', 'The affiliate settlement wallet has insufficient available balance.');
                }
                $availableAfter -= $cents;
                $reservedAfter += $cents;
            } elseif ($action === 'capture') {
                if (! $reserveEntry || $releaseEntry || $reservedBefore < $cents) {
                    $this->fail('wallet', 'This purchase does not have enough active reserved settlement funds.');
                }
                $reservedAfter -= $cents;
            } elseif ($action === 'release') {
                if (! $reserveEntry || $captureEntry || $reservedBefore < $cents) {
                    $this->fail('wallet', 'This purchase does not have enough releasable settlement funds.');
                }
                $reservedAfter -= $cents;
                $availableAfter += $cents;
            } elseif ($action === 'refund') {
                if (! $captureEntry) {
                    $this->fail('wallet', 'Only captured settlement funds can be refunded.');
                }
                $availableAfter += $cents;
            }

            $wallet->forceFill([
                'available_balance' => $this->fromCents($availableAfter),
                'reserved_balance' => $this->fromCents($reservedAfter),
            ])->save();

            $wallet->ledgerEntries()->create([
                'parent_business_id' => $affiliate->parent_business_id,
                'affiliate_id' => $affiliate->id,
                'entry_type' => $entryType,
                'amount' => $amount,
                'balance_before' => $this->fromCents($availableBefore),
                'balance_after' => $this->fromCents($availableAfter),
                'reference' => $reference,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'reason' => "Settlement {$entryType} for {$purchaseReference}",
                'metadata' => [
                    'purchase_reference' => $purchaseReference,
                    'reserved_before' => $this->fromCents($reservedBefore),
                    'reserved_after' => $this->fromCents($reservedAfter),
                ],
            ]);

            return $wallet->fresh();
        });
    }

    private function actionReference(string $purchaseReference, string $action): string
    {
        $reference = "{$purchaseReference}:{$action}";

        return strlen($reference) <= 191 ? $reference : substr($purchaseReference, 0, 100).':'.hash('sha256', $reference).":{$action}";
    }

    private function normalizePositiveMoney(string $amount): string
    {
        $amount = trim($amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) || $this->toCents($amount) <= 0) {
            $this->fail('amount', 'The amount must be greater than zero with no more than two decimal places.');
        }

        return $this->fromCents($this->toCents($amount));
    }

    private function addMoney(string $left, string $right): string
    {
        return $this->fromCents($this->toCents($left) + $this->toCents($right));
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
