<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TenantUserType;

/**
 * Leitura do catálogo de permissões declarado em config/permissions.php (RN11).
 */
final class PermissionCatalog
{
    public const PEOPLE_MANAGE = 'people.manage';

    public const ROLES_MANAGE = 'roles.manage';

    /** @param array<int|string, mixed> $catalog */
    public function __construct(
        private readonly array $catalog,
    ) {}

    /**
     * Chaves das permissões que se aplicam a um tipo base.
     *
     * @return list<string>
     */
    public function keysFor(TenantUserType $type): array
    {
        return array_keys($this->optionsFor($type));
    }

    /**
     * Permissões de um tipo base, chave => nome, para seleção.
     *
     * @return array<string, string>
     */
    public function optionsFor(TenantUserType $type): array
    {
        $options = [];

        foreach ($this->catalog as $permission) {
            if (! is_array($permission) || empty($permission['key']) || empty($permission['name'])) {
                continue;
            }

            if (in_array($type->value, (array) ($permission['base_types'] ?? []), true)) {
                $options[(string) $permission['key']] = (string) $permission['name'];
            }
        }

        return $options;
    }

    /**
     * Do que foi pedido, só o que existe no catálogo e se aplica ao tipo base.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public function applicable(array $keys, TenantUserType $type): array
    {
        return array_values(array_intersect($this->keysFor($type), $keys));
    }
}
