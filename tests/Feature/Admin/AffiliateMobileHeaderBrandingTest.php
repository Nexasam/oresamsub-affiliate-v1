<?php

use Illuminate\Support\Facades\Blade;

it('shows the current affiliate name in the mobile header when no logo is configured', function () {
    ['affiliate' => $affiliate, 'user' => $user] = affiliateTestContext();

    config(['app.name' => 'Emiplug']);

    $this->actingAs($user);

    $html = Blade::render("@include('partials.topnav')");

    expect($html)
        ->toContain($affiliate->name)
        ->not->toContain('>Emiplug</h1>');
});
