<?php

return [
    'navigation' => 'طلبات السلفة',
    'navigation_group' => 'المالية',
    'model' => [
        'singular' => 'طلب سلفة',
        'plural' => 'طلبات السلفة',
    ],
    'sections' => [
        'request' => 'بيانات الطلب',
        'decision' => 'القرار',
        'payment' => 'الصرف',
        'metadata' => 'بيانات النظام',
    ],
    'fields' => [
        'branch_id' => 'الفرع',
        'employee_id' => 'الموظف',
        'amount' => 'المبلغ',
        'reason' => 'سبب الطلب',
        'status' => 'الحالة',
        'requested_at' => 'تاريخ الطلب',
        'processed_at' => 'تاريخ المعالجة',
        'processed_by' => 'تمت المعالجة بواسطة',
        'decision_notes' => 'ملاحظات القرار',
        'rejection_reason' => 'سبب الرفض',
        'payment_date' => 'تاريخ الصرف',
        'payment_method' => 'طريقة الصرف',
        'attachment_url' => 'رابط المرفق',
        'ledger_entry_id' => 'القيد المحاسبي',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'deleted_at' => 'تاريخ الحذف',
    ],
    'status' => [
        'pending' => 'معلّق',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغي',
    ],
    'payment_methods' => [
        'cash' => 'نقداً',
        'bank_transfer' => 'تحويل بنكي',
        'check' => 'شيك',
        'deduction' => 'خصم من الراتب',
    ],
    'actions' => [
        'approve' => 'اعتماد',
        'reject' => 'رفض',
        'cancel' => 'إلغاء',
    ],
];
