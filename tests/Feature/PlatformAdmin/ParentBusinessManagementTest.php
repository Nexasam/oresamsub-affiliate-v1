<?php

use App\Models\Admin;
use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function parentBusinessPlatformAdmin(): Admin
{
    return Admin::create([
        'name' => 'Tenant Manager',
        'email' => 'tenant-manager@example.test',
        'password' => 'secret-password',
        'active' => true,
    ]);
}

function parentBusinessPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'business' => [
            'name' => 'Paultechs',
            'slug' => 'paultechs',
            'contact_email' => 'support@paultechs.example',
            'contact_phone' => '2347030989671',
            'status' => 'active',
        ],
        'admin' => [
            'name' => 'Paultechs Owner',
            'email' => 'owner@paultechs.example',
            'password' => 'Temporary-Password-2026!',
            'active' => true,
            'must_change_password' => true,
        ],
    ], $overrides);
}

it('protects the parent business workspace with the platform guard', function () {
    $this->get('/admin/parent-businesses')->assertRedirect('/admin/login');
    $this->getJson('/admin/parent-businesses/data')->assertUnauthorized();
    $this->postJson('/admin/parent-businesses', parentBusinessPayload())->assertUnauthorized();
});

it('renders and lists existing parent businesses without password data', function () {
    $parent = ParentBusiness::create([
        'name' => 'Existing Parent', 'slug' => 'existing-parent',
        'contact_email' => 'existing@example.test', 'contact_phone' => '08030000000', 'status' => 'active',
    ]);
    ParentAdmin::create([
        'parent_business_id' => $parent->id, 'name' => 'Existing Owner',
        'email' => 'existing-owner@example.test', 'password' => 'never-return-this', 'active' => true,
    ]);

    $admin = parentBusinessPlatformAdmin();
    $this->actingAs($admin, 'platform_admin')->get('/admin/parent-businesses')
        ->assertOk()->assertSee('Parent businesses')->assertSee('Create parent business');

    $this->actingAs($admin, 'platform_admin')->getJson('/admin/parent-businesses/data')
        ->assertOk()
        ->assertJsonPath('parents.0.name', 'Existing Parent')
        ->assertJsonPath('parents.0.admins.0.email', 'existing-owner@example.test')
        ->assertJsonMissing(['never-return-this'])
        ->assertJsonMissingPath('parents.0.admins.0.password');
});

it('atomically creates a parent business first administrator and six default reseller levels', function () {
    $response = $this->actingAs(parentBusinessPlatformAdmin(), 'platform_admin')
        ->postJson('/admin/parent-businesses', parentBusinessPayload())
        ->assertCreated()
        ->assertJsonPath('parent.slug', 'paultechs')
        ->assertJsonPath('parent.level_count', 6)
        ->assertJsonPath('parent.admins.0.email', 'owner@paultechs.example')
        ->assertJsonMissing(['Temporary-Password-2026!']);

    $parent = ParentBusiness::where('slug', 'paultechs')->sole();
    $parentAdmin = ParentAdmin::where('parent_business_id', $parent->id)->sole();

    expect($parent->resellerLevels()->orderBy('position')->pluck('name')->all())
        ->toBe(['Basic', 'Bronze', 'Silver', 'Gold', 'Diamond', 'Platinum'])
        ->and($parentAdmin->active)->toBeTrue()
        ->and($parentAdmin->must_change_password)->toBeTrue()
        ->and(Hash::check('Temporary-Password-2026!', $parentAdmin->password))->toBeTrue()
        ->and($response->json('parent.provider_connection_count'))->toBe(0);
});

it('rejects duplicate business slugs and parent administrator emails', function () {
    $admin = parentBusinessPlatformAdmin();
    ParentBusiness::create(['name' => 'Taken', 'slug' => 'paultechs']);
    $otherParent = ParentBusiness::create(['name' => 'Other', 'slug' => 'other-parent']);
    ParentAdmin::create([
        'parent_business_id' => $otherParent->id, 'name' => 'Taken Admin',
        'email' => 'owner@paultechs.example', 'password' => 'secret-password', 'active' => true,
    ]);

    $this->actingAs($admin, 'platform_admin')->postJson('/admin/parent-businesses', parentBusinessPayload())
        ->assertUnprocessable()->assertJsonValidationErrors(['business.slug', 'admin.email']);
});

it('does not create partial parent records when validation fails', function () {
    $this->actingAs(parentBusinessPlatformAdmin(), 'platform_admin')->postJson('/admin/parent-businesses', parentBusinessPayload([
        'business' => ['name' => '', 'slug' => 'Not A Valid Slug'],
        'admin' => ['email' => 'invalid-email', 'password' => 'short'],
    ]))->assertUnprocessable();

    expect(ParentBusiness::count())->toBe(0)
        ->and(ParentAdmin::count())->toBe(0);
});
