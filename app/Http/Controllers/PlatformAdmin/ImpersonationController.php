<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\PlatformImpersonationToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImpersonationController extends Controller
{
    public function create(Request $request, Affiliate $affiliate, int $user): JsonResponse
    {
        $user = User::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $affiliate->id)
            ->findOrFail($user);

        abort_unless($affiliate->activation_status == 1, 422, 'The affiliate must be active before impersonation.');
        abort_unless($affiliate->domain_url, 422, 'The affiliate has no configured domain.');

        $plainToken = Str::random(64);
        PlatformImpersonationToken::create([
            'admin_id' => $request->user('platform_admin')->id,
            'affiliate_id' => $affiliate->id,
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'return_url' => route('platform-admin.affiliate-users.index', ['affiliate_id' => $affiliate->id]),
            'expires_at' => now()->addMinutes(2),
            'created_ip' => $request->ip(),
        ]);

        $host = preg_replace('#^https?://#', '', trim($affiliate->domain_url));
        $host = rtrim($host, '/');
        $scheme = str_contains($affiliate->domain_url, '://')
            ? parse_url($affiliate->domain_url, PHP_URL_SCHEME)
            : ($request->isSecure() ? 'https' : 'http');
        if (
            ! str_contains($host, ':')
            && strtolower($host) === strtolower($request->getHost())
            && ! in_array($request->getPort(), [80, 443], true)
        ) {
            $host .= ':'.$request->getPort();
        }

        return response()->json([
            'message' => 'A single-use impersonation session is ready.',
            'url' => "{$scheme}://{$host}/platform-impersonation/{$plainToken}",
            'expires_in_seconds' => 120,
        ]);
    }

    public function consume(Request $request, string $token): RedirectResponse
    {
        $record = DB::transaction(function () use ($token) {
            $record = PlatformImpersonationToken::where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($record->used_at || $record->expires_at->isPast(), 410, 'This impersonation link has expired or was already used.');
            abort_unless((int) session('affiliate')?->id === (int) $record->affiliate_id, 403, 'The impersonation link belongs to another affiliate domain.');
            $record->update(['used_at' => now()]);

            return $record;
        });

        $user = User::withoutGlobalScope('affiliate')
            ->where('affiliate_id', $record->affiliate_id)
            ->findOrFail($record->user_id);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('platform_impersonation', [
            'admin_id' => $record->admin_id,
            'parent_admin_id' => $record->parent_admin_id,
            'affiliate_id' => $record->affiliate_id,
            'user_id' => $record->user_id,
            'return_url' => $record->return_url,
        ]);

        return redirect()->route('dashboard')->with('success', ($record->parent_admin_id ? 'Parent' : 'Platform').' administrator impersonation session started.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $impersonation = $request->session()->pull('platform_impersonation');
        abort_unless($impersonation, 404);

        Auth::guard('web')->logout();
        if ($impersonation['parent_admin_id'] ?? null) {
            $request->session()->regenerateToken();
        } else {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->away($impersonation['return_url']);
    }
}
