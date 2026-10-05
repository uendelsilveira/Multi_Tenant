<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantUserType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Perfil de um tenant. Vive no banco do tenant.
 *
 * @property int $id
 * @property string $name
 * @property TenantUserType|null $base_type
 * @property bool $is_system
 * @property list<string>|null $permissions
 */
final class Role extends Model
{
    protected $fillable = [
        'name',
        'base_type',
        'is_system',
        'permissions',
    ];

    /** Mesmo padrão da coluna, para o model recém-criado já responder sem reler o banco. */
    protected $attributes = [
        'is_system' => false,
    ];

    /** @return HasMany<TenantUser, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'role_id');
    }

    /**
     * Tipo desconhecido no banco vira null em vez de derrubar a tela.
     *
     * @return Attribute<TenantUserType|null, TenantUserType|string|null>
     */
    protected function baseType(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?TenantUserType => $value === null ? null : TenantUserType::tryFrom($value),
            set: fn (TenantUserType|string|null $value): ?string => $value instanceof TenantUserType ? $value->value : $value,
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'permissions' => 'array',
        ];
    }
}
