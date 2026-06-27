<?php

declare(strict_types=1);

use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PaypalGateway;

return [

    'default' => env('PAYMENTS_DEFAULT_GATEWAY', 'credit_card'),

    'idempotency_ttl_days' => env('PAYMENTS_IDEMPOTENCY_TTL_DAYS', 7),

    'abandoned_cart_ttl_days' => env('PAYMENTS_ABANDONED_CART_TTL_DAYS', 30),

    'gateways' => [

        'credit_card' => [
            'driver' => CreditCardGateway::class,
            'api_key' => env('CREDIT_CARD_API_KEY'),
            'secret' => env('CREDIT_CARD_SECRET'),
        ],

        'paypal' => [
            'driver' => PaypalGateway::class,
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
        ],

    ],

];
