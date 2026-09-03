<?php

return [
    'default_provider' => env('PAYMENT_PROVIDER', 'sandbox'),

    'sandbox_enabled' => env('PAYMENTS_SANDBOX_ENABLED', env('APP_ENV') !== 'production'),
];
