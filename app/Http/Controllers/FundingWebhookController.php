<?php

namespace App\Http\Controllers;

use App\Models\AffiliateFundingProviderConfig;
use App\Models\FundingProvider;
use App\Models\ParentFundingProvider;
use App\Services\Funding\FundingWebhookAdapter;
use App\Services\Funding\FundingWebhookProcessor;
use App\Services\Funding\FundingWebhookRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FundingWebhookController extends Controller
{
    public function __construct(
        private readonly FundingWebhookRecorder $recorder,
        private readonly FundingWebhookAdapter $adapter,
        private readonly FundingWebhookProcessor $processor,
        private readonly \App\Services\Funding\SettlementFundingWebhookProcessor $settlementProcessor,
    ) {}

    public function __invoke(Request $request, FundingProvider $provider, string $webhookKey): JsonResponse
    {
        abort_unless(config('parent_businesses.features.multi_parent_funding', false) && $provider->active, 404);

        $affiliateConfig = AffiliateFundingProviderConfig::query()
            ->where('webhook_key', $webhookKey)->where('webhook_active', true)->where('management_mode', 'affiliate_managed')
            ->whereHas('parentFundingProvider', fn ($query) => $query->where('funding_provider_id', $provider->id))
            ->with('parentFundingProvider')->first();
        $parentProvider = $affiliateConfig ? null : ParentFundingProvider::query()
            ->where('funding_provider_id', $provider->id)->where('webhook_key', $webhookKey)->where('webhook_active', true)->first();

        $signature = $this->adapter->signature($request, $provider);
        $secrets = $this->verificationSecrets($provider, $affiliateConfig, $parentProvider);
        abort_unless($secrets !== [], 404);
        abort_unless(collect($secrets)->contains(
            fn (string $secret) => hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)
        ), 401);

        $payload = $request->json()->all();
        $normalized = $this->adapter->normalize($provider, $payload);
        $externalId = (string) ($request->header('X-Webhook-Id') ?: $normalized['external_id']);
        abort_if($externalId === '', 422, 'A provider event ID is required.');
        $recorded = $this->recorder->record($provider, $externalId, $payload, $affiliateConfig, $parentProvider);
        if ($recorded['duplicate']) {
            $status = $recorded['event']->status;

            return response()->json(['accepted' => true, 'duplicate' => true, 'status' => $status], $status === 'processed' ? 200 : 202);
        }

        $status = $affiliateConfig
            ? $this->processor->process($recorded['event'], $affiliateConfig, $normalized)
            : $this->settlementProcessor->process($recorded['event'], $parentProvider, $normalized);

        return response()->json(['accepted' => true, 'duplicate' => false, 'status' => $status], $status === 'processed' ? 200 : 202);
    }

    private function verificationSecrets(
        FundingProvider $provider,
        ?AffiliateFundingProviderConfig $affiliateConfig,
        ?ParentFundingProvider $parentProvider,
    ): array {
        $secrets = [];
        if ($provider->adapter_key === 'securewaveng') {
            $credentials = $affiliateConfig?->credentials ?? $parentProvider?->credentials ?? [];
            $secrets[] = $credentials['api_secret_key'] ?? null;
        }
        $secrets[] = $affiliateConfig?->webhook_secret ?? $parentProvider?->webhook_secret;

        return collect($secrets)->filter(fn ($secret) => filled($secret))->map(fn ($secret) => (string) $secret)->unique()->values()->all();
    }
}
