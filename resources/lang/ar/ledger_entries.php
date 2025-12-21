<?php

return [
    'navigation' => 'القيود المحاسبية',
    'navigation_group' => 'المالية',
    'model' => [
        'singular' => 'قيد محاسبي',
        'plural' => 'القيود المحاسبية',
    ],
    'sections' => [
        'party' => 'بيانات الطرف',
        'details' => 'تفاصيل القيد',
        'description' => 'الوصف والمرفق',
        'reference' => 'المرجع',
        'metadata' => 'بيانات النظام',
    ],
    'fields' => [
        'party_type' => 'نوع الطرف',
        'party' => 'الطرف',
        'party_id' => 'معرف الطرف',
        'date' => 'التاريخ',
        'type' => 'نوع القيد',
        'amount' => 'المبلغ',
        'description' => 'الوصف',
        'category' => 'الفئة',
        'source' => 'المصدر',
        'reference_id' => 'معرف المرجع',
        'reference_type' => 'نوع المرجع',
        'payment_method' => 'طريقة الدفع',
        'attachment_url' => 'رابط المرفق',
        'status' => 'الحالة',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'deleted_at' => 'تاريخ الحذف',
        'created_by' => 'أُنشئ بواسطة',
        'updated_by' => 'عُدّل بواسطة',
    ],
    'helpers' => [
        'party_id' => 'اختر الطرف بالأعلى أو أدخل المعرّف (UUID).',
    ],
    'party_types' => [
        'employee' => 'موظف',
        'branch' => 'فرع',
        'supplier' => 'مورد',
        'customer' => 'عميل',
    ],
    'types' => [
        'debit' => 'مدين',
        'credit' => 'دائن',
    ],
    'sources' => [
        'manual' => 'يدوي',
        'advance_request' => 'طلب سلفة',
        'salary' => 'رواتب',
        'closure' => 'إقفال يوم',
        'other' => 'أخرى',
    ],
    'payment_methods' => [
        'cash' => 'نقداً',
        'bank_transfer' => 'تحويل بنكي',
        'check' => 'شيك',
        'other' => 'أخرى',
    ],
    'status' => [
        'pending' => 'معلّق',
        'confirmed' => 'مؤكد',
        'cancelled' => 'ملغي',
    ],
    'actions' => [
        'mark_confirmed' => 'تأكيد',
        'mark_pending' => 'تعليق',
        'mark_cancelled' => 'إلغاء',
    ],
];
