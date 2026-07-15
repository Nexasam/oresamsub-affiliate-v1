<?php

namespace Tests;

use App\Http\Middleware\SetAffiliate;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(SetAffiliate::class);
        $this->withoutVite();
    }
}
