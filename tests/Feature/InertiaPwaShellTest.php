<?php

namespace Tests\Feature;

use App\Http\Controllers\PwaManifestController;
use App\Models\Affiliate;
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
            ->assertSee('<link rel="manifest" href="'.route('pwa.manifest').'">', false)
            ->assertSee('name="theme-color"', false);
    }

    public function test_manifest_uses_the_current_affiliate_brand(): void
    {
        session()->put('affiliate', new Affiliate([
            'name' => 'Tommy Telecoms',
            'slug' => 'tommy',
            'logo' => 'uploads/affiliates/tommy.png',
        ]));

        $manifest = app(PwaManifestController::class)()->getData(true);

        $this->assertSame('Tommy Telecoms', $manifest['name']);
        $this->assertSame(url('uploads/affiliates/tommy.png'), $manifest['icons'][0]['src']);
        $this->assertSame('/?affiliate=tommy', $manifest['id']);
    }
}
