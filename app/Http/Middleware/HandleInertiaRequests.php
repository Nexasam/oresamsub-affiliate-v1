<?php

namespace App\Http\Middleware;

use App\Models\SiteImage;
use App\Models\Affiliate;
use App\Services\CustomerUiResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // return [
        //     ...parent::share($request),
        //     //
        // ];
        $siteLogo = SiteImage::where('image_category', 'site_logo')->first();


        $affiliate = session('affiliate');
        if (! $affiliate instanceof Affiliate && $request->user()?->affiliate_id) {
            $affiliate = Affiliate::find($request->user()->affiliate_id);
        }
        $customerUiVersion = app(CustomerUiResolver::class)->resolve($request->user(), $affiliate);

        return array_merge(parent::share($request), [
            'userDashboardPrimaryColor' => session('user_dashboard_primary_color'),
            'userDashboardSecondaryColor' => session('user_dashboard_secondary_color'),
            'userDashboardAnnouncementColor' => session('user_dashboard_announcement_color'),
            'affiliate' => session('affiliate'),
            'siteLogo' => $siteLogo?->image_name,
            'sitename' => $affiliate?->name ?? 'Oresamsub',
            'customerUi' => [
                'version' => $customerUiVersion,
                'canSwitch' => (bool) config('customer-ui.v2_enabled') && ! config('customer-ui.force_v1'),
                'updateUrl' => route('customer-ui.update'),
            ],
        ]);
    }
}
