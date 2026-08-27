<?php

use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ProviderConnection;
use App\Models\ProviderAdapter;
use App\Services\Providers\ConfigurableProviderClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

function executableProviderConnection(array $overrides = []): ParentProviderConnection
{
    $parent = ParentBusiness::create(['name' => 'Execution Parent', 'slug' => 'execution-parent']);
    $adapter = ProviderConnection::create([
        'name' => 'Configurable HTTP', 'slug' => 'execution-http', 'adapter' => 'execution_http',
        'capabilities' => [
            'services' => ['data', 'airtime', 'utility_bills', 'cable_subscription', 'e_pins', 'result_checker'],
            'methods' => ['GET', 'POST'],
            'credential_fields' => ['api_public_key'],
        ],
        'status' => 'active',
    ]);

    $attributes = array_replace_recursive([
        'parent_business_id' => $parent->id,
        'provider_connection_id' => $adapter->id,
        'name' => 'Execution primary',
        'base_url' => null,
        'credentials' => ['api_public_key' => 'provider-secret-token'],
        'settings' => [
            'http_method' => 'POST',
            'timeout_seconds' => 20,
            'endpoints' => [
                'data' => 'https://provider.example/data',
                'airtime' => 'https://provider.example/airtime',
                'utility_bills' => 'https://provider.example/electricity',
                'cable_subscription' => 'https://provider.example/cable',
                'e_pins' => 'https://provider.example/epins',
                'result_checker' => 'https://provider.example/results',
            ],
            'request_parameters' => [
                ['key' => 'phone', 'type' => 'runtime', 'value' => 'phone_number'],
                ['key' => 'plan_id', 'type' => 'runtime', 'value' => 'plan'],
                ['key' => 'request_id', 'type' => 'runtime', 'value' => 'reference'],
            ],
            'request_headers' => [
                ['key' => 'Authorization', 'type' => 'credential', 'value' => 'api_public_key', 'prefix' => 'Bearer '],
                ['key' => 'Accept', 'type' => 'literal', 'value' => 'application/json'],
            ],
            'network_mapping' => ['MTN' => '1'],
            'success_conditions' => [
                ['key' => 'status', 'value' => 'true'],
                ['key' => 'data.state', 'value' => 'completed'],
            ],
            'success_message_path' => 'data.message',
            'failure_message_path' => 'error.message',
            'expected_success_code' => 200,
            'product_configs' => [
                'data' => [
                    'request_parameters' => [
                        ['key' => 'data_phone', 'type' => 'runtime', 'value' => 'phone_number'],
                    ],
                    'request_headers' => [],
                    'network_mapping' => ['MTN' => 'DATA-MTN'],
                    'success_conditions' => [['key' => 'data_status', 'value' => 'delivered']],
                    'success_message_path' => 'data.message',
                    'failure_message_path' => 'error.message',
                    'expected_success_code' => 200,
                ],
                'airtime' => [
                    'request_parameters' => [
                        ['key' => 'airtime_mobile', 'type' => 'runtime', 'value' => 'phone_number'],
                    ],
                    'request_headers' => [
                        ['key' => 'X-Airtime-Key', 'type' => 'credential', 'value' => 'api_public_key'],
                    ],
                    'network_mapping' => ['MTN' => 'AIRTIME-MTN'],
                    'success_conditions' => [['key' => 'airtime.ok', 'value' => 'true']],
                    'success_message_path' => 'airtime.message',
                    'failure_message_path' => 'airtime.error',
                    'expected_success_code' => 201,
                ],
            ],
        ],
        'status' => 'active',
        'approval_status' => 'approved',
        'submitted_at' => now(),
        'approved_at' => now(),
    ], $overrides);

    return ParentProviderConnection::create($attributes);
}

it('executes any configured product endpoint with mapped runtime values and credential headers', function () {
    $connection = executableProviderConnection();
    Http::fake(['provider.example/*' => Http::response([
        'status' => true,
        'data' => ['state' => 'completed', 'message' => 'PIN generated', 'reference' => 'VENDOR-55'],
    ], 200)]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'e_pins', [
        'phone_number' => '08030000000', 'plan' => 'WAEC-1', 'reference' => 'ORDER-10001',
    ]);

    expect($result)->toMatchArray([
        'successful' => true, 'ambiguous' => false, 'message' => 'PIN generated',
        'provider_reference' => 'VENDOR-55', 'http_status' => 200,
    ]);
    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://provider.example/epins'
            && $request['request_id'] === 'ORDER-10001'
            && $request->header('Authorization')[0] === 'Bearer provider-secret-token';
    });
});

