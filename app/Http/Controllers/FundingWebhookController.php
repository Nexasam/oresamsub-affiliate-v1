<?php

namespace App\Http\Controllers;

use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingProvider;
use App\Models\ParentFundingProvider;
use App\Services\Funding\FundingWebhookRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FundingWebhookController extends Controller
{
    public function __construct(private readonly FundingWebhookRecorder $recorder) {}

    public function __invoke(Request $request, FundingProvider $provider, string $webhookKey): JsonResponse
    {
        abort_unless(config('parent_businesses.features.multi_parent_funding', false) && $provider->active, 404);

        $affiliateConfig = AffiliateFundingProviderConfig::query()
            ->where('webhook_key', $webhookKey)->where('webhook_active', true)->where('management_mode', 'affiliate_managed')
            ->whereHas('parentFundingProvider', fn ($query) => $query->where('funding_provider_id', $provider->id))
            ->with('parentFundingProvider')->first();
        $parentProvider = $affiliateConfig ? null : ParentFundingProvider::query()
            ->where('funding_provider_id', $provider->id)->where('webhook_key', $webhookKey)->where('webhook_active', true)->first();

        $secret = $affiliateConfig?->webhook_secret ?? $parentProvider?->webhook_secret;
        abort_unless(filled($secret), 404);

        $signature = (string) $request->header('X-Webhook-Signature');
        abort_unless(hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature), 401);

        $payload = $request->json()->all();
        $externalId = (string) ($request->header('X-Webhook-Id') ?: data_get($payload, 'reference'));
        abort_if($externalId === '', 422, 'A provider event ID is required.');
        $recorded = $this->recorder->record($provider, $externalId, $payload, $affiliateConfig);

        return response()->json(['accepted' => true, 'duplicate' => $recorded['duplicate']], 202);
    }
}
