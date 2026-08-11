<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentOwnsAffiliate
{
    public function handle(Request $request, Closure $next): Response
    {
        $affiliate = $request->route('affiliate');
        abort_unless($affiliate instanceof Affiliate, 404);
        abort_unless($affiliate->parent_business_id === $request->user('parent_admin')?->parent_business_id, 404);

        return $next($request);
    }
}
