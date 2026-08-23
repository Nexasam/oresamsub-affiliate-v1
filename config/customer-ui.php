<?php

return [
    'v2_enabled' => (bool) env('CUSTOMER_UI_V2_ENABLED', false),
    'force_v1' => (bool) env('CUSTOMER_UI_FORCE_V1', false),
];
