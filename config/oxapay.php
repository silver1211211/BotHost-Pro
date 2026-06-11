<?php

return [
    'merchant_api_key' => env('OXAPAY_MERCHANT_API_KEY'),
    'base_url' => env('OXAPAY_BASE_URL', 'https://api.oxapay.com'),
    'callback_secret' => env('OXAPAY_CALLBACK_SECRET'),
    'fee_paid_by_user' => filter_var(env('OXAPAY_FEE_PAID_BY_USER', true), FILTER_VALIDATE_BOOLEAN),
    'enabled' => filter_var(env('OXAPAY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
