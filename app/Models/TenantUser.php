<?php

declare(strict_types=1);

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

namespace App\Models;

use App\Enums\TenantUserType;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Pessoa de um tenant. Vive no banco do tenant, na tabela `users`.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $role_id
 * @property bool $is_active
 * @property bool $must_change_password
 * @property Carbon|null $password_expires_at
 * @property-read Role|null $role
 * @property-read TenantUserType|null $type
 */
final class TenantUser extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'must_change_password',
        'password_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Mesmos padrões das colunas, para o model recém-criado já responder sem reler o banco. */
    protected $attributes = [
        'is_active' => true,
        'must_change_password' => false,
    ];

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Pessoa ativa só entra no painel do tipo base do seu perfil (RF09). */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && $this->type !== null
            && $this->type->panel()->panelId() === $panel->getId();
    }

    /**
     * O tipo base da pessoa é o do perfil dela.
     *
     * @return Attribute<TenantUserType|null, never>
     */
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn (): ?TenantUserType => $this->role?->base_type,
        );
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_expires_at' => 'datetime',
        ];
    }
}
