<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Tenant\ProvisionalPasswordException;
use App\Filament\Tenant\Pages\ChangeProvisionalPassword;
use App\Models\TenantUser;
use App\Services\TenantUserService;
use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quem entrou com senha provisória só enxerga a tela de troca (RF10).
 * Senha provisória vencida encerra a sessão (RN28).
 */
final class EnforceProvisionalPasswordChange
{
    public function __construct(
        private readonly TenantUserService $users,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Authenticatable|null $user */
        $user = Filament::auth()->user();

        if (! $user instanceof TenantUser || ! $this->users->mustChangePassword($user)) {
            return $next($request);
        }

        if ($this->users->provisionalPasswordExpired($user)) {
            Filament::auth()->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Notification::make()
                ->title(ProvisionalPasswordException::expired()->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return redirect()->to(Filament::getLoginUrl() ?? '/admin/login');
        }

        if ($request->routeIs(ChangeProvisionalPassword::getRouteName(), 'filament.*.auth.logout')) {
            return $next($request);
        }

        return redirect()->to(ChangeProvisionalPassword::getUrl());
    }
}
