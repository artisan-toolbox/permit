<?php

declare(strict_types=1);

return [
    'group_description' => 'Permissões de :name.',
    'permission_description' => 'Permite :name.',
    'role_description' => 'Identifica :name.',
    'roles' => [
        'name' => 'Papéis',
        'description' => 'Papéis atribuídos a este usuário.',
    ],
    'management' => [
        'name' => 'Permissões de usuários',
        'description' => 'Gerencie as permissões de usuários.',
        'read' => ['name' => 'Ler', 'description' => 'Consulte as permissões de usuários.'],
        'update' => ['name' => 'Atualizar', 'description' => 'Atualize as permissões de usuários.'],
    ],
    'crud' => [
        'create' => ['name' => 'Criar', 'description' => 'Crie registros.'],
        'read' => ['name' => 'Ler', 'description' => 'Leia registros.'],
        'update' => ['name' => 'Atualizar', 'description' => 'Atualize registros.'],
        'delete' => ['name' => 'Excluir', 'description' => 'Exclua registros.'],
    ],
    'inspect' => [
        'allowed' => 'Permitido',
        'denied' => 'Negado',
        'default_status' => 'Padrão · :status',
        'defaults' => 'Padrões do catálogo',
        'search' => 'Selecione um usuário ou consulte os padrões do catálogo',
        'placeholder' => 'Busque pelo ID ou nome do usuário',
        'subject' => 'Permissões atribuídas a :subject',
    ],
    'conflict' => 'As permissões mudaram. Recarregue os dados antes de salvar novamente.',
    'invalid_assignments' => 'Envie uma revisão válida e um mapa completo com valores booleanos.',
];
