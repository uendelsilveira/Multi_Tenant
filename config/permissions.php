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
    | As duas primeiras são da própria plataforma. Cada módulo construído sobre
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
    ],

];
