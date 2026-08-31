<?php

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function loginIdentifierAffiliate(string $slug): Affiliate
{
    return Affiliate::create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'affiliate_plan_id' => 1,
        'ip_address' => '127.40.0.'.(Affiliate::count() + 1),
        'contact_phone' => '0805000000'.(Affiliate::count() + 1),
        'contact_email' => $slug.'@example.test',
        'parent_key' => $slug.'-key',
        'parent_email' => 'parent-'.$slug.'@example.test',
        'domain_url' => $slug.'.example.test',
    ]);
}

function loginIdentifierCustomer(Affiliate $affiliate, string $suffix): User
{
    return User::withoutGlobalScope('affiliate')->create([
        'affiliate_id' => $affiliate->id,
        'username' => 'customer-'.$suffix,
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'phone_number' => '081600000'.$suffix,
        'email' => 'customer-'.$suffix.'@example.test',
        'role_id' => 1,
        'password' => 'StrongPass1!',
        'email_verified_at' => now(),
    ]);
}

it('allows a customer to sign in with email username or phone', function (string $attribute) {
    $affiliate = loginIdentifierAffiliate('login-'.$attribute);
    $customer = loginIdentifierCustomer($affiliate, match ($attribute) {
        'email' => '01',
        'username' => '02',
        default => '03',
    });

    $this->withSession(['affiliate' => $affiliate])
        ->post('/login', [
            'login' => $customer->{$attribute},
            'password' => 'StrongPass1!',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($customer);
})->with(['email', 'username', 'phone_number']);

it('does not authenticate a matching identifier owned by another affiliate', function () {
    $currentAffiliate = loginIdentifierAffiliate('current-login-site');
    $otherAffiliate = loginIdentifierAffiliate('other-login-site');
    $otherCustomer = loginIdentifierCustomer($otherAffiliate, '04');

    $this->withSession(['affiliate' => $currentAffiliate])
        ->from('/login')
        ->post('/login', [
            'login' => $otherCustomer->username,
            'password' => 'StrongPass1!',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('keeps accepting the legacy email field during rollout', function () {
    $affiliate = loginIdentifierAffiliate('legacy-login-field');
    $customer = loginIdentifierCustomer($affiliate, '05');

    $this->withSession(['affiliate' => $affiliate])
        ->post('/login', [
            'email' => $customer->email,
            'password' => 'StrongPass1!',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($customer);
});

