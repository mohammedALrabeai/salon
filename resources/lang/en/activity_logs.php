<?php

return [
    'navigation' => 'Activity Logs',
    'navigation_group' => 'System',
    'model' => [
        'singular' => 'Activity Log',
        'plural' => 'Activity Logs',
    ],
    'sections' => [
        'details' => 'Details',
        'properties' => 'Properties',
    ],
    'fields' => [
        'log_name' => 'Log Name',
        'event' => 'Event',
        'description' => 'Description',
        'subject' => 'Subject',
        'subject_type' => 'Subject Type',
        'subject_id' => 'Subject ID',
        'causer' => 'Causer',
        'causer_type' => 'Causer Type',
        'causer_id' => 'Causer ID',
        'properties' => 'Properties',
        'created_at' => 'Created At',
    ],
    'events' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
    ],
];
