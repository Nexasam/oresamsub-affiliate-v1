<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminWebhookString;
use App\Models\Affiliate;
use App\Models\AffiliateFundingOption;
use App\Models\AffiliateFundingOptionBankCodes;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Wallet\ManualWalletCreditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $affiliates = Affiliate::query()
            ->withCount([
                'users' => fn ($query) => $query->withoutGlobalScope('affiliate'),
                'transactions' => fn ($query) => $query->withoutGlobalScope('affiliate'),
            ])
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('platform-admin.affiliates.index', compact('affiliates', 'search'));
    }

    public function show(Affiliate $affiliate): View
    {
        $affiliate->loadCount([
            'users' => fn ($query) => $query->withoutGlobalScope('affiliate'),
            'transactions' => fn ($query) => $query->withoutGlobalScope('affiliate'),
        ]);

        return view('platform-admin.affiliates.show', compact('affiliate'));
    }

    public function update(Request $request, Affiliate $affiliate): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'required', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('affiliates')->ignore($affiliate)],
            'activation_status' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'domain_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'website_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'parent_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'parent_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'affiliate_plan_id' => ['sometimes', 'integer'],
        ]);

        $affiliate->update($data);

        return response()->json(['message' => 'Affiliate updated.', 'affiliate' => $affiliate->fresh()]);
    }

    public function users(Request $request, Affiliate $affiliate): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        $users = User::withoutGlobalScope('affiliate')
            ->with('role:id,role_name')
            ->where('affiliate_id', $affiliate->id)
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(15);

        return response()->json($users);
    }

    public function storeUser(Request $request, Affiliate $affiliate): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->where('affiliate_id', $affiliate->id)],
            'phone_number' => ['nullable', 'string', 'max:30', Rule::unique('users')->where('affiliate_id', $affiliate->id)],
            'other_names' => ['nullable', 'string', 'max:100'],
            'pin' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::exists('roles', 'role_name')],
            'user_plan_id' => [
                'nullable',
                Rule::exists('affiliate_user_plans', 'id')->where('affiliate_id', $affiliate->id),
            ],
            'customer_category' => ['nullable', 'string', 'max:50'],
            'customer_landmark' => ['nullable', 'string', 'max:255'],
            'account_tier' => ['nullable', 'integer', 'min:0', 'max:5'],
            'default_wallet_setting' => ['required', Rule::in(['main_wallet', 'bulk_data_wallet'])],
            'active' => ['required', Rule::in([0, 1, '0', '1'])],
            'email_verified' => ['required', 'boolean'],
        ]);

        $roleId = Role::where('role_name', $data['role'])->value('id');
        abort_unless($roleId, 422, 'The selected role is not configured.');

        $user = User::withoutGlobalScope('affiliate')->create([
            ...collect($data)->except(['role', 'password_confirmation', 'email_verified'])->all(),
            'affiliate_id' => $affiliate->id,
            'role_id' => $roleId,
            'password' => Hash::make($data['password']),
            'is_deactivated' => $data['active'] ? 0 : 1,
            'email_verified_at' => $data['email_verified'] ? now() : null,
        ]);

        return response()->json(['message' => 'User created.', 'user' => $user->load('role:id,role_name')], 201);
    }

    public function updateUser(Request $request, Affiliate $affiliate, int $user): JsonResponse
    {
        $user = User::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->findOrFail($user);

        $data = $request->validate([
            'active' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'role' => ['sometimes', Rule::in(['User', 'Admin'])],
        ]);

        if (array_key_exists('role', $data)) {
            $roleId = Role::where('role_name', $data['role'])->value('id');
            abort_unless($roleId, 422, 'The selected role is not configured.');
            $user->role_id = $roleId;
        }

        if (array_key_exists('active', $data)) {
            $user->active = $data['active'];
            $user->is_deactivated = $data['active'] ? 0 : 1;
        }

        $user->save();

        return response()->json(['message' => 'User updated.', 'user' => $user->load('role:id,role_name')]);
    }

    public function showUser(Affiliate $affiliate, int $user): JsonResponse
    {
        $user = User::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->with(['role:id,role_name', 'user_plan', 'upline:id,username,email'])
            ->withCount([
                'transactions' => fn ($query) => $query->withoutGlobalScope('affiliate'),
                'referrals' => fn ($query) => $query->withoutGlobalScope('affiliate'),
            ])
            ->findOrFail($user);

        $user->setRelation('virtual_accounts', $user->virtual_accounts()->withoutGlobalScope('affiliate')->get());
        $user->setRelation('transactions', $user->transactions()->withoutGlobalScope('affiliate')->latest()->limit(10)->get());

        return response()->json($user);
    }

    public function creditUser(Request $request, Affiliate $affiliate, int $user, ManualWalletCreditService $service): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $user = User::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->findOrFail($user);
        $user = $service->credit($user, (float) $data['amount'], $request->user('platform_admin'), $data['reason']);

        return response()->json(['message' => 'Wallet credited successfully.', 'user' => $user]);
    }

    public function transactions(Request $request, Affiliate $affiliate): JsonResponse
    {
        $transactions = Transaction::withoutGlobalScope('affiliate')
            ->with('user:id,first_name,last_name,email')
            ->where('affiliate_id', $affiliate->id)
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->query('status')))
            ->latest()
            ->paginate(15);

        return response()->json($transactions);
    }

    public function bankCodes(Affiliate $affiliate): JsonResponse
    {
        return response()->json(
            AffiliateFundingOptionBankCodes::withoutGlobalScope('affiliate')
                ->where('affiliate_id', $affiliate->id)
                ->latest()
                ->get()
        );
    }

    public function updateBankCode(Request $request, Affiliate $affiliate, int $bankCode): JsonResponse
    {
        $bankCode = AffiliateFundingOptionBankCodes::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->findOrFail($bankCode);

        $data = $request->validate([
            'bank_code' => ['required', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'visibility_status' => ['required', Rule::in([0, 1, '0', '1'])],
            'rate_category' => ['sometimes', Rule::in(['Flat', 'Percentage'])],
            'bank_charges' => ['nullable', 'numeric', 'min:0'],
            'capped_at' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bankCode->update($data);

        return response()->json(['message' => 'Bank code updated.', 'bank_code' => $bankCode]);
    }

    public function storeBankCode(Request $request, Affiliate $affiliate): JsonResponse
    {
        $data = $request->validate([
            'funding_option_id' => ['required', 'integer'],
            'bank_code' => ['required', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'visibility_status' => ['required', Rule::in([0, 1, '0', '1'])],
            'rate_category' => ['required', Rule::in(['Flat', 'Percentage'])],
            'bank_charges' => ['nullable', 'numeric', 'min:0'],
            'capped_at' => ['nullable', 'numeric', 'min:0'],
        ]);

        AffiliateFundingOption::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->findOrFail($data['funding_option_id']);
        $code = AffiliateFundingOptionBankCodes::withoutGlobalScope('affiliate')->create([...$data, 'affiliate_id' => $affiliate->id]);

        return response()->json(['message' => 'Bank code added.', 'bank_code' => $code], 201);
    }

    public function fundingOptions(Affiliate $affiliate): JsonResponse
    {
        $options = AffiliateFundingOption::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->with(['bank_codes' => fn ($q) => $q->withoutGlobalScope('affiliate'), 'webhook_string' => fn ($q) => $q->withoutGlobalScope('affiliate')])
            ->get()
            ->map(function ($option) use ($affiliate) {
                $option->api_public_key_masked = $this->maskSecret($option->api_public_key);
                $option->api_secret_key_masked = $this->maskSecret($option->api_secret_key);
                $option->setHidden(['api_public_key', 'api_secret_key']);
                $suffix = $option->webhook_string?->webhook_suffix_string;
                $host = parse_url(
                    str_contains((string) ($affiliate->domain_url ?: $affiliate->website_url), '://')
                        ? ($affiliate->domain_url ?: $affiliate->website_url)
                        : 'https://'.($affiliate->domain_url ?: $affiliate->website_url),
                    PHP_URL_HOST
                );
                $option->webhook_url = $suffix && $host
                    ? 'https://'.$host.'/api/admin/wallets/securewaveng_webhook/'.$suffix
                    : null;

                return $option;
            });

        return response()->json($options);
    }

    public function updateFundingOption(Request $request, Affiliate $affiliate, int $fundingOption): JsonResponse
    {
        $option = AffiliateFundingOption::withoutGlobalScope('affiliate')->where('affiliate_id', $affiliate->id)->findOrFail($fundingOption);
        $data = $request->validate([
            'activation_status' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'is_current_option' => ['sometimes', Rule::in([0, 1, '0', '1'])],
            'api_public_key' => ['nullable', 'string', 'max:2000'],
            'api_secret_key' => ['nullable', 'string', 'max:2000'],
            'contract_code' => ['nullable', 'string', 'max:255'],
            'biz_bvn' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_charges' => ['nullable', 'numeric', 'min:0'],
            'webhook_suffix_string' => ['nullable', 'alpha_dash', 'max:150'],
        ]);

        $suffix = $data['webhook_suffix_string'] ?? null;
        unset($data['webhook_suffix_string']);
        foreach (['api_public_key', 'api_secret_key'] as $key) {
            if (empty($data[$key])) {
                unset($data[$key]);
            }
        }
        $option->update($data);
        if ($suffix) {
            AdminWebhookString::withoutGlobalScope('affiliate')->updateOrCreate(
                ['affiliate_id' => $affiliate->id, 'funding_option_id' => $option->id],
                ['webhook_suffix_string' => $suffix]
            );
        }

        return response()->json(['message' => 'Funding option updated.']);
    }

    private function maskSecret(?string $secret): ?string
    {
        return $secret ? str_repeat('•', max(8, min(20, strlen($secret) - 4))).substr($secret, -4) : null;
    }
}
