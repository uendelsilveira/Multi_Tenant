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
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property TenantUserType|null $type
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
        'type',
        'must_change_password',
        'password_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Mesmo padrão da coluna, para o model recém-criado já responder sem reler o banco. */
    protected $attributes = [
        'must_change_password' => false,
    ];

    /** Cada pessoa só entra no painel do seu tipo base (RF09). */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->type !== null && $this->type->panel()->panelId() === $panel->getId();
    }

    /**
     * Tipo desconhecido no banco vira null em vez de derrubar a tela: quem não
     * tem tipo reconhecido não entra em painel nenhum.
     *
     * @return Attribute<TenantUserType|null, TenantUserType|string|null>
     */
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?TenantUserType => $value === null ? null : TenantUserType::tryFrom($value),
            set: fn (TenantUserType|string|null $value): ?string => $value instanceof TenantUserType ? $value->value : $value,
        );
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
