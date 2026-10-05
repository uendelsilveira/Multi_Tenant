<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Policies\PlanPolicy;
use App\Policies\TenantPolicy;
use Tests\TestCase;

/**
 * Um papel gravado fora do enum (dado legado, edição manual no banco) não pode
 * derrubar o painel. Quem tem papel desconhecido só consulta.
 */
final class UnknownRoleTest extends TestCase
{
    public function test_an_unknown_role_reads_as_no_role_instead_of_failing(): void
    {
        $user = (new User)->setRawAttributes(['role' => 'user']);
        $tenantUser = (new TenantUser)->setRawAttributes(['role' => 'qualquer-coisa']);

        $this->assertNull($user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->hasRole(UserRole::Admin));
        $this->assertNull($tenantUser->role);
        $this->assertFalse($tenantUser->isAdmin());
    }

    public function test_a_user_with_an_unknown_role_can_only_consult(): void
    {
        $user = (new User)->setRawAttributes(['role' => 'user']);

        $this->assertTrue((new PlanPolicy)->viewAny($user));
        $this->assertFalse((new PlanPolicy)->create($user));
        $this->assertFalse((new PlanPolicy)->delete($user, new Plan));
        $this->assertTrue((new TenantPolicy)->viewAny($user));
        $this->assertFalse((new TenantPolicy)->create($user));
        $this->assertFalse((new TenantPolicy)->retryProvisioning($user, new Tenant));
    }

    public function test_known_roles_keep_working_as_enum_and_as_string(): void
    {
        $fromEnum = new User(['role' => UserRole::SuperAdmin]);
        $fromString = new User(['role' => 'admin']);

        $this->assertSame(UserRole::SuperAdmin, $fromEnum->role);
        $this->assertSame('super_admin', $fromEnum->getAttributes()['role']);
        $this->assertSame(UserRole::Admin, $fromString->role);
        $this->assertTrue((new PlanPolicy)->create($fromString));
    }
}
