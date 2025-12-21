<?php

return [
    'navigation' => 'الوثائق',
    'navigation_group' => 'الوثائق',
    'model' => [
        'singular' => 'وثيقة',
        'plural' => 'الوثائق',
    ],
    'sections' => [
        'owner' => 'المالك والتفاصيل',
        'dates' => 'التواريخ والتنبيهات',
        'notes' => 'ملاحظات',
        'metadata' => 'بيانات النظام',
    ],
    'fields' => [
        'owner_type' => 'نوع المالك',
        'owner' => 'المالك',
        'owner_id' => 'معرف المالك',
        'type' => 'نوع الوثيقة',
        'number' => 'رقم الوثيقة',
        'title' => 'العنوان',
        'issue_date' => 'تاريخ الإصدار',
        'expiry_date' => 'تاريخ الانتهاء',
        'status' => 'الحالة',
        'days_remaining' => 'الأيام المتبقية',
        'notify_before_days' => 'التنبيه قبل (أيام)',
        'last_notified_at' => 'آخر إشعار',
        'notes' => 'ملاحظات',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'deleted_at' => 'تاريخ الحذف',
        'created_by' => 'أُنشئ بواسطة',
        'updated_by' => 'عُدّل بواسطة',
    ],
    'helpers' => [
        'owner_id' => 'استخدم UUID عندما تكون الوثيقة على مستوى الشركة.',
    ],
    'owner_types' => [
        'employee' => 'موظف',
        'branch' => 'فرع',
        'company' => 'شركة',
    ],
    'status' => [
        'safe' => 'سليم',
        'near' => 'قريب الانتهاء',
        'urgent' => 'عاجل',
        'expired' => 'منتهي',
    ],
    'actions' => [
        'mark_notified' => 'تسجيل إشعار',
    ],
];
