<?php

return [
    'navigation' => 'Ledger Entries',
    'navigation_group' => 'Finance',
    'model' => [
        'singular' => 'Ledger Entry',
        'plural' => 'Ledger Entries',
    ],
    'sections' => [
        'party' => 'Party Details',
        'details' => 'Entry Details',
        'description' => 'Description & Attachment',
        'reference' => 'Reference',
        'metadata' => 'System Metadata',
    ],
    'fields' => [
        'party_type' => 'Party Type',
        'party' => 'Party',
        'party_id' => 'Party ID',
        'date' => 'Date',
        'type' => 'Entry Type',
        'amount' => 'Amount',
        'description' => 'Description',
        'category' => 'Category',
        'source' => 'Source',
        'reference_id' => 'Reference ID',
        'reference_type' => 'Reference Type',
        'payment_method' => 'Payment Method',
        'attachment_url' => 'Attachment URL',
        'status' => 'Status',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'deleted_at' => 'Deleted At',
        'created_by' => 'Created By',
        'updated_by' => 'Updated By',
    ],
    'helpers' => [
        'party_id' => 'Select a party above or paste the UUID.',
    ],
    'party_types' => [
        'employee' => 'Employee',
        'branch' => 'Branch',
        'supplier' => 'Supplier',
        'customer' => 'Customer',
    ],
    'types' => [
        'debit' => 'Debit',
        'credit' => 'Credit',
    ],
    'sources' => [
        'manual' => 'Manual',
        'advance_request' => 'Advance Request',
        'salary' => 'Salary',
        'closure' => 'Day Closure',
        'other' => 'Other',
    ],
    'payment_methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'check' => 'Check',
        'other' => 'Other',
    ],
    'status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
    ],
    'actions' => [
        'mark_confirmed' => 'Mark Confirmed',
        'mark_pending' => 'Mark Pending',
        'mark_cancelled' => 'Mark Cancelled',
    ],
];
