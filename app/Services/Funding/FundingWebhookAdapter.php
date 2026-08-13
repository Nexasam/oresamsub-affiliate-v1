<?php

namespace App\Services\Funding;

use App\Models\FundingProvider;
use Illuminate\Http\Request;

class FundingWebhookAdapter
{
    public function signature(Request $request, FundingProvider $provider): string
    {
        return match ($provider->adapter_key) {
            'xixapay' => (string) ($request->header('XIXAPAY') ?: $request->header('X-Webhook-Signature')),
            'securewaveng' => (string) ($request->header('X-Signature') ?: $request->header('X-Webhook-Signature')),
            default => (string) $request->header('X-Webhook-Signature'),
        };
    }

    public function normalize(FundingProvider $provider, array $payload): array
    {
        $data = match ($provider->adapter_key) {
            'xixapay' => [
                'external_id' => data_get($payload, 'transaction_id'),
                'successful' => data_get($payload, 'notification_status') === 'payment_successful'
                    && data_get($payload, 'transaction_status') === 'success',
                'email' => data_get($payload, 'customer.email'),
                'gross_amount' => data_get($payload, 'amount_paid'),
                'settlement_amount' => data_get($payload, 'settlement_amount'),
                'bank_name' => data_get($payload, 'receiver.bank'),
                'account_number' => data_get($payload, 'receiver.account_number'),
                'account_reference' => data_get($payload, 'receiver.account_reference'),
            ],
            'securewaveng' => [
                'external_id' => data_get($payload, 'provider_reference'),
                'successful' => data_get($payload, 'transaction_status') === 'success',
                'email' => data_get($payload, 'customer.email'),
                'gross_amount' => data_get($payload, 'amount'),
                'settlement_amount' => data_get($payload, 'settlement_amount'),
                'bank_name' => data_get($payload, 'receiver.bank'),
                'account_number' => data_get($payload, 'receiver.account_number'),
                'account_reference' => data_get($payload, 'receiver.account_reference'),
            ],
            default => [
                'external_id' => data_get($payload, 'reference'),
                'successful' => false,
                'email' => null,
                'gross_amount' => null,
                'settlement_amount' => null,
                'bank_name' => null,
                'account_number' => null,
                'account_reference' => null,
            ],
        };

        return $data;
    }
}
