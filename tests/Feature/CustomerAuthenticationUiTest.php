<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CustomerAuthenticationUiTest extends TestCase
{
    public function test_shared_authentication_shell_renders_mobile_first_branding(): void
    {
        $html = Blade::render(
            '<x-customer-auth.shell title="Welcome back" description="Sign in securely" :affiliate="$affiliate" site-logo="emiplug.png"><form>Fields</form></x-customer-auth.shell>',
            ['affiliate' => (object) ['name' => 'Emiplug', 'logo' => 'uploads/affiliates/favicon.png']]
        );

        $this->assertStringContainsString('data-testid="customer-auth-shell"', $html);
        $this->assertStringContainsString('Emiplug', $html);
        $this->assertStringContainsString('assets/landing_page_assets/img/site_logo/emiplug.png', $html);
        $this->assertStringContainsString('Welcome back', $html);
        $this->assertStringContainsString('Sign in securely', $html);
        $this->assertStringContainsString('<form>Fields</form>', $html);
    }
}
