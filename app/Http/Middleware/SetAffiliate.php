<?php

namespace App\Http\Middleware;

use App\Models\AdminColorSetting;
use App\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetAffiliate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // CURRENT DOMAIN
        $currentDomain = $request->getHost();
        $currentDomain = preg_replace('/^www\./', '', strtolower($currentDomain));

        Log::info('Current domain detected', [
            'domain' => $currentDomain
        ]);

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING SESSION
        |--------------------------------------------------------------------------
        | If affiliate exists in session BUT domain does not match,
        | clear session and reload correct affiliate.
        */

        if (Session::has('affiliate')) {

            $sessionAffiliate = Session::get('affiliate');

            $sessionDomain = $sessionAffiliate->domain_url ?? null;

            $sessionDomain = $sessionDomain
                ? preg_replace('/^www\./', '', strtolower($sessionDomain))
                : null;

            // SESSION DOMAIN MATCHES CURRENT DOMAIN
            if ($sessionDomain === $currentDomain) {

                Log::info('Affiliate session valid', [
                    'session_domain' => $sessionDomain,
                    'current_domain' => $currentDomain,
                    'affiliate_id' => $sessionAffiliate->id ?? null,
                ]);

                return $next($request);
            }

            // DOMAIN MISMATCH → CLEAR SESSION
            Log::warning('Affiliate session mismatch detected. Resetting session.', [
                'session_domain' => $sessionDomain,
                'current_domain' => $currentDomain,
            ]);

            Session::forget([
                'affiliate',
                'user_dashboard_primary_color',
                'user_dashboard_secondary_color',
                'user_dashboard_announcement_color',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | FIND AFFILIATE FROM DOMAIN
        |--------------------------------------------------------------------------
        */

        $affiliate = Affiliate::whereRaw('LOWER(domain_url) = ?', [$currentDomain])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | AFFILIATE FOUND
        |--------------------------------------------------------------------------
        */

        if ($affiliate) {

            Session::put('affiliate', $affiliate);

            $primaryColor = AdminColorSetting::where(
                'affiliate_id',
                $affiliate->id
            )->where(
                'color_name',
                'user_dashboard_primary_color'
            )->value('color_value');

            $secondaryColor = AdminColorSetting::where(
                'affiliate_id',
                $affiliate->id
            )->where(
                'color_name',
                'user_dashboard_secondary_color'
            )->value('color_value');

            $announcementColor = AdminColorSetting::where(
                'affiliate_id',
                $affiliate->id
            )->where(
                'color_name',
                'user_dashboard_announcement_color'
            )->value('color_value');

            Session::put(
                'user_dashboard_primary_color',
                $primaryColor ?? '#5a66f2'
            );

            Session::put(
                'user_dashboard_secondary_color',
                $secondaryColor ?? '#5a66f2'
            );

            Session::put(
                'user_dashboard_announcement_color',
                $announcementColor ?? '#5a66f2'
            );

            Log::info('Affiliate set successfully from domain.', [
                'domain' => $currentDomain,
                'affiliate_id' => $affiliate->id,
                'affiliate_slug' => $affiliate->slug,
            ]);

        } else {

            /*
            |--------------------------------------------------------------------------
            | DOMAIN NOT FOUND
            |--------------------------------------------------------------------------
            */

            Log::warning('Affiliate not configured for domain.', [
                'domain' => $currentDomain,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Your site has not been configured yet. Please contact the system administrator.',
                'domain' => $currentDomain,
            ], 403);
        }

        return $next($request);
    }
}