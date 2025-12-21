<?php

return [
    'navigation' => 'Advance Requests',
    'navigation_group' => 'Finance',
    'model' => [
        'singular' => 'Advance Request',
        'plural' => 'Advance Requests',
    ],
    'sections' => [
        'request' => 'Request Details',
        'decision' => 'Decision',
        'payment' => 'Payment',
        'metadata' => 'System Metadata',
    ],
    'fields' => [
        'branch_id' => 'Branch',
        'employee_id' => 'Employee',
        'amount' => 'Amount',
        'reason' => 'Reason',
        'status' => 'Status',
        'requested_at' => 'Requested At',
        'processed_at' => 'Processed At',
        'processed_by' => 'Processed By',
        'decision_notes' => 'Decision Notes',
        'rejection_reason' => 'Rejection Reason',
        'payment_date' => 'Payment Date',
        'payment_method' => 'Payment Method',
        'attachment_url' => 'Attachment URL',
        'ledger_entry_id' => 'Ledger Entry',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'deleted_at' => 'Deleted At',
    ],
    'status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],
    'payment_methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'check' => 'Check',
        'deduction' => 'Salary Deduction',
    ],
    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'cancel' => 'Cancel',
    ],
];
