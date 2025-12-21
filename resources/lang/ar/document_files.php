<?php

return [
    'relation_title' => 'الملفات',
    'sections' => [
        'file' => 'تفاصيل الملف',
        'metadata' => 'بيانات الرفع',
    ],
    'fields' => [
        'name' => 'اسم الملف',
        'size' => 'الحجم',
        'mime_type' => 'نوع الملف',
        'file_url' => 'رابط الملف',
        'storage_provider' => 'مزود التخزين',
        'version' => 'الإصدار',
        'is_current' => 'حالي',
        'uploaded_at' => 'تاريخ الرفع',
        'uploaded_by' => 'رُفع بواسطة',
    ],
    'helpers' => [
        'bytes' => 'بايت',
    ],
    'storage_providers' => [
        'local' => 'محلي',
        's3' => 'أمازون S3',
        'cloudinary' => 'كلاودينري',
        'supabase' => 'سوبابيس',
    ],
    'actions' => [
        'set_current' => 'تعيين كحالي',
        'open' => 'فتح الملف',
    ],
];