it('inherits adapter authorization headers when the shared connection only overrides its endpoint', function () {
    $parent = ParentBusiness::create(['name' => 'MSORG Parent', 'slug' => 'msorg-parent']);
    $adapter = ProviderAdapter::create([
        'name' => 'MSORG',
        'slug' => 'msorg-layered',
        'adapter_key' => 'msorg_layered',
        'capabilities' => ['services' => ['data'], 'credential_fields' => ['api_token']],
        'settings' => [
            'http_method' => 'POST',
            'endpoints' => ['data' => '/api/data/'],
            'product_configs' => ['data' => [
                'request_parameters' => [['key' => 'plan', 'type' => 'runtime', 'value' => 'plan']],
                'request_headers' => [[
                    'key' => 'Authorization', 'type' => 'credential', 'value' => 'api_token', 'prefix' => 'Token ',
                ]],
                'success_conditions' => [['key' => 'Status', 'value' => 'successful']],
                'success_message_path' => 'api_response',
                'failure_message_path' => 'api_response',
            ]],
        ],
        'version' => 1,
        'status' => 'active',
    ]);
    $provider = ProviderConnection::create([
        'provider_adapter_id' => $adapter->id,
        'name' => 'Gongoz',
        'slug' => 'gongoz-layered',
        'adapter' => 'msorg_layered',
        'capabilities' => ['services' => ['data']],
        'base_url' => 'https://gongoz.example',
        'settings' => ['endpoints' => ['data' => '/api/data/']],
        'status' => 'active',
    ]);
    $connection = ParentProviderConnection::create([
        'parent_business_id' => $parent->id,
        'provider_adapter_id' => $adapter->id,
        'provider_connection_id' => $provider->id,
        'name' => 'Gongoz parent',
        'credentials' => ['api_token' => 'gongoz-secret'],
        'settings' => ['is_primary' => false],
        'status' => 'active',
        'approval_status' => 'approved',
    ]);
    Http::fake(['gongoz.example/*' => Http::response(['Status' => 'successful', 'api_response' => 'Delivered'])]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'data', ['plan' => '389']);

    expect($result['successful'])->toBeTrue();
    Http::assertSent(fn (Request $request) => $request->header('Authorization')[0] === 'Token gongoz-secret');
});

it('resolves reusable relative adapter endpoints against the provider connection base URL', function () {
    $connection = executableProviderConnection([
        'settings' => [
            'endpoints' => ['data' => '/api/data'],
            'product_configs' => ['data' => [
                'request_parameters' => [['key' => 'phone', 'type' => 'runtime', 'value' => 'phone_number']],
                'request_headers' => [],
                'network_mapping' => [],
                'success_conditions' => [['key' => 'success', 'value' => 'true']],
                'success_message_path' => 'message',
                'failure_message_path' => 'message',
            ]],
        ],
    ]);
    $connection->providerConnection->update(['base_url' => 'https://parent-provider.test']);
    Http::fake(['parent-provider.test/*' => Http::response(['success' => true, 'message' => 'Delivered'])]);

    $result = app(ConfigurableProviderClient::class)->execute($connection->fresh(), 'data', [
        'phone_number' => '08030000000', 'reference' => 'RELATIVE-1',
    ]);

    expect($result['successful'])->toBeTrue();
    Http::assertSent(fn (Request $request) => $request->url() === 'https://parent-provider.test/api/data');
});

it('uses mappings headers network IDs and response rules from the selected product only', function () {
    $connection = executableProviderConnection();
    Http::fake(['provider.example/*' => Http::response([
        'airtime' => ['ok' => true, 'message' => 'Airtime delivered'],
    ], 201)]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'airtime', [
        'phone_number' => '08030000000', 'network' => 'MTN', 'reference' => 'ORDER-AIRTIME',
    ]);

    expect($result)->toMatchArray([
        'successful' => true,
        'message' => 'Airtime delivered',
        'http_status' => 201,
    ]);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://provider.example/airtime'
        && $request['airtime_mobile'] === '08030000000'
        && ! isset($request['data_phone'])
        && $request->header('X-Airtime-Key')[0] === 'provider-secret-token');
});

