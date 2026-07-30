<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateUserPlan;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPlan;
use App\Services\AffiliateCatalogGenerationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateUsersController extends Controller
{
    public function index(): View
    {
        return view('platform-admin.affiliate-users.index', [
            'affiliates' => Affiliate::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function data(Request $request, Affiliate $affiliate): JsonResponse
    {
        return $this->userData($request, $affiliate);
    }

    public function allData(Request $request): JsonResponse
    {
        $affiliate = $request->filled('affiliate_id')
            ? Affiliate::findOrFail($request->integer('affiliate_id'))
            : null;

        return $this->userData($request, $affiliate);
    }

    private function userData(Request $request, ?Affiliate $affiliate): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        $users = User::withoutGlobalScope('affiliate')
            ->select([
                'id', 'affiliate_id', 'user_plan_id', 'role_id', 'upline_id',
                'username', 'first_name', 'last_name', 'other_names', 'email',
                'phone_number', 'main_wallet', 'active', 'is_deactivated',
                'email_verified_at', 'customer_category', 'customer_landmark', 'account_tier',
                'default_wallet_setting', 'created_at',
            ])
            ->when($affiliate, fn (Builder $query) => $query->where('affiliate_id', $affiliate->id))
            ->with(['affiliate:id,name', 'role:id,role_name', 'user_plan:id,affiliate_id,user_plan_name,updated_user_plan_name,plan_level'])
            ->withCount([
                'transactions' => fn (Builder $query) => $query->withoutGlobalScope('affiliate'),
                'referrals' => fn (Builder $query) => $query->withoutGlobalScope('affiliate'),
            ])
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(25);

        $plans = AffiliateUserPlan::withoutGlobalScope('affiliate')
            ->when($affiliate, fn (Builder $query) => $query->where('affiliate_id', $affiliate->id))
            ->withCount(['users' => fn (Builder $query) => $query->withoutGlobalScope('affiliate')])
            ->orderByRaw('CAST(plan_level AS UNSIGNED)')
            ->get();

        return response()->json([
            'users' => $users,
            'plans' => $plans,
            'roles' => Role::orderBy('role_name')->get(['id', 'role_name']),
            'source_plan_count' => UserPlan::count(),
        ]);
    }

    public function generatePlans(
        Affiliate $affiliate,
        AffiliateCatalogGenerationService $generationService
    ): JsonResponse {
        $result = $generationService->generateUserPlans($affiliate);

        return response()->json([
            'message' => "{$result['created']} affiliate user plans generated; {$result['existing']} already existed.",
            ...$result,
        ]);
    }

    public function updateUser(Request $request, Affiliate $affiliate, int $user): JsonResponse
    {
        $user = User::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->findOrFail($user);

        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'other_names' => ['sometimes', 'nullable', 'string', 'max:100'],
            'username' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->where('affiliate_id', $affiliate->id)->ignore($user->id)],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users')->where('affiliate_id', $affiliate->id)->ignore($user->id)],
            'user_plan_id' => [
                'sometimes',
                'nullable',
                Rule::exists('affiliate_user_plans', 'id')->where('affiliate_id', $affiliate->id),
            ],
            'role_id' => ['sometimes', 'required', Rule::exists('roles', 'id')],
            'active' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'customer_category' => ['sometimes', 'nullable', 'string', 'max:50'],
            'customer_landmark' => ['sometimes', 'nullable', 'string', 'max:255'],
            'account_tier' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:5'],
            'default_wallet_setting' => ['sometimes', Rule::in(['main_wallet', 'bulk_data_wallet'])],
            'email_verified' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'max:255'],
            'pin' => ['sometimes', 'nullable', 'digits:6'],
        ]);

        if (array_key_exists('email_verified', $data)) {
            $user->email_verified_at = $data['email_verified'] ? ($user->email_verified_at ?: now()) : null;
            unset($data['email_verified']);
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (empty($data['pin'])) {
            unset($data['pin']);
        }

        if (array_key_exists('active', $data)) {
            $data['is_deactivated'] = $data['active'] ? 0 : 1;
        }

        $user->fill($data)->save();

        $freshUser = $user->fresh()
            ->load(['role:id,role_name', 'user_plan:id,affiliate_id,user_plan_name,updated_user_plan_name,plan_level'])
            ->makeHidden(['pin', 'bvn', 'nin', 'bvn_json', 'nin_json']);

        return response()->json([
            'message' => 'Affiliate user updated.',
            'user' => $freshUser,
        ]);
    }

    public function updatePlan(Request $request, Affiliate $affiliate, int $plan): JsonResponse
    {
        $plan = AffiliateUserPlan::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->findOrFail($plan);

        $data = $request->validate([
            'updated_user_plan_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'visibility' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'max_profit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_default' => ['sometimes', Rule::in([0, 1, '0', '1', null])],
        ]);

        if (! empty($data['is_default'])) {
            AffiliateUserPlan::withoutGlobalScope('affiliate')
                ->where('affiliate_id', $affiliate->id)
                ->whereKeyNot($plan->id)
                ->update(['is_default' => 0]);
        }

        $plan->update($data);

        return response()->json(['message' => 'Affiliate user plan updated.', 'plan' => $plan->fresh()]);
    }
}
