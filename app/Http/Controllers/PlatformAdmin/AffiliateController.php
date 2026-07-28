<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateFundingOptionBankCodes;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
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
            ->paginate(5)
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
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['User', 'Admin'])],
        ]);

        $roleId = Role::where('role_name', $data['role'])->value('id');
        abort_unless($roleId, 422, 'The selected role is not configured.');

        $user = User::withoutGlobalScope('affiliate')->create([
            ...collect($data)->except(['role'])->all(),
            'affiliate_id' => $affiliate->id,
            'role_id' => $roleId,
            'password' => Hash::make($data['password']),
            'active' => 1,
            'email_verified_at' => now(),
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
        ]);

        $bankCode->update($data);

        return response()->json(['message' => 'Bank code updated.', 'bank_code' => $bankCode]);
    }
}