it('extracts the actual provider charge from the configured response path', function () {
    $connection = executableProviderConnection([
        'settings' => ['product_configs' => ['airtime' => [
            'actual_charge_path' => 'data.transaction.discounted_amount',
        ]]],
    ]);
    Http::fake(['provider.example/*' => Http::response([
        'airtime' => ['ok' => true, 'message' => 'Airtime delivered'],
        'data' => ['transaction' => ['discounted_amount' => '99.00']],
    ], 201)]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'airtime', [
        'phone_number' => '08030000000', 'network' => 'MTN', 'reference' => 'ORDER-AIRTIME-COST',
    ]);

    expect($result['successful'])->toBeTrue()
        ->and($result['actual_provider_charge'])->toBe('99.00');
});

it('ignores a missing or invalid configured actual provider charge', function (mixed $charge) {
    $connection = executableProviderConnection([
        'settings' => ['product_configs' => ['airtime' => [
            'actual_charge_path' => 'data.charged',
        ]]],
    ]);
    Http::fake(['provider.example/*' => Http::response([
        'airtime' => ['ok' => true, 'message' => 'Airtime delivered'],
        'data' => ['charged' => $charge],
    ], 201)]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'airtime', [
        'phone_number' => '08030000000', 'network' => 'MTN', 'reference' => 'ORDER-AIRTIME-FALLBACK',
    ]);

    expect($result['successful'])->toBeTrue()
        ->and($result['actual_provider_charge'])->toBeNull();
})->with(['missing' => null, 'non numeric' => 'unknown', 'negative' => '-1.00', 'zero' => '0']);

it('adds the required separator when an authorization prefix was saved without a trailing space', function (string $prefix) {
    $connection = executableProviderConnection([
        'settings' => ['product_configs' => ['data' => [
            'request_parameters' => [['key' => 'request_id', 'type' => 'runtime', 'value' => 'reference']],
            'request_headers' => [[
                'key' => 'Authorization', 'type' => 'credential', 'value' => 'api_public_key', 'prefix' => $prefix,
            ]],
            'success_conditions' => [['key' => 'status', 'value' => 'success']],
        ]]],
    ]);
    Http::fake(['provider.example/*' => Http::response(['status' => 'success'])]);

    app(ConfigurableProviderClient::class)->execute($connection, 'data', ['reference' => 'ORDER-BEARER-SPACE']);

    Http::assertSent(fn (Request $request) => $request->header('Authorization')[0] === $prefix.' provider-secret-token');
})->with(['Bearer', 'Token']);

it('falls back to legacy shared mappings when a product configuration has not been migrated', function () {
    $connection = executableProviderConnection(['settings' => ['product_configs' => []]]);
    Http::fake(['provider.example/*' => Http::response([
        'status' => true,
        'data' => ['state' => 'completed', 'message' => 'Legacy configuration worked'],
    ], 200)]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'result_checker', [
        'phone_number' => '08030000000', 'plan' => 'WAEC', 'reference' => 'ORDER-LEGACY',
    ]);

    expect($result)->toMatchArray(['successful' => true, 'message' => 'Legacy configuration worked']);
    Http::assertSent(fn (Request $request) => $request['phone'] === '08030000000');
});

it('supports configured get requests and legacy product endpoint aliases', function () {
    $connection = executableProviderConnection([
        'settings' => [
            'http_method' => 'GET',
            'endpoints' => ['utility_bills' => null, 'electricity' => 'https://provider.example/legacy-electricity'],
        ],
    ]);
    Http::fake(['provider.example/*' => Http::response([
        'status' => true, 'data' => ['state' => 'completed', 'message' => 'Token generated'],
    ])]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'utility_bills', [
        'phone_number' => '08030000000', 'plan' => 'IKEDC', 'reference' => 'ORDER-2',
    ]);

    expect($result['successful'])->toBeTrue();
    Http::assertSent(fn (Request $request) => $request->method() === 'GET' && str_contains($request->url(), 'legacy-electricity'));
});

it('maps provider network identifiers and fails safely when a required mapping is missing', function () {
    $connection = executableProviderConnection([
        'settings' => [
            'product_configs' => ['data' => [
                'request_parameters' => [['key' => 'network_id', 'type' => 'runtime', 'value' => 'network']],
            ]],
        ],
    ]);
    Http::fake();

    $missing = app(ConfigurableProviderClient::class)->execute($connection, 'data', [
        'network' => 'GLO', 'reference' => 'ORDER-3',
    ]);

    expect($missing)->toMatchArray(['successful' => false, 'ambiguous' => false])
        ->and($missing['message'])->toContain('network mapping');
    Http::assertNothingSent();
});

