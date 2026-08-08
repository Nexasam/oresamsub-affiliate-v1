<?php

use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ParentResellerLevel;
use App\Models\ProviderConnection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the parent provider foundation schema', function () {
    expect(Schema::hasColumns('parent_businesses', ['id', 'name', 'slug', 'status']))->toBeTrue()
        ->and(Schema::hasColumns('parent_admins', ['parent_business_id', 'email', 'password', 'must_change_password']))->toBeTrue()
        ->and(Schema::hasColumns('provider_connections', ['name', 'slug', 'adapter', 'capabilities', 'status']))->toBeTrue()
        ->and(Schema::hasColumns('parent_provider_connections', ['parent_business_id', 'provider_connection_id', 'base_url', 'credentials', 'settings']))->toBeTrue()
        ->and(Schema::hasColumns('parent_reseller_levels', ['parent_business_id', 'name', 'position', 'status']))->toBeTrue();
});

it('encrypts provider credentials and hides them from serialization', function () {
    $parent = ParentBusiness::create(['name' => 'Acme', 'slug' => 'acme']);
    $provider = ProviderConnection::create([
        'name' => 'Provider One',
        'slug' => 'provider-one',
        'adapter' => 'provider-one',
    ]);

    $connection = ParentProviderConnection::create([
        'parent_business_id' => $parent->id,
        'provider_connection_id' => $provider->id,
        'name' => 'Primary',
        'credentials' => ['token' => 'secret-value'],
    ]);

    $storedCredentials = DB::table('parent_provider_connections')
        ->where('id', $connection->id)
        ->value('credentials');

    expect($storedCredentials)->not->toContain('secret-value')
        ->and($connection->fresh()->credentials)->toBe(['token' => 'secret-value'])
        ->and($connection->toArray())->not->toHaveKey('credentials');
});

it('exposes a parents provider connections and reseller levels', function () {
    $parent = ParentBusiness::create(['name' => 'Acme', 'slug' => 'acme']);
    $provider = ProviderConnection::create([
        'name' => 'Provider One',
        'slug' => 'provider-one',
        'adapter' => 'provider-one',
    ]);
    $connection = ParentProviderConnection::create([
        'parent_business_id' => $parent->id,
        'provider_connection_id' => $provider->id,
        'name' => 'Primary',
    ]);
    $level = ParentResellerLevel::create([
        'parent_business_id' => $parent->id,
        'name' => 'Starter',
        'position' => 1,
    ]);

    expect($parent->providerConnections->modelKeys())->toBe([$connection->id])
        ->and($parent->resellerLevels->modelKeys())->toBe([$level->id]);
});

it('requires unique parent slugs', function () {
    ParentBusiness::create(['name' => 'Acme', 'slug' => 'acme']);

    expect(fn () => ParentBusiness::create(['name' => 'Other', 'slug' => 'acme']))
        ->toThrow(QueryException::class);
});

it('requires unique provider slugs', function () {
    ProviderConnection::create(['name' => 'One', 'slug' => 'provider', 'adapter' => 'one']);

    expect(fn () => ProviderConnection::create(['name' => 'Two', 'slug' => 'provider', 'adapter' => 'two']))
        ->toThrow(QueryException::class);
});

it('requires unique provider connection identities within a parent', function () {
    $parent = ParentBusiness::create(['name' => 'Acme', 'slug' => 'acme']);
    $provider = ProviderConnection::create(['name' => 'One', 'slug' => 'one', 'adapter' => 'one']);
    $attributes = [
        'parent_business_id' => $parent->id,
        'provider_connection_id' => $provider->id,
        'name' => 'Primary',
    ];
    ParentProviderConnection::create($attributes);

    expect(fn () => ParentProviderConnection::create($attributes))
        ->toThrow(QueryException::class);
});

it('requires unique reseller level positions within a parent', function () {
    $parent = ParentBusiness::create(['name' => 'Acme', 'slug' => 'acme']);
    $attributes = [
        'parent_business_id' => $parent->id,
        'name' => 'Starter',
        'position' => 1,
    ];
    ParentResellerLevel::create($attributes);

    expect(fn () => ParentResellerLevel::create([...$attributes, 'name' => 'Growth']))
        ->toThrow(QueryException::class);
});

it('rejects reseller level positions outside one through six', function (int $position) {
    $parent = ParentBusiness::create(['name' => 'Acme', 'slug' => 'acme']);

    expect(fn () => DB::table('parent_reseller_levels')->insert([
        'parent_business_id' => $parent->id,
        'name' => 'Invalid',
        'position' => $position,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
})->with([0, 7]);
