<?php

return [
    'marketing_host' => env('RESELLGRID_MARKETING_HOST', 'affiliate.emiplug.com'),

    'whatsapp' => [
        'number' => env('RESELLGRID_WHATSAPP_NUMBER'),
        'message' => env(
            'RESELLGRID_WHATSAPP_MESSAGE',
            'Hello ResellGrid, I run a VTU business and would like to learn how to launch affiliate websites under my platform.'
        ),
    ],
];
