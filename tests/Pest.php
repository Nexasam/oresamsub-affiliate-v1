<?php

use App\Models\Affiliate;
use App\Models\AffiliateUserPlan;
use App\Models\LandingPagesSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

require_once __DIR__.'/Support/MultiParentFixtures.php';

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function affiliateTestContext(array $userAttributes = []): array
{
    static $sequence = 0;
    $sequence++;

    $affiliate = Affiliate::create([
        'name' => "Test Affiliate {$sequence}",
        'slug' => "test-affiliate-{$sequence}",
        'affiliate_plan_id' => 1,
        'ip_address' => "127.20.0.{$sequence}",
        'contact_phone' => '0804'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT),
        'contact_email' => "affiliate-{$sequence}@example.test",
        'parent_key' => "test-key-{$sequence}",
        'parent_email' => "parent-{$sequence}@example.test",
        'domain_url' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost',
    ]);

    session()->put('affiliate', $affiliate);

    $role = Role::firstOrCreate(['role_name' => 'User']);
    $plan = AffiliateUserPlan::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id,
        'user_plan_name' => 'Basic',
        'plan_level' => 1,
        'visibility' => 1,
    ]);
    LandingPagesSetting::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id,
        'field_name' => 'support_whatsapp_number',
        'field_details' => '08030000000',
    ]);

    $user = User::factory()->create(array_merge([
        'affiliate_id' => $affiliate->id,
        'role_id' => $role->id,
        'user_plan_id' => $plan->id,
        'pin' => '4321',
    ], $userAttributes));

    return compact('affiliate', 'plan', 'role', 'user');
}
