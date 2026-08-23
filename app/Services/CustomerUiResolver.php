<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\User;

class CustomerUiResolver
{
    public function resolve(?User $user, ?Affiliate $affiliate): string
    {
        if (config('customer-ui.force_v1') || ! config('customer-ui.v2_enabled')) {
            return 'v1';
        }

        $preference = $user?->customer_ui_version ?: $affiliate?->customer_ui_default;

        return $preference === 'v2' ? 'v2' : 'v1';
    }
}
