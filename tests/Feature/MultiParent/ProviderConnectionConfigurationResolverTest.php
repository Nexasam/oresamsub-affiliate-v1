<?php

use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use App\Services\Providers\ProviderConnectionConfigurationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function configurationConnection(?array $shared, array $legacy = [], ?string $sharedUrl = null, ?string $legacyUrl = null): ParentProviderConnection
{
    $adapter = ProviderAdapter::create(['name' => uniqid('Adapter '), 'slug' => uniqid('adapter-'), 'adapter_key' => uniqid('adapter_'), 'status' => 'active']);
    $provider = ProviderConnection::create(['provider_adapter_id' => $adapter->id, 'name' => uniqid('Provider '), 'slug' => uniqid('provider-'), 'adapter' => $adapter->adapter_key, 'settings' => $shared, 'base_url' => $sharedUrl, 'status' => 'active']);
    $parent = ParentBusiness::create(['name' => uniqid('Parent '), 'slug' => uniqid('parent-')]);

    return ParentProviderConnection::create(['parent_business_id' => $parent->id, 'provider_adapter_id' => $adapter->id, 'provider_connection_id' => $provider->id, 'name' => 'Primary', 'settings' => $legacy, 'base_url' => $legacyUrl, 'status' => 'active']);
}

it('prefers shared provider configuration for new connections', function () {
    $connection = configurationConnection(['http_method' => 'POST', 'timeout_seconds' => 20], ['http_method' => 'GET'], 'https://shared.test/api', 'https://legacy.test/api');
    $resolver = app(ProviderConnectionConfigurationResolver::class);

    expect($resolver->settings($connection))->toBe(['http_method' => 'POST', 'timeout_seconds' => 20])
        ->and($resolver->baseUrl($connection))->toBe('https://shared.test/api');
});

it('keeps legacy parent configuration as a non destructive fallback', function () {
    $connection = configurationConnection(null, ['http_method' => 'GET'], null, 'https://legacy.test/api');
    $resolver = app(ProviderConnectionConfigurationResolver::class);

    expect($resolver->settings($connection))->toBe(['http_method' => 'GET'])
        ->and($resolver->baseUrl($connection))->toBe('https://legacy.test/api');
});
