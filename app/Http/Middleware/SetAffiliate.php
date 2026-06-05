<?php

namespace App\Http\Middleware;

use App\Models\AdminColorSetting;
use App\Models\Affiliate;
use App\Models\LandingPagesSetting;
use App\Models\SiteImage;
use App\Models\SiteTemplate;
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

        // Log::info('Current domain detected', [
        //     'domain' => $currentDomain
        // ]);

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING SESSION
        |--------------------------------------------------------------------------
        | If affiliate exists in session BUT domain does not match,
        | clear session and reload correct affiliate.
        */

        if (Session::has('affiliate')) {



            $sessionAffiliate = Session::get('affiliate');


            /*
            |--------------------------------------------------------------------------
            | ENSURE DEFAULT RECORDS EXIST
            |--------------------------------------------------------------------------
            */

            $this->seedAffiliateDefaults($sessionAffiliate);


            $sessionDomain = $sessionAffiliate->domain_url ?? null;

            $sessionDomain = $sessionDomain
                ? preg_replace('/^www\./', '', strtolower($sessionDomain))
                : null;

            // SESSION DOMAIN MATCHES CURRENT DOMAIN
            if ($sessionDomain === $currentDomain) {

                // Log::info('Affiliate session valid', [
                //     'session_domain' => $sessionDomain,
                //     'current_domain' => $currentDomain,
                //     'affiliate_id' => $sessionAffiliate->id ?? null,
                // ]);

                return $next($request);
            }

            // DOMAIN MISMATCH → CLEAR SESSION
            // Log::warning('Affiliate session mismatch detected. Resetting session.', [
            //     'session_domain' => $sessionDomain,
            //     'current_domain' => $currentDomain,
            // ]);

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

            $sessionAffiliate = Session::get('affiliate');


            /*
            |--------------------------------------------------------------------------
            | ENSURE DEFAULT RECORDS EXIST
            |--------------------------------------------------------------------------
            */
            $this->seedAffiliateDefaults($sessionAffiliate);


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

            // Log::info('Affiliate set successfully from domain.', [
            //     'domain' => $currentDomain,
            //     'affiliate_id' => $affiliate->id,
            //     'affiliate_slug' => $affiliate->slug,
            // ]);

        } else {

            /*
            |--------------------------------------------------------------------------
            | DOMAIN NOT FOUND
            |--------------------------------------------------------------------------
            */

            // Log::warning('Affiliate not configured for domain.', [
            //     'domain' => $currentDomain,
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Your site has not been configured yet. Please contact the system administrator.',
                'domain' => $currentDomain,
            ], 403);
        }

        return $next($request);
    }

      /**
     * Create missing affiliate defaults
     */
    protected function seedAffiliateDefaults(Affiliate $affiliate): void
    {

        if (
            LandingPagesSetting::where('affiliate_id', $affiliate->id)->count() > 0
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | LANDING PAGE SETTINGS
        |--------------------------------------------------------------------------
        */

        foreach (config('landing_pages') as $setting) {

            LandingPagesSetting::firstOrCreate(
                [
                    'affiliate_id' => $affiliate->id,
                    'field_name' => $setting[0],
                ],
                [
                    'field_details' => $setting[2],
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | COLORS
        |--------------------------------------------------------------------------
        */

        $colors = [
            'user_dashboard_primary_color' => '#5a66f2',
            'user_dashboard_secondary_color' => '#5a66f2',
            'user_dashboard_announcement_color' => '#5a66f2',

            'admin_site_color' => '90, 102, 242',
            'site_landing_analytics_color' => '90, 102, 242',
            'site_landing_review_color' => '90, 102, 242',
        ];

        foreach ($colors as $name => $value) {

            AdminColorSetting::firstOrCreate(
                [
                    'affiliate_id' => $affiliate->id,
                    'color_name' => $name,
                ],
                [
                    'color_value' => $value,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TEMPLATE
        |--------------------------------------------------------------------------
        */

        SiteTemplate::firstOrCreate(
            [
                'affiliate_id' => $affiliate->id,
            ],
            [
                'template_name' => 'template_1',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | DEFAULT IMAGES
        |--------------------------------------------------------------------------
        */

        $images = [
            'site_logo' => 'default-logo.png',
            'favicon' => 'favicon.png',
            'hero_image' => 'hero.png',
        ];

        foreach ($images as $category => $image) {

            SiteImage::firstOrCreate(
                [
                    'affiliate_id' => $affiliate->id,
                    'image_category' => $category,
                ],
                [
                    'image_name' => $image,
                ]
            );
        }
    }
}