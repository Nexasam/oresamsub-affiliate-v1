<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Tests\TestCase;

class InertiaPwaShellTest extends TestCase
{
    public function test_inertia_document_exposes_the_pwa_manifest_before_react_mounts(): void
    {
        Route::get('/_test/inertia-pwa-shell', fn () => Inertia::render('Auth/Login'));

        $this->get('/_test/inertia-pwa-shell')
            ->assertOk()
            ->assertSee('<link rel="manifest" href="'.asset('manifest.json').'">', false)
            ->assertSee('name="theme-color"', false);
    }
}
