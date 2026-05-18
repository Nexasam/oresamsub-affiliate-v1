<?php
namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
            'auth' => fn () => [
                'user' => auth()->check() ? [
                    'id'         => auth()->id(),
                    'first_name' => auth()->user()->first_name,
                    'last_name'  => auth()->user()->last_name,
                    'email'      => auth()->user()->email,
                    'main_wallet'      => auth()->user()->main_wallet,
                    'username'      => auth()->user()->username,
                    'phone_number'      => auth()->user()->phone_number,
                    'is_marketer'      => auth()->user()->is_marketer,
                   
                ] : null,
              
            ],
            'flash' => fn () => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        
            'impersonator' => fn () =>
                session()->has('impersonator') ? [
                    'fname'    => auth()->user()->first_name,
                    'lname'    => auth()->user()->last_name,
                    'username'    => auth()->user()->username,
                    'pin'     => auth()->user()->pin,
                    'exitUrl' => route('admin.exit_impersonate'),
                ] : null,
        ]);
        

        /**
         * Custom Email Verification Branding
         */
       VerifyEmail::toMailUsing(function ($notifiable, string $url) {

            $affiliate = session('affiliate');
            $siteName = $affiliate?->name ?? 'Emiplug';

            return (new MailMessage)
                ->from('noreply@oresamsub.com', $siteName)
                ->subject("Verify Your Email - {$siteName}")
                ->greeting("Welcome to {$siteName}")
                ->line("Please verify your email address to continue using {$siteName}.")
                ->action('Verify Email Address', $url)
                ->salutation("Regards,\n{$siteName}");
        });


 

        // ResetPassword::toMailUsing(function ($notifiable, string $url) {

        //     logger("Url: ".$url);

        //     $affiliate = session('affiliate');
        //     $siteName = $affiliate?->name ?? 'OresamSubeeee';
        
        //     return (new MailMessage)
        //         ->subject("Reset Password - {$siteName}")
        //         ->greeting("Hello from {$siteName}")
        //         ->line("You requested a password reset.")
        //         ->action('Reset Password', $url)
        //         ->line('This password reset link will expire in 60 minutes.')
        //         ->salutation("Regards,\n{$siteName}");
        // });

       
        
        ResetPassword::toMailUsing(function ($notifiable, string $url) {

            $affiliate = session('affiliate');

            $siteName = $affiliate?->name ?? 'Emiplug';

            // Dynamically set sender name
            config([
                'mail.from.name' => $siteName,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Extract token safely from Laravel reset URL
            |--------------------------------------------------------------------------
            |
            | Example Laravel URL:
            | https://domain.com/reset-password/{token}?email=test@mail.com
            |
            */

            $path = parse_url($url, PHP_URL_PATH);

            $segments = explode('/', trim($path, '/'));

            $token = end($segments);

            /*
            |--------------------------------------------------------------------------
            | Build affiliate-specific reset URL
            |--------------------------------------------------------------------------
            */

            $baseDomain = $affiliate?->domain_url ?? config('app.url');

            $baseDomain = 'http://'.$baseDomain;

            $resetUrl = rtrim($baseDomain, '/')
                . '/reset-password/' . $token
                . '?email=' . urlencode($notifiable->email);

            return (new MailMessage)
                ->subject("Reset Password - {$siteName}")
                ->greeting("Hello from {$siteName}")
                ->line("You requested a password reset.")
                ->action('Reset Password', $resetUrl)
                ->line('This password reset link will expire in 60 minutes.')
                ->salutation("Regards,\n{$siteName}");
        });
        
        User::observe(UserObserver::class);
    }
}
