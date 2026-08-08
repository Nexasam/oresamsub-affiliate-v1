<?php

use App\Http\Middleware\AuthenticateExternalIntegration;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\MarketerMiddleware;
use App\Http\Middleware\RoleAdminAccess;
use App\Http\Middleware\RoleAssess;
use App\Http\Middleware\RoleUserAccess;
use App\Http\Middleware\SetAffiliate;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTransactionPin;
use App\Http\Middleware\TemplateSetting;
use App\Http\Middleware\ValidateApiToken;
use App\Http\Middleware\ValidateSanctumUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function ($request) {
            $routeName = (string) $request->route()?->getName();

            return match (true) {
                str_starts_with($routeName, 'platform-admin.') => route('platform-admin.login'),
                str_starts_with($routeName, 'parent-admin.') => route('parent-admin.login'),
                default => route('login'),
            };
        });
        $middleware->redirectUsersTo(function ($request) {
            $routeName = (string) $request->route()?->getName();

            return match (true) {
                str_starts_with($routeName, 'platform-admin.') => route('platform-admin.dashboard'),
                str_starts_with($routeName, 'parent-admin.') => route('parent-admin.dashboard'),
                default => route('dashboard'),
            };
        });

        // $middleware->append(RoleAssess::class);
        $middleware->alias([
            'template_setting' => TemplateSetting::class,
            'admin' => RoleAdminAccess::class,
            'user' => RoleUserAccess::class,
            'marketer' => MarketerMiddleware::class,
            'validate_user' => ValidateSanctumUser::class,
            'api_token' => ValidateApiToken::class,
            'set_transaction_pin' => SetTransactionPin::class,
            'set_locale' => SetLocale::class,
            'set_affiliate' => SetAffiliate::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // 'api_access' => AuthenticateExternalIntegration::class
        // $middleware->alias(['user' => RoleUserAccess::class]);
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(
            // Specify the routes to exclude from CSRF protection
            except: ['register']
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