it('matches configured network names without case sensitivity', function () {
    $connection = executableProviderConnection([
        'settings' => ['product_configs' => ['data' => [
            'request_parameters' => [['key' => 'network_id', 'type' => 'runtime', 'value' => 'network']],
            'network_mapping' => ['MTN' => '1'],
            'success_conditions' => [['key' => 'status', 'value' => 'success']],
        ]]],
    ]);
    Http::fake(['provider.example/*' => Http::response(['status' => 'success'])]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'data', ['network' => 'mtn']);

    expect($result['successful'])->toBeTrue();
    Http::assertSent(fn (Request $request) => $request['network_id'] === '1');
});

it('does not execute inactive unapproved or unsupported provider connections', function (array $override, string $message) {
    $connection = executableProviderConnection($override);
    Http::fake();

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'data', ['reference' => 'ORDER-4']);

    expect($result)->toMatchArray(['successful' => false, 'ambiguous' => false])
        ->and($result['message'])->toContain($message);
    Http::assertNothingSent();
})->with([
    'pending approval' => [['approval_status' => 'pending'], 'approved'],
    'inactive parent connection' => [['status' => 'inactive'], 'inactive'],
    'unsupported product' => [['settings' => ['endpoints' => ['data' => null]]], 'endpoint'],
]);

it('returns an ambiguous result when the provider connection times out', function () {
    $connection = executableProviderConnection();
    Http::fake(fn () => throw new ConnectionException('Timed out'));

    $timeout = app(ConfigurableProviderClient::class)->execute($connection, 'data', [
        'phone_number' => '08030000000', 'plan' => '1GB', 'reference' => 'ORDER-5',
    ]);
    expect($timeout)->toMatchArray(['successful' => false, 'ambiguous' => true, 'http_status' => null]);
});

it('returns a conclusive failure for provider business errors', function () {
    $connection = executableProviderConnection();

    Http::fake(['provider.example/*' => Http::response(['status' => false, 'error' => ['message' => 'Insufficient balance']], 200)]);
    $failure = app(ConfigurableProviderClient::class)->execute($connection, 'data', [
        'phone_number' => '08030000000', 'plan' => '1GB', 'reference' => 'ORDER-6',
    ]);
    expect($failure)->toMatchArray([
        'successful' => false, 'ambiguous' => false, 'message' => 'Insufficient balance', 'http_status' => 200,
    ]);
});

it('logs the provider request boundary and redacted response for transaction diagnosis', function () {
    $connection = executableProviderConnection();
    Log::spy();
    Http::fake(['provider.example/*' => Http::response([
        'status' => false,
        'error' => ['message' => 'Upstream wallet is insufficient'],
        'api_token' => 'response-secret-token',
    ], 200)]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'data', [
        'phone_number' => '08030000000', 'plan' => '1GB', 'reference' => 'ORDER-LOG-1',
    ]);

    expect($result['provider_response'])->toBe([
        'status' => false,
        'error' => ['message' => 'Upstream wallet is insufficient'],
        'api_token' => '[REDACTED]',
    ]);

    Log::shouldHaveReceived('info')->with('provider.request.prepared', Mockery::on(
        fn (array $context) => $context['reference'] === 'ORDER-LOG-1'
            && $context['connection_id'] === $connection->id
            && $context['endpoint'] === 'https://provider.example/data'
            && $context['payload']['data_phone'] === '[REDACTED]'
            && ! array_key_exists('headers', $context)
    ))->once();
    Log::shouldHaveReceived('info')->with('provider.response.received', Mockery::on(
        fn (array $context) => $context['reference'] === 'ORDER-LOG-1'
            && $context['http_status'] === 200
            && $context['response']['error']['message'] === 'Upstream wallet is insufficient'
            && $context['response']['api_token'] === '[REDACTED]'
    ))->once();
});

it('fails safely for invalid json responses', function () {
    $connection = executableProviderConnection();
    Http::fake(['provider.example/*' => Http::response('<html>bad gateway</html>', 502)]);

    $result = app(ConfigurableProviderClient::class)->execute($connection, 'result_checker', [
        'phone_number' => '08030000000', 'plan' => 'NECO', 'reference' => 'ORDER-7',
    ]);

    expect($result)->toMatchArray(['successful' => false, 'ambiguous' => false, 'http_status' => 502])
        ->and($result['provider_response'])->toBeNull();
});

