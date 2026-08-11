<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateOnboardingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AffiliateOnboardingController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.affiliate-onboarding.index', [
            'requests' => AffiliateOnboardingRequest::with(['parentBusiness:id,name', 'requestedBy:id,name,email', 'resellerLevel:id,name', 'affiliate:id,name,contact_email'])
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")->latest()->paginate(30),
        ]);
    }

    public function review(Request $request, AffiliateOnboardingRequest $onboarding): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'required_if:action,reject', 'string', 'min:5', 'max:1000'],
        ]);

        $onboarding = DB::transaction(function () use ($onboarding, $data, $request) {
            $onboarding = AffiliateOnboardingRequest::lockForUpdate()->findOrFail($onboarding->id);
            if ($onboarding->status !== 'pending') {
                throw ValidationException::withMessages(['action' => 'Only a pending onboarding request can be reviewed.']);
            }

            if ($data['action'] === 'approve') {
                $affiliate = $onboarding->request_type === 'attach'
                    ? Affiliate::whereNull('parent_business_id')->lockForUpdate()->findOrFail($onboarding->affiliate_id)
                    : Affiliate::create([
                        'name' => $onboarding->requested_name, 'slug' => $onboarding->requested_slug,
                        'contact_email' => $onboarding->requested_email, 'contact_phone' => $onboarding->requested_phone,
                        'domain_url' => $onboarding->requested_domain, 'affiliate_plan_id' => 1,
                        'ip_address' => 'managed-'.Str::uuid(), 'parent_key' => Str::random(48),
                        'parent_email' => "parent+{$onboarding->requested_slug}@affiliate.local", 'activation_status' => 1,
                    ]);
                $affiliate->update(['parent_business_id' => $onboarding->parent_business_id, 'parent_reseller_level_id' => $onboarding->parent_reseller_level_id]);
                $onboarding->update(['affiliate_id' => $affiliate->id, 'status' => 'approved', 'reviewed_by_admin_id' => $request->user('platform_admin')->id, 'reviewed_at' => now(), 'rejection_reason' => null]);
            } else {
                $onboarding->update(['status' => 'rejected', 'reviewed_by_admin_id' => $request->user('platform_admin')->id, 'reviewed_at' => now(), 'rejection_reason' => $data['reason']]);
            }

            return $onboarding->fresh();
        });

        if (! $request->expectsJson()) {
            return back()->with('success', "Onboarding request {$onboarding->status}.");
        }

        return response()->json(['message' => "Onboarding request {$onboarding->status}.", 'request' => $onboarding]);
    }
}
