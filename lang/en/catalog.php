<?php

declare(strict_types=1);

return [
    'group_description' => 'Permissions for :name.',
    'permission_description' => 'Allows :name.',
    'role_description' => 'Identifies :name.',
    'roles' => [
        'name' => 'Roles',
        'description' => 'Roles assigned to this user.',
    ],
    'management' => [
        'name' => 'User permissions',
        'description' => 'Manage user permissions.',
        'read' => ['name' => 'Read', 'description' => 'Read user permissions.'],
        'update' => ['name' => 'Update', 'description' => 'Update user permissions.'],
    ],
    'crud' => [
        'create' => ['name' => 'Create', 'description' => 'Create records.'],
        'read' => ['name' => 'Read', 'description' => 'Read records.'],
        'update' => ['name' => 'Update', 'description' => 'Update records.'],
        'delete' => ['name' => 'Delete', 'description' => 'Delete records.'],
    ],
    'inspect' => [
        'allowed' => 'Allowed',
        'denied' => 'Denied',
        'default_status' => 'Default · :status',
        'defaults' => 'Catalog defaults',
        'search' => 'Select a user or view catalog defaults',
        'placeholder' => 'Search by user ID or name',
        'subject' => 'Permission assignments for :subject',
    ],
    'conflict' => 'The assignments have changed. Reload them before saving again.',
    'invalid_assignments' => 'Send a valid revision and a complete map of boolean assignments.',
];