it('requeries an uncertain purchase through the separately configured status endpoint', function () {
    $connection = executableProviderConnection([
        'settings' => ['product_configs' => ['data' => [
            'requery_endpoint' => 'https://provider.example/data/status',
            'requery_http_method' => 'POST',
            'requery_parameters' => [['key' => 'request_id', 'type' => 'runtime', 'value' => 'reference']],
        ]]],
    ]);
    Http::fake(['provider.example/data/status' => Http::response([
        'data_status' => 'delivered', 'data' => ['message' => 'Confirmed', 'reference' => 'UPSTREAM-9'],
    ], 200)]);

    $result = app(ConfigurableProviderClient::class)->requery($connection, 'data', [
        'reference' => 'ORDER-REQUERY-1', 'provider_reference' => 'UPSTREAM-9',
    ]);

    expect($result)->toMatchArray(['successful' => true, 'ambiguous' => false, 'message' => 'Confirmed']);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://provider.example/data/status'
        && $request['request_id'] === 'ORDER-REQUERY-1');
});

it('validates a cable customer through its separate configured operation without vending', function () {
    $connection = executableProviderConnection([
        'settings' => ['product_configs' => ['cable_subscription' => [
            'validation' => [
                'endpoint' => 'https://provider.example/cable/validate',
                'http_method' => 'POST',
                'request_parameters' => [
                    ['key' => 'smartcard', 'type' => 'runtime', 'value' => 'smartcard_number'],
                    ['key' => 'service', 'type' => 'runtime', 'value' => 'service_provider'],
                ],
                'request_headers' => [[
                    'key' => 'Authorization', 'type' => 'credential', 'value' => 'api_public_key', 'prefix' => 'Bearer',
                ]],
                'success_conditions' => [['key' => 'success', 'value' => 'true']],
                'success_message_path' => 'message',
                'failure_message_path' => 'message',
                'customer_name_path' => 'data.customer_name',
                'customer_address_path' => 'data.address',
                'expected_success_code' => 200,
            ],
        ]]],
    ]);
    Http::fake([
        'provider.example/cable/validate' => Http::response([
            'success' => true, 'message' => 'Customer verified',
            'data' => ['customer_name' => 'Ada Customer', 'address' => 'Lagos'],
        ]),
    ]);

    $result = app(ConfigurableProviderClient::class)->validateCustomer($connection, 'cable_subscription', [
        'smartcard_number' => '1234567890', 'service_provider' => 'DSTV', 'reference' => 'VALIDATE-CABLE-1',
    ]);

    expect($result)->toMatchArray([
        'successful' => true, 'message' => 'Customer verified',
        'customer_name' => 'Ada Customer', 'customer_address' => 'Lagos',
    ]);
    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://provider.example/cable/validate'
        && $request['smartcard'] === '1234567890'
        && $request['service'] === 'DSTV'
        && $request->header('Authorization')[0] === 'Bearer provider-secret-token');
});

it('logs a redacted provider response when customer validation fails', function () {
    $connection = executableProviderConnection([
        'settings' => ['product_configs' => ['utility_bills' => [
            'validation' => [
                'endpoint' => 'https://provider.example/electricity/validate',
                'http_method' => 'POST',
                'request_parameters' => [
                    ['key' => 'customer_number', 'type' => 'runtime', 'value' => 'meter_number'],
                ],
                'request_headers' => [[
                    'key' => 'Authorization', 'type' => 'credential', 'value' => 'api_public_key', 'prefix' => 'Bearer',
                ]],
                'success_conditions' => [['key' => 'success', 'value' => 'true']],
                'success_message_path' => 'message',
                'failure_message_path' => 'message',
                'customer_name_path' => 'data.customer_name',
                'customer_address_path' => 'data.address',
                'expected_success_code' => 200,
            ],
        ]]],
    ]);
    Http::fake(['provider.example/electricity/validate' => Http::response([
        'success' => false,
        'message' => 'Meter does not belong to IBEDC PREPAID',
        'data' => ['meter_number' => '45082894648'],
    ], 422)]);
    Log::spy();

    $result = app(ConfigurableProviderClient::class)->validateCustomer($connection, 'utility_bills', [
        'meter_number' => '45082894648',
        'reference' => 'VALIDATE-METER-1',
    ]);

    expect($result)->toMatchArray([
        'successful' => false,
        'message' => 'Meter does not belong to IBEDC PREPAID',
        'http_status' => 422,
    ]);
    Log::shouldHaveReceived('info')->withArgs(function (string $event, array $context): bool {
        return $event === 'provider.validation.response.received'
            && $context['reference'] === 'VALIDATE-METER-1'
            && $context['http_status'] === 422
            && $context['successful'] === false
            && $context['message'] === 'Meter does not belong to IBEDC PREPAID'
            && data_get($context, 'response.data.meter_number') === '[REDACTED]';
    })->once();
});
