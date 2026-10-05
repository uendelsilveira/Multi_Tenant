<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantUserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Cliente: a pessoa do tenant cujo perfil é de tipo cliente, vista pelo
 * cadastro de clientes. É a mesma linha da tabela `users` que TenantUser usa
 * para autenticar (ADR-0003); aqui ela aparece com seus dados e vínculos.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $role_id
 * @property bool $is_active
 * @property bool $must_change_password
 * @property-read CustomerProfile|null $profile
 * @property-read Collection<int, TenantUser> $responsibles
 */
final class Customer extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
    ];

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return HasOne<CustomerProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class, 'user_id');
    }

    /**
     * Usuários do tenant que atendem este cliente.
     *
     * @return BelongsToMany<TenantUser, $this>
     */
    public function responsibles(): BelongsToMany
    {
        return $this->belongsToMany(TenantUser::class, 'customer_user', 'customer_id', 'user_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('customers', function (Builder $query): void {
            $query->whereHas('role', fn (Builder $role): Builder => $role->where('base_type', TenantUserType::Customer->value));
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }
}
