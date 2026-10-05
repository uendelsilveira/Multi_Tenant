<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Catálogo de permissões
    |--------------------------------------------------------------------------
    |
    | Fonte única das permissões que um perfil pode ter (RN11). O tenant combina
    | permissões deste catálogo, não cria novas. Cada permissão declara a quais
    | tipos base se aplica: admin, user, customer.
    |
    | Estas são as da própria plataforma. Cada módulo construído sobre
    | ela acrescenta as suas aqui.
    |
    */

    'catalog' => [
        [
            'key' => 'people.manage',
            'name' => 'Gerenciar pessoas',
            'group' => 'Plataforma',
            'base_types' => ['admin'],
        ],
        [
            'key' => 'roles.manage',
            'name' => 'Gerenciar perfis',
            'group' => 'Plataforma',
            'base_types' => ['admin'],
        ],
        [
            'key' => 'features.manage',
            'name' => 'Gerenciar funcionalidades',
            'group' => 'Plataforma',
            'base_types' => ['admin'],
        ],
        [
            'key' => 'customers.manage_all',
            'name' => 'Gerenciar todos os clientes',
            'group' => 'Plataforma',
            'base_types' => ['admin'],
        ],
        [
            'key' => 'customers.manage_own',
            'name' => 'Gerenciar os próprios clientes',
            'group' => 'Plataforma',
            'base_types' => ['user'],
        ],
    ],

];
