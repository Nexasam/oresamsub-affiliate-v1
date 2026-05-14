<?php

namespace App\Http\Middleware;

use App\Models\AdminColorSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Affiliate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class SetAffiliatenewest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         // Get current host/domain
         $currentDomain = $request->getHost();
         $currentDomain = preg_replace('/^www\./', '', $currentDomain);
         logger('curr:' .$currentDomain);


        // If affiliate is already in session → skip DB lookup
        if (Session::has('affiliate')) {
           return $next($request);
        }

       
        logger('currentdoman:::::'.$currentDomain);
        // Try to find affiliate with this domain
        $affiliate = Affiliate::where('domain_url', $currentDomain)
            // ->orWhere('slug','like', '%'.$currentDomain.'%')
            ->first();

        if ($affiliate) {
            Session::put('affiliate', $affiliate);
            $user_dashboard_primary_color = AdminColorSetting::where('affiliate_id',$affiliate->id)->where('color_name', 'user_dashboard_primary_color')->first();
            $user_dashboard_secondary_color = AdminColorSetting::where('affiliate_id',$affiliate->id)->where('color_name', 'user_dashboard_secondary_color')->first();
            $user_dashboard_announcement_color = AdminColorSetting::where('affiliate_id',$affiliate->id)->where('color_name', 'user_dashboard_announcement_color')->first();
            // Session::put('affiliate', $affiliate);
            Session::put('user_dashboard_primary_color', $user_dashboard_primary_color->color_value ?? '#5a66f2');
            Session::put('user_dashboard_secondary_color', $user_dashboard_secondary_color->color_value ?? '#5a66f2');
            Session::put('user_dashboard_announcement_color', $user_dashboard_announcement_color->color_value ?? '#5a66f2');
            Log::info("Affiliate set from domain.", [
                'domain' => $currentDomain,
                'affiliate_id' => $affiliate->id,
                'affiliate_slug' => $affiliate->slug,
            ]);
        } else {
            // fallback → default affiliate
            $defaultAffiliate = Affiliate::with('site_colors')->where('id', 1)->first();
            $user_dashboard_primary_color = AdminColorSetting::where('affiliate_id',$defaultAffiliate->id)->where('color_name', 'user_dashboard_primary_color')->first();
            $user_dashboard_secondary_color = AdminColorSetting::where('affiliate_id',$defaultAffiliate->id)->where('color_name', 'user_dashboard_secondary_color')->first();
            $user_dashboard_announcement_color = AdminColorSetting::where('affiliate_id',$defaultAffiliate->id)->where('color_name', 'user_dashboard_announcement_color')->first();
            Session::put('affiliate', $defaultAffiliate);
            Session::put('user_dashboard_primary_color', $user_dashboard_primary_color->color_value ?? '#5a66f2');
            Session::put('user_dashboard_secondary_color', $user_dashboard_secondary_color->color_value ?? '#5a66f2');
            Session::put('user_dashboard_announcement_color', $user_dashboard_announcement_color->color_value ?? '#5a66f2');

            Log::warning("Affiliate not found for domain. Using default.", [
                'domain' => $currentDomain,
                'site_colors' => $defaultAffiliate->site_colors,
                'default_affiliate' => $defaultAffiliate ? $defaultAffiliate->slug : null,
            ]);

        }

        return $next($request);
    }
}
