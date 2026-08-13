<?php

namespace App\Services\Funding;

use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingProvider;
use App\Models\FundingWebhookEvent;
use App\Models\ParentFundingProvider;
use Illuminate\Database\UniqueConstraintViolationException;

class FundingWebhookRecorder
{
    public function record(FundingProvider $provider, string $externalEventId, array $payload, ?AffiliateFundingProviderConfig $config = null, ?ParentFundingProvider $parentProvider = null): array
    {
        try {
            $event = FundingWebhookEvent::create([
                'funding_provider_id' => $provider->id,
                'parent_business_id' => $config?->parentFundingProvider?->parent_business_id ?? $parentProvider?->parent_business_id,
                'affiliate_id' => $config?->affiliate_id,
                'affiliate_funding_provider_config_id' => $config?->id,
                'external_event_id' => $externalEventId,
                'payload' => $payload,
                'status' => 'received',
            ]);

            return ['event' => $event, 'duplicate' => false];
        } catch (UniqueConstraintViolationException) {
            return [
                'event' => FundingWebhookEvent::where('funding_provider_id', $provider->id)->where('external_event_id', $externalEventId)->sole(),
                'duplicate' => true,
            ];
        }
    }
}
