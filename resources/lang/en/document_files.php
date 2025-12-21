<?php

return [
    'relation_title' => 'Files',
    'sections' => [
        'file' => 'File Details',
        'metadata' => 'Upload Metadata',
    ],
    'fields' => [
        'name' => 'File Name',
        'size' => 'Size',
        'mime_type' => 'MIME Type',
        'file_url' => 'File URL',
        'storage_provider' => 'Storage Provider',
        'version' => 'Version',
        'is_current' => 'Current',
        'uploaded_at' => 'Uploaded At',
        'uploaded_by' => 'Uploaded By',
    ],
    'helpers' => [
        'bytes' => 'bytes',
    ],
    'storage_providers' => [
        'local' => 'Local',
        's3' => 'Amazon S3',
        'cloudinary' => 'Cloudinary',
        'supabase' => 'Supabase',
    ],
    'actions' => [
        'set_current' => 'Set Current',
        'open' => 'Open File',
    ],
];
