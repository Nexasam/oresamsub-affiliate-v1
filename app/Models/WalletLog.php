<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletLog extends AffiliateScopedModel
{
    use HasFactory;

    protected $guarded = [];

    public function actionBy(){
        return $this->belongsTo(User::class,'action_by','id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function transaction(){
        return $this->belongsTo(User::class,'transaction_id','id');
    }

    public function fundingHistoryPayload(): array
    {
        $provider = str((string) $this->transaction_category)
            ->beforeLast('_WALLET_FUNDING')
            ->replace('_', ' ')
            ->lower()
            ->headline()
            ->toString();

        return [
            'id' => $this->id,
            'reference' => $this->transaction_id,
            'provider' => $provider ?: 'Wallet Funding',
            'amount' => number_format(max(0, (float) $this->balance_after - (float) $this->balance_before), 2, '.', ''),
            'balance_after' => number_format((float) $this->balance_after, 2, '.', ''),
            'status' => 'Successful',
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

}
