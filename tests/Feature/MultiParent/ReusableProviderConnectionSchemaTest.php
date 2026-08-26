<?php

use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProviderAdapter;
use App\Models\ProviderConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('separates reusable adapters provider connections and parent credentials', function () {
    expect(Schema::hasColumns('provider_adapters', ['name', 'slug', 'adapter_key', 'capabilities', 'settings', 'version', 'status']))->toBeTrue()
        ->and(Schema::hasColumns('provider_connections', ['provider_adapter_id', 'base_url', 'website_url', 'documentation_url', 'settings', 'adapter_version']))->toBeTrue()
        ->and(Schema::hasColumns('parent_provider_connections', ['provider_adapter_id', 'request_type', 'proposed_provider_name', 'proposed_base_url', 'proposed_documentation_url', 'discovery_notes']))->toBeTrue();

    $adapter = ProviderAdapter::create([
        'name' => 'MSORG', 'slug' => 'msorg', 'adapter_key' => 'configurable_http',
        'capabilities' => ['services' => ['data'], 'credential_fields' => ['api_public_key']],
        'settings' => ['http_method' => 'POST'], 'version' => 1, 'status' => 'active',
    ]);
    $provider = ProviderConnection::create([
        'provider_adapter_id' => $adapter->id, 'name' => 'PaulTechs', 'slug' => 'paultechs',
        'adapter' => 'configurable_http', 'capabilities' => $adapter->capabilities,
        'settings' => ['endpoints' => ['data' => 'https://paultechs.test/api/data']],
        'adapter_version' => 1, 'status' => 'active',
    ]);
    $parent = ParentBusiness::create(['name' => 'Parent', 'slug' => 'schema-parent']);
    $parentConnection = ParentProviderConnection::create([
        'parent_business_id' => $parent->id, 'provider_adapter_id' => $adapter->id,
        'provider_connection_id' => null, 'request_type' => 'discovery', 'name' => 'My PaulTechs',
        'proposed_provider_name' => 'New PaulTechs Site', 'proposed_base_url' => 'https://new-provider.test',
        'credentials' => ['api_public_key' => 'secret'], 'status' => 'active',
    ]);

    expect($adapter->connections()->sole()->is($provider))->toBeTrue()
        ->and($provider->providerAdapter->is($adapter))->toBeTrue()
        ->and($parentConnection->providerAdapter->is($adapter))->toBeTrue()
        ->and($parentConnection->providerConnection)->toBeNull()
        ->and($parentConnection->toArray())->not->toHaveKey('credentials');
});
