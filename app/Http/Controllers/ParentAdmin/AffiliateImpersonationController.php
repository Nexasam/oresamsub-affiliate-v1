<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\PlatformImpersonationToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateImpersonationController extends Controller
{
    public function store(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $this->authorizeAffiliate($request, $affiliate);

        $user = User::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Admin'))
            ->firstOrFail();

        return $this->createHandoff($request, $affiliate, $user);
    }

    public function storeUser(Request $request, Affiliate $affiliate, int $user): RedirectResponse
    {
        $this->authorizeAffiliate($request, $affiliate);

        $user = User::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->findOrFail($user);

        return $this->createHandoff($request, $affiliate, $user);
    }

    private function authorizeAffiliate(Request $request, Affiliate $affiliate): void
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        abort_unless($affiliate->parent_business_id === $parent->id, 404);
        abort_if(
            $request->session()->has('parent_impersonation') || $request->session()->has('platform_impersonation'),
            409,
            'Nested impersonation is not allowed.'
        );
        abort_unless(
            $affiliate->activation_status == 1 && $affiliate->domain_url,
            422,
            'The affiliate must be active and have a domain.'
        );
    }

    private function createHandoff(Request $request, Affiliate $affiliate, User $user): RedirectResponse
    {
        $plainToken = Str::random(64);

        PlatformImpersonationToken::create([
            'admin_id' => null,
            'parent_admin_id' => $request->user('parent_admin')->id,
            'affiliate_id' => $affiliate->id,
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'return_url' => route('parent-admin.operations.index', ['affiliate_id' => $affiliate->id]),
            'expires_at' => now()->addMinutes(2),
            'created_ip' => $request->ip(),
        ]);

        $host = rtrim(preg_replace('#^https?://#', '', trim($affiliate->domain_url)), '/');
        $scheme = parse_url(
            str_contains($affiliate->domain_url, '://') ? $affiliate->domain_url : 'https://'.$affiliate->domain_url,
            PHP_URL_SCHEME
        );

        return redirect()->away("{$scheme}://{$host}/parent-impersonation/{$plainToken}");
    }
}
