<?php

declare(strict_types=1);

return [
    'navigation' => [
        'shop' => 'المتجر',
        'webhooks' => 'الويب هوك',
    ],

    'resources' => [
        'order' => [
            'label' => 'طلب',
            'plural' => 'الطلبات',
        ],
        'payment' => [
            'label' => 'دفعة',
            'plural' => 'المدفوعات',
        ],
        'webhook_bounce' => [
            'label' => 'ويب هوك مرتجع',
            'plural' => 'الويب هوك المرتجعة',
        ],
        'product' => [
            'label' => 'منتج',
            'plural' => 'المنتجات',
        ],
    ],

    'fields' => [
        'id' => 'المعرف',
        'uuid' => 'المرجع',
        'customer' => 'العميل',
        'status' => 'الحالة',
        'currency' => 'العملة',
        'subtotal' => 'المجموع الفرعي',
        'tax' => 'الضريبة',
        'discount' => 'الخصم',
        'total' => 'الإجمالي',
        'gateway' => 'بوابة الدفع',
        'method' => 'طريقة الدفع',
        'amount' => 'المبلغ',
        'gateway_reference' => 'مرجع البوابة',
        'processed_at' => 'تاريخ المعالجة',
        'created_at' => 'تاريخ الإنشاء',
        'name' => 'الاسم',
        'url' => 'الرابط',
        'exception' => 'الخطأ',
        'payload' => 'البيانات',
        'items_count' => 'العناصر',
        'payments_count' => 'المدفوعات',
        'sku' => 'رمز المنتج',
        'price' => 'السعر',
        'stock' => 'المخزون',
        'is_active' => 'مُفعّل',
        'description' => 'الوصف',
    ],

    'widgets' => [
        'failed_payments' => 'المدفوعات الفاشلة',
        'webhook_bounces' => 'الويب هوك المرتجعة',
        'orders_total' => 'الطلبات',
        'revenue' => 'الإيرادات',
    ],

    'actions' => [
        'retry' => 'إعادة المحاولة',
        'retried' => 'تمت إعادة جدولة الويب هوك للمعالجة.',
        'confirm' => 'تأكيد',
        'cancel' => 'إلغاء',
        'confirmed' => 'تم تأكيد الطلب.',
        'cancelled' => 'تم إلغاء الطلب.',
        'refund' => 'استرداد',
        'refunded' => 'تم إصدار الاسترداد.',
        'edit_totals' => 'تعديل الإجماليات',
        'updated' => 'تم تحديث الطلب.',
        'amount' => 'المبلغ (اتركه فارغًا للاسترداد الكامل)',
        'reason' => 'السبب',
        'failed' => 'فشل الإجراء: :message',
    ],
];
