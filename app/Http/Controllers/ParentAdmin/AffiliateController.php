<?php

namespace App\Http\Controllers\ParentAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateCatalogGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function __construct(private readonly AffiliateCatalogGenerationService $catalog) {}

    public function index(Request $request): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;

        return view('parent-admin.affiliates.index', [
            'affiliates' => Affiliate::query()->where('parent_business_id', $parent->id)->with('parentResellerLevel:id,name')->latest()->paginate(30),
            'eligibleAffiliates' => Affiliate::query()->whereNull('parent_business_id')->orderBy('name')->get(['id', 'name', 'slug', 'contact_email']),
            'levels' => $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(['id', 'name', 'position']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        $data = $request->validate($this->rules($parent->id));

        Affiliate::create([
            ...$data,
            'parent_business_id' => $parent->id,
            'affiliate_plan_id' => 1,
            'ip_address' => 'managed-'.Str::uuid(),
            'parent_key' => Str::random(48),
            'parent_email' => "parent+{$data['slug']}@affiliate.local",
            'activation_status' => 1,
        ]);

        return to_route('parent-admin.affiliates.index')->with('success', 'Affiliate created and assigned to this parent.');
    }

    public function attach(Request $request, Affiliate $affiliate): RedirectResponse
    {
        abort_unless($affiliate->parent_business_id === null, 404);
        $parent = $request->user('parent_admin')->parentBusiness;
        $data = $request->validate(['parent_reseller_level_id' => $this->levelRule($parent->id)]);
        $affiliate->update(['parent_business_id' => $parent->id, 'parent_reseller_level_id' => $data['parent_reseller_level_id']]);

        return to_route('parent-admin.affiliates.index')->with('success', 'Affiliate attached.');
    }

    public function edit(Request $request, Affiliate $affiliate): View
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        abort_unless($affiliate->parent_business_id === $parent->id, 404);

        return view('parent-admin.affiliates.edit', [
            'affiliate' => $affiliate,
            'levels' => $parent->resellerLevels()->where('status', 'active')->orderBy('position')->get(['id', 'name', 'position']),
        ]);
    }

    public function update(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        abort_unless($affiliate->parent_business_id === $parent->id, 404);

        $affiliate->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('affiliates', 'slug')->ignore($affiliate->id)],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:50', Rule::unique('affiliates', 'contact_phone')->ignore($affiliate->id)],
            'domain_url' => ['nullable', 'string', 'max:255'],
            'parent_reseller_level_id' => $this->levelRule($parent->id),
        ]));

        return to_route('parent-admin.affiliates.index')->with('success', 'Affiliate details updated.');
    }

    public function updateLevel(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        abort_unless($affiliate->parent_business_id === $parent->id, 404);
        $data = $request->validate(['parent_reseller_level_id' => $this->levelRule($parent->id)]);
        $affiliate->update($data);

        return to_route('parent-admin.affiliates.index')->with('success', 'Affiliate reseller level updated.');
    }

    public function syncCategories(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $parent = $request->user('parent_admin')->parentBusiness;
        abort_unless($affiliate->parent_business_id === $parent->id, 404);
        $result = $this->catalog->generateCategories($affiliate);

        return to_route('parent-admin.affiliates.index')->with('success', "Categories synchronized: {$result['created']} added, {$result['existing']} preserved.");
    }

    private function rules(int $parentId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', 'unique:affiliates,slug'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:50', 'unique:affiliates,contact_phone'],
            'domain_url' => ['nullable', 'string', 'max:255'],
            'parent_reseller_level_id' => $this->levelRule($parentId),
        ];
    }

    private function levelRule(int $parentId): array
    {
        return ['required', 'integer', Rule::exists('parent_reseller_levels', 'id')->where(fn ($query) => $query->where('parent_business_id', $parentId)->where('status', 'active'))];
    }
}
