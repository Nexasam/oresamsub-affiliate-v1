<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResellGridLandingPageTest extends TestCase
{
    public function test_marketing_host_bypasses_affiliate_tenant_resolution(): void
    {
        config()->set('resellgrid.marketing_host', 'affiliate.emiplug.com');

        $this->withServerVariables(['HTTP_HOST' => 'affiliate.emiplug.com'])
            ->get('http://affiliate.emiplug.com/')
            ->assertOk()
            ->assertSee('data-resellgrid-landing', false);
    }

    public function test_marketing_host_receives_the_resellgrid_landing_page(): void
    {
        config()->set('resellgrid.marketing_host', 'affiliate.emiplug.com');

        $response = $this
            ->withoutMiddleware()
            ->withServerVariables(['HTTP_HOST' => 'affiliate.emiplug.com'])
            ->get('http://affiliate.emiplug.com/');

        $response
            ->assertOk()
            ->assertSee('data-resellgrid-landing', false)
            ->assertSee('Turn one VTU business into a network of growing affiliate brands.')
            ->assertSee('Built for parent businesses')
            ->assertSee('Built for affiliates')
            ->assertSee('One operational system')
            ->assertSee('Talk to us on WhatsApp')
            ->assertSee('/assets/resellgrid/landing.css', false)
            ->assertSee('/assets/resellgrid/landing.js', false);
    }

    public function test_whatsapp_cta_uses_configured_number_and_message(): void
    {
        config()->set('resellgrid.marketing_host', 'affiliate.emiplug.com');
        config()->set('resellgrid.whatsapp.number', '+234 801 234 5678');
        config()->set('resellgrid.whatsapp.message', 'Hello ResellGrid, show me the platform.');

        $response = $this
            ->withoutMiddleware()
            ->withServerVariables(['HTTP_HOST' => 'affiliate.emiplug.com'])
            ->get('http://affiliate.emiplug.com/');

        $response->assertSee(
            'https://wa.me/2348012345678?text=Hello%20ResellGrid%2C%20show%20me%20the%20platform.',
            false
        );
    }
}
