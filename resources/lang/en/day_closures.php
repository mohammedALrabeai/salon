<?php

return [
    'navigation' => 'Day Closures',
    'navigation_group' => 'Operations',
    'model' => [
        'singular' => 'Day Closure',
        'plural' => 'Day Closures',
    ],
    'sections' => [
        'summary' => 'Closure Summary',
        'totals' => 'Totals',
        'report' => 'Report',
        'metadata' => 'System Metadata',
    ],
    'fields' => [
        'branch_id' => 'Branch',
        'date' => 'Date',
        'total_sales' => 'Total Sales',
        'total_cash' => 'Total Cash',
        'total_expense' => 'Total Expenses',
        'total_net' => 'Total Net',
        'total_commission' => 'Total Commission',
        'total_bonus' => 'Total Bonus',
        'entries_count' => 'Entries Count',
        'employees_count' => 'Employees Count',
        'closed_by' => 'Closed By',
        'closed_at' => 'Closed At',
        'pdf_url' => 'PDF URL',
        'pdf_generated_at' => 'PDF Generated At',
        'notes' => 'Notes',
        'created_at' => 'Created At',
        'computed_net' => 'Computed Net',
    ],
    'actions' => [
        'sync_totals' => 'Sync Totals',
        'lock_entries' => 'Lock Daily Entries',
        'unlock_entries' => 'Unlock Daily Entries',
    ],
];
