<?php

declare(strict_types=1);

return [
    'guard' => 'web',
    'table' => 'user_permissions',

    'management' => [
        'enabled' => true,
        'target_policy' => null,
    ],

    'icons' => [
        'enabled' => true,
        'family' => 'classic',
        'crud' => [
            'create' => 'plus',
            'read' => 'eye',
            'update' => 'pen',
            'delete' => 'trash',
        ],
        'user_permissions' => [
            'group' => 'user-shield',
            'read' => 'eye',
            'update' => 'pen-to-square',
        ],
    ],

    'cache' => [
        'enabled' => true,
        'store' => null,
        'ttl' => 300,
    ],
];
