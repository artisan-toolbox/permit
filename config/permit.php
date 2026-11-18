<?php

declare(strict_types=1);

return [
    'guard' => 'web',
    'table' => 'user_permissions',

    'management' => [
        'enabled' => true,
        'target_policy' => null,
    ],

    'cache' => [
        'enabled' => true,
        'store' => null,
        'ttl' => 300,
    ],
];
