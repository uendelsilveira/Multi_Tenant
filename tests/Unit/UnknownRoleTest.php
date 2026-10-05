<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TenantUserType;
use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Policies\PlanPolicy;
use App\Policies\TenantPolicy;
use Filament\Facades\Filament;
use Tests\TestCase;

/**
 * Um papel ou tipo gravado fora do enum (dado legado, edição manual no banco)
 * não pode derrubar o painel. Quem está nessa situação fica sem permissão.
 */
final class UnknownRoleTest extends TestCase
{
    public function test_an_unknown_central_role_reads_as_no_role_instead_of_failing(): void
    {
        $user = (new User)->setRawAttributes(['role' => 'user']);

        $this->assertNull($user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->hasRole(UserRole::Admin));
    }

    public function test_a_central_user_with_an_unknown_role_can_only_consult(): void
    {
        $user = (new User)->setRawAttributes(['role' => 'user']);

        $this->assertTrue((new PlanPolicy)->viewAny($user));
        $this->assertFalse((new PlanPolicy)->create($user));
        $this->assertFalse((new PlanPolicy)->delete($user, new Plan));
        $this->assertTrue((new TenantPolicy)->viewAny($user));
        $this->assertFalse((new TenantPolicy)->create($user));
        $this->assertFalse((new TenantPolicy)->retryProvisioning($user, new Tenant));
    }

    public function test_known_central_roles_keep_working_as_enum_and_as_string(): void
    {
        $fromEnum = new User(['role' => UserRole::SuperAdmin]);
        $fromString = new User(['role' => 'admin']);

        $this->assertSame(UserRole::SuperAdmin, $fromEnum->role);
        $this->assertSame('super_admin', $fromEnum->getAttributes()['role']);
        $this->assertSame(UserRole::Admin, $fromString->role);
        $this->assertTrue((new PlanPolicy)->create($fromString));
    }

    public function test_each_tenant_person_enters_only_the_panel_of_its_role_type(): void
    {
        $panels = [
            'tenant-admin' => TenantUserType::Admin,
            'tenant-user' => TenantUserType::User,
            'tenant-customer' => TenantUserType::Customer,
        ];

        foreach ($panels as $panelId => $ownType) {
            foreach (TenantUserType::cases() as $type) {
                $this->assertSame(
                    $type === $ownType,
                    $this->person($type)->canAccessPanel(Filament::getPanel($panelId)),
                    "Perfil de tipo {$type->value} no painel {$panelId}.",
                );
            }
        }
    }

    public function test_a_tenant_person_without_a_usable_role_or_deactivated_enters_no_panel(): void
    {
        $unknownType = (new TenantUser)->setRelation('role', (new Role)->setRawAttributes(['base_type' => 'qualquer-coisa']));
        $noRole = (new TenantUser)->setRelation('role', null);
        $deactivated = $this->person(TenantUserType::Admin);
        $deactivated->is_active = false;
        $central = new User(['role' => UserRole::SuperAdmin]);

        $this->assertNull($unknownType->type);

        foreach (['tenant-admin', 'tenant-user', 'tenant-customer'] as $panelId) {
            $panel = Filament::getPanel($panelId);

            $this->assertFalse($unknownType->canAccessPanel($panel));
            $this->assertFalse($noRole->canAccessPanel($panel));
            $this->assertFalse($deactivated->canAccessPanel($panel));
            $this->assertFalse($central->canAccessPanel($panel));
        }

        $this->assertTrue($central->canAccessPanel(Filament::getPanel('admin')));
    }

    private function person(TenantUserType $type): TenantUser
    {
        return (new TenantUser)->setRelation('role', new Role(['base_type' => $type]));
    }
}
