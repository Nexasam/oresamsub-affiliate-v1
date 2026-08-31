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

    public function test_shared_authentication_shell_renders_the_trust_message(): void
    {
        $html = Blade::render(
            '<x-customer-auth.shell title="Welcome back" :affiliate="$affiliate"><form>Fields</form></x-customer-auth.shell>',
            ['affiliate' => (object) ['name' => 'Emiplug', 'logo' => null]]
        );

        $this->assertStringContainsString('data-testid="auth-trust-message"', $html);
        $this->assertStringContainsString('Protected and private', $html);
    }

    public function test_blade_authentication_layout_supports_a_persistent_theme_toggle(): void
    {
        $layout = file_get_contents(resource_path('views/oresamsub/layouts/authapp.blade.php'));

        $this->assertStringContainsString('data-testid="auth-theme-toggle"', $layout);
        $this->assertStringContainsString("localStorage.getItem('theme')", $layout);
        $this->assertStringContainsString("localStorage.setItem('theme'", $layout);
    }

    public function test_react_login_uses_the_shared_premium_authentication_surface(): void
    {
        $login = file_get_contents(resource_path('js/Pages/Auth/Login.jsx'));

        $this->assertStringContainsString('data-testid="customer-login-card"', $login);
        $this->assertStringContainsString('Welcome back', $login);
        $this->assertStringNotContainsString('<p>{props.user}</p>', $login);
    }

    public function test_customer_login_invites_email_username_or_phone(): void
    {
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertStringContainsString('name="login"', $login);
        $this->assertStringContainsString('Email, username or phone', $login);
        $this->assertStringContainsString('$errors->get(\'login\')', $login);
    }
}
