<?php

return [
    'navigation' => 'Documents',
    'navigation_group' => 'Documents',
    'model' => [
        'singular' => 'Document',
        'plural' => 'Documents',
    ],
    'sections' => [
        'owner' => 'Owner & Details',
        'dates' => 'Dates & Notifications',
        'notes' => 'Notes',
        'metadata' => 'System Metadata',
    ],
    'fields' => [
        'owner_type' => 'Owner Type',
        'owner' => 'Owner',
        'owner_id' => 'Owner ID',
        'type' => 'Document Type',
        'number' => 'Document Number',
        'title' => 'Title',
        'issue_date' => 'Issue Date',
        'expiry_date' => 'Expiry Date',
        'status' => 'Status',
        'days_remaining' => 'Days Remaining',
        'notify_before_days' => 'Notify Before (Days)',
        'last_notified_at' => 'Last Notified At',
        'notes' => 'Notes',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'deleted_at' => 'Deleted At',
        'created_by' => 'Created By',
        'updated_by' => 'Updated By',
    ],
    'helpers' => [
        'owner_id' => 'Use a UUID when the document is company-wide.',
    ],
    'owner_types' => [
        'employee' => 'Employee',
        'branch' => 'Branch',
        'company' => 'Company',
    ],
    'status' => [
        'safe' => 'Safe',
        'near' => 'Near Expiry',
        'urgent' => 'Urgent',
        'expired' => 'Expired',
    ],
    'actions' => [
        'mark_notified' => 'Mark Notified',
    ],
];
