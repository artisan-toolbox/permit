<?php

declare(strict_types=1);

return [
    'group_description' => 'Permisos de :name.',
    'permission_description' => 'Permite :name.',
    'role_description' => 'Identifica a :name.',
    'roles' => [
        'name' => 'Roles',
        'description' => 'Roles asignados a este usuario.',
    ],
    'management' => [
        'name' => 'Permisos de usuarios',
        'description' => 'Administra los permisos de usuarios.',
        'read' => ['name' => 'Leer', 'description' => 'Consulta los permisos de usuarios.'],
        'update' => ['name' => 'Actualizar', 'description' => 'Actualiza los permisos de usuarios.'],
    ],
    'crud' => [
        'create' => ['name' => 'Crear', 'description' => 'Crea registros.'],
        'read' => ['name' => 'Leer', 'description' => 'Lee registros.'],
        'update' => ['name' => 'Actualizar', 'description' => 'Actualiza registros.'],
        'delete' => ['name' => 'Eliminar', 'description' => 'Elimina registros.'],
    ],
    'inspect' => [
        'allowed' => 'Permitido',
        'denied' => 'Denegado',
        'default_status' => 'Predeterminado · :status',
        'defaults' => 'Valores predeterminados del catálogo',
        'search' => 'Selecciona un usuario o consulta los valores predeterminados',
        'placeholder' => 'Busca por identificador o nombre',
        'subject' => 'Permisos asignados a :subject',
    ],
    'conflict' => 'Los permisos han cambiado. Vuelve a cargarlos antes de guardar.',
    'invalid_assignments' => 'Envía una revisión válida y un mapa completo de valores booleanos.',
];
