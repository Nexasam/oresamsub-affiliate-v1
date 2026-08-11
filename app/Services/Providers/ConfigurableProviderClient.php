<?php

namespace App\Services\Providers;

use App\Models\ParentProviderConnection;
use App\Support\ProviderProductRegistry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class ConfigurableProviderClient
{
    public function __construct(private readonly ProviderProductRegistry $products) {}

    public function execute(ParentProviderConnection $connection, string $productSlug, array $runtime): array
    {
        $connection->loadMissing('providerConnection');

        if ($connection->approval_status !== 'approved') {
            return $this->failure('This provider connection has not been approved.');
        }
        if ($connection->status !== 'active' || $connection->providerConnection?->status !== 'active') {
            return $this->failure('This provider connection is inactive.');
        }

        $settings = $connection->settings ?? [];
        $productSlug = $this->products->normalize($productSlug);
        $capabilities = $this->products->normalizeCapabilities($connection->providerConnection?->capabilities);
        if (! in_array($productSlug, $capabilities['services'] ?? [], true)) {
            return $this->failure("This adapter does not support {$productSlug}.");
        }
        $endpoint = $this->endpoint($connection, $settings, $productSlug);
        if (! $endpoint) {
            return $this->failure("No endpoint is configured for {$productSlug}.");
        }
        $productSettings = $this->productSettings($settings, $productSlug);

        try {
            $payload = $this->mapValues(
                $productSettings['request_parameters'] ?? [],
                $runtime,
                $connection->credentials ?? [],
                $productSettings['network_mapping'] ?? []
            );
            $headers = $this->mapHeaders(
                $productSettings['request_headers'] ?? [],
                $runtime,
                $connection->credentials ?? [],
                $productSettings['network_mapping'] ?? []
            );

            $timeout = min(120, max(5, (int) ($settings['timeout_seconds'] ?? 30)));
            $request = Http::acceptJson()->asJson()->connectTimeout(min(10, $timeout))->timeout($timeout)->withHeaders($headers);
            $method = strtoupper($settings['http_method'] ?? 'POST');
            $response = $method === 'GET' ? $request->get($endpoint, $payload) : $request->post($endpoint, $payload);

            return $this->interpret($response, $productSettings);
        } catch (ConnectionException) {
            return $this->failure('The provider response is uncertain and requires reconciliation.', ambiguous: true);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure($this->safeConfigurationMessage($exception));
        }
    }

    /** Requery an uncertain purchase without repeating the vending request. */
    public function requery(ParentProviderConnection $connection, string $productSlug, array $runtime): array
    {
        $connection->loadMissing('providerConnection');
        $settings = $connection->settings ?? [];
        $productSlug = $this->products->normalize($productSlug);
        $productSettings = $this->productSettings($settings, $productSlug);
        $endpoint = $productSettings['requery_endpoint'] ?? $settings['requery_endpoint'] ?? null;
        if (! $endpoint) {
            return $this->failure('No provider requery endpoint is configured.', ambiguous: true);
        }

        try {
            $mappings = $productSettings['requery_parameters'] ?? [
                ['key' => 'reference', 'type' => 'runtime', 'value' => 'reference'],
            ];
            $payload = $this->mapValues($mappings, $runtime, $connection->credentials ?? [], $productSettings['network_mapping'] ?? []);
            $headers = $this->mapHeaders($productSettings['request_headers'] ?? [], $runtime, $connection->credentials ?? [], $productSettings['network_mapping'] ?? []);
            $timeout = min(120, max(5, (int) ($settings['timeout_seconds'] ?? 30)));
            $request = Http::acceptJson()->asJson()->connectTimeout(min(10, $timeout))->timeout($timeout)->withHeaders($headers);
            $method = strtoupper($productSettings['requery_http_method'] ?? $settings['requery_http_method'] ?? 'POST');
            $response = $method === 'GET' ? $request->get($endpoint, $payload) : $request->post($endpoint, $payload);

            return $this->interpret($response, $productSettings);
        } catch (ConnectionException) {
            return $this->failure('The provider requery response remains uncertain.', ambiguous: true);
        } catch (Throwable $exception) {
            report($exception);
            return $this->failure($this->safeConfigurationMessage($exception), ambiguous: true);
        }
    }

    private function productSettings(array $settings, string $productSlug): array
    {
        $productConfigs = $settings['product_configs'] ?? [];
        $productConfig = $productConfigs[$productSlug] ?? null;

        if (! $productConfig) {
            $legacyKey = array_search($productSlug, ProviderProductRegistry::LEGACY_ALIASES, true);
            $productConfig = $legacyKey ? ($productConfigs[$legacyKey] ?? null) : null;
        }

        return is_array($productConfig) ? $productConfig : $settings;
    }

    private function endpoint(ParentProviderConnection $connection, array $settings, string $productSlug): ?string
    {
        $endpoints = $settings['endpoints'] ?? [];
        $endpoint = $endpoints[$productSlug] ?? null;

        if (! $endpoint) {
            $legacyKey = array_search($productSlug, ProviderProductRegistry::LEGACY_ALIASES, true);
            $endpoint = $legacyKey ? ($endpoints[$legacyKey] ?? null) : null;
        }

        return $endpoint ?: $connection->base_url;
    }

    private function mapValues(array $mappings, array $runtime, array $credentials, array $networkMapping): array
    {
        $payload = [];

        foreach ($mappings as $mapping) {
            $payload[$mapping['key']] = $this->resolveValue($mapping, $runtime, $credentials, $networkMapping);
        }

        return $payload;
    }

    private function mapHeaders(array $mappings, array $runtime, array $credentials, array $networkMapping): array
    {
        $headers = [];

        foreach ($mappings as $mapping) {
            $value = $this->resolveValue($mapping, $runtime, $credentials, $networkMapping);
            $headers[$mapping['key']] = ($mapping['prefix'] ?? '').$value.($mapping['suffix'] ?? '');
        }

        return $headers;
    }

    private function resolveValue(array $mapping, array $runtime, array $credentials, array $networkMapping): mixed
    {
        return match ($mapping['type'] ?? 'literal') {
            'runtime' => $this->runtimeValue((string) ($mapping['value'] ?? ''), $runtime, $networkMapping),
            'credential' => $this->credentialValue((string) ($mapping['value'] ?? ''), $credentials),
            default => $mapping['value'] ?? null,
        };
    }

    private function runtimeValue(string $field, array $runtime, array $networkMapping): mixed
    {
        if (! array_key_exists($field, $runtime)) {
            throw new \InvalidArgumentException("Missing runtime field: {$field}.");
        }

        if ($field !== 'network') {
            return $runtime[$field];
        }

        $network = (string) $runtime[$field];
        $mappedKey = collect(array_keys($networkMapping))->first(
            fn ($configuredNetwork) => strcasecmp((string) $configuredNetwork, $network) === 0
        );
        if ($mappedKey === null || blank($networkMapping[$mappedKey])) {
            throw new \InvalidArgumentException("No provider network mapping exists for {$network}.");
        }

        return $networkMapping[$mappedKey];
    }

    private function credentialValue(string $field, array $credentials): string
    {
        if (! isset($credentials[$field]) || blank($credentials[$field])) {
            throw new \InvalidArgumentException("The required provider credential {$field} is not configured.");
        }

        return (string) $credentials[$field];
    }

    private function interpret(Response $response, array $settings): array
    {
        $decoded = $response->json();
        if (! is_array($decoded)) {
            return $this->failure(
                $response->successful() ? 'The provider returned an invalid response.' : 'The provider request failed.',
                httpStatus: $response->status()
            );
        }

        $expectedCode = $settings['expected_success_code'] ?? null;
        $httpPassed = $expectedCode ? $response->status() === (int) $expectedCode : $response->successful();
        $conditionsPassed = collect($settings['success_conditions'] ?? [])->every(function ($condition) use ($decoded) {
            return $this->normalize(data_get($decoded, $condition['key'])) == $this->normalize($condition['value'] ?? null);
        });
        $successful = $httpPassed && $conditionsPassed;
        $messagePath = $successful ? ($settings['success_message_path'] ?? null) : ($settings['failure_message_path'] ?? null);
        $message = $messagePath ? data_get($decoded, $messagePath) : null;

        return [
            'successful' => $successful,
            'ambiguous' => false,
            'message' => is_scalar($message) ? (string) $message : ($successful ? 'Transaction was successful.' : 'Transaction failed.'),
            'provider_reference' => data_get($decoded, 'provider_reference')
                ?? data_get($decoded, 'reference')
                ?? data_get($decoded, 'data.reference'),
            'http_status' => $response->status(),
            'provider_response' => $decoded,
        ];
    }

    private function normalize(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return match (strtolower(trim($value))) {
            'true' => true,
            'false' => false,
            default => trim($value),
        };
    }

    private function safeConfigurationMessage(Throwable $exception): string
    {
        return $exception instanceof \InvalidArgumentException
            ? $exception->getMessage()
            : 'The provider request could not be prepared.';
    }

    private function failure(string $message, bool $ambiguous = false, ?int $httpStatus = null): array
    {
        return [
            'successful' => false,
            'ambiguous' => $ambiguous,
            'message' => $message,
            'provider_reference' => null,
            'http_status' => $httpStatus,
            'provider_response' => null,
        ];
    }
}
