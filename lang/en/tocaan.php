<?php

declare(strict_types=1);

return [
    'navigation' => [
        'shop' => 'Shop',
        'webhooks' => 'Webhooks',
    ],

    'resources' => [
        'order' => [
            'label' => 'Order',
            'plural' => 'Orders',
        ],
        'payment' => [
            'label' => 'Payment',
            'plural' => 'Payments',
        ],
        'webhook_bounce' => [
            'label' => 'Webhook Bounce',
            'plural' => 'Webhook Bounces',
        ],
        'product' => [
            'label' => 'Product',
            'plural' => 'Products',
        ],
    ],

    'fields' => [
        'id' => 'ID',
        'uuid' => 'Reference',
        'customer' => 'Customer',
        'status' => 'Status',
        'currency' => 'Currency',
        'subtotal' => 'Subtotal',
        'tax' => 'Tax',
        'discount' => 'Discount',
        'total' => 'Total',
        'gateway' => 'Gateway',
        'method' => 'Method',
        'amount' => 'Amount',
        'gateway_reference' => 'Gateway Reference',
        'processed_at' => 'Processed At',
        'created_at' => 'Created At',
        'name' => 'Name',
        'url' => 'URL',
        'exception' => 'Exception',
        'payload' => 'Payload',
        'items_count' => 'Items',
        'payments_count' => 'Payments',
        'sku' => 'SKU',
        'price' => 'Price',
        'stock' => 'Stock',
        'is_active' => 'Active',
        'description' => 'Description',
    ],

    'widgets' => [
        'failed_payments' => 'Failed Payments',
        'webhook_bounces' => 'Webhook Bounces',
        'orders_total' => 'Orders',
        'revenue' => 'Revenue',
    ],

    'actions' => [
        'retry' => 'Retry',
        'retried' => 'Webhook re-queued for processing.',
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
        'confirmed' => 'Order confirmed.',
        'cancelled' => 'Order cancelled.',
        'refund' => 'Refund',
        'refunded' => 'Refund issued.',
        'edit_totals' => 'Edit totals',
        'updated' => 'Order updated.',
        'amount' => 'Amount (leave empty for full refund)',
        'reason' => 'Reason',
        'failed' => 'Action failed: :message',
    ],
];
