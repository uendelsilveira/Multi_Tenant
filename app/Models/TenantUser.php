<?php

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property UserRole|null $role
 */
class TenantUser extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<Factory<self>> */
    use HasFactory, Notifiable, HasProfilePhoto, TwoFactorAuthenticatable;

    protected $table = 'tenant_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
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
}
