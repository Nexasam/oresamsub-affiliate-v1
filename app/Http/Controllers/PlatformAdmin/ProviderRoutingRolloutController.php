<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\ParentBusiness;
use App\Models\ProviderRoutingRollout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProviderRoutingRolloutController extends Controller
{
    private const SERVICES = ['data', 'airtime', 'cable_subscription', 'utility_bills'];

    public function index(): View
    {
        return view('platform-admin.provider-routing-rollouts.index', [
            'parents' => ParentBusiness::query()->with(['affiliates:id,parent_business_id,name'])->orderBy('name')->get(),
            'rollouts' => ProviderRoutingRollout::query()->get()->keyBy(fn ($rule) => "{$rule->scope_type}:{$rule->scope_id}:{$rule->service}"),
            'services' => self::SERVICES,
            'environmentEnabled' => (bool) config('parent_businesses.features.provider_routing'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'parent_business_id' => ['required', 'exists:parent_businesses,id'],
            'scope_type' => ['required', Rule::in(['parent', 'affiliate'])],
            'scope_id' => ['required', 'integer'],
            'service' => ['required', Rule::in(self::SERVICES)],
            'enabled' => ['required', 'boolean'],
        ]);

        if ($data['scope_type'] === 'parent' && (int) $data['scope_id'] !== (int) $data['parent_business_id']) {
            abort(422, 'The parent rollout scope is invalid.');
        }
        if ($data['scope_type'] === 'affiliate' && ! Affiliate::query()->whereKey($data['scope_id'])->where('parent_business_id', $data['parent_business_id'])->exists()) {
            abort(422, 'The affiliate does not belong to this parent.');
        }

        ProviderRoutingRollout::updateOrCreate(
            ['scope_type' => $data['scope_type'], 'scope_id' => $data['scope_id'], 'service' => $data['service']],
            ['parent_business_id' => $data['parent_business_id'], 'enabled' => $data['enabled']],
        );

        return back()->with('success', 'Provider routing rollout updated.');
    }
}
