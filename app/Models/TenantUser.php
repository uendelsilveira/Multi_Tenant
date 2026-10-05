<?php

declare(strict_types=1);

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole|null $role
 * @property bool $must_change_password
 * @property Carbon|null $password_expires_at
 */
final class TenantUser extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory, Notifiable;

    protected $table = 'tenant_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'must_change_password',
        'password_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Papel desconhecido no banco vira null em vez de derrubar a tela: quem não
     * tem papel reconhecido fica sem nenhuma permissão de gestão.
     *
     * @return Attribute<UserRole|null, UserRole|string|null>
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?UserRole => $value === null ? null : UserRole::tryFrom($value),
            set: fn (UserRole|string|null $value): ?string => $value instanceof UserRole ? $value->value : $value,
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    public function isOperator(): bool
    {
        return $this->role === UserRole::Operator;
    }

    public function hasRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        return $this->role !== null && $this->role->value === $roleValue;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_expires_at' => 'datetime',
        ];
    }
}
