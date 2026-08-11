<?php

use App\Http\Middleware\SetAffiliate;

it('renders the configured affiliate landing page', function () {
    affiliateTestContext();
    $this->withMiddleware(SetAffiliate::class);
    $response = $this->get('/');

    $response->assertStatus(200);
});
