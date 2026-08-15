<?php

return [
    'features' => [
        'ownership_reads' => env('PARENT_OWNERSHIP_READS', false),
        'normalized_pricing' => env('PARENT_NORMALIZED_PRICING', false),
        'provider_routing' => env('PARENT_PROVIDER_ROUTING', false),
        'parent_managed_purchases' => env('PARENT_MANAGED_PURCHASES_ENABLED', false),
        'multi_parent_funding' => env('MULTI_PARENT_FUNDING_ENABLED', false),
        'affiliate_blade_ui' => env('AFFILIATE_BLADE_UI_ENABLED', true),
    ],
    'oresamsub' => [
        'name' => 'OresamSub',
        'slug' => 'oresamsub',
        'provider' => [
            'name' => 'OresamSub',
            'slug' => 'oresamsub',
            'adapter' => 'oresamsub_legacy',
            'base_url' => env('ORESAMSUB_PROVIDER_BASE_URL', 'https://oresamsub.com/api/v1'),
        ],
        'admin' => [
            'name' => env('ORESAMSUB_PARENT_ADMIN_NAME', 'OresamSub Parent Admin'),
            'email' => env('ORESAMSUB_PARENT_ADMIN_EMAIL', 'parent-admin@oresamsub.local'),
            'password' => env('ORESAMSUB_PARENT_ADMIN_PASSWORD'),
        ],
    ],
    'reset_test_affiliate' => [
        'parent_admin_password' => env('TEST_PARENT_ADMIN_PASSWORD'),
    ],
];
