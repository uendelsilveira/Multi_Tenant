<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\PersonType;
use App\Enums\ProvisioningStatus;
use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string|null $legal_name
 * @property string|null $trade_name
 * @property PersonType|null $person_type
 * @property string|null $document
 * @property string|null $state_registration
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $zip_code
 * @property string|null $street
 * @property string|null $number
 * @property string|null $complement
 * @property string|null $district
 * @property string|null $city
 * @property string|null $state
 * @property string|null $notes
 * @property int|null $plan_id
 * @property BillingCycle|null $billing_cycle
 * @property TenantStatus $status
 * @property ProvisioningStatus $provisioning_status
 * @property string|null $provisioning_error
 * @property Carbon|null $provisioned_at
 * @property Carbon|null $deleted_at
 * @property string|null $tenancy_db_name
 * @property-read Plan|null $plan
 * @property-read Collection<int, Domain> $domains
 */
final class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, SoftDeletes;

    /**
     * Colunas reais da tabela. O que não estiver aqui vai para o JSON `data`.
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'legal_name',
            'trade_name',
            'person_type',
            'document',
            'state_registration',
            'contact_name',
            'contact_email',
            'contact_phone',
            'zip_code',
            'street',
            'number',
            'complement',
            'district',
            'city',
            'state',
            'notes',
            'plan_id',
            'billing_cycle',
            'status',
            'provisioning_status',
            'provisioning_error',
            'provisioned_at',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    /** @return HasMany<Domain, $this> */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'tenant_id');
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'person_type' => PersonType::class,
            'billing_cycle' => BillingCycle::class,
            'status' => TenantStatus::class,
            'provisioning_status' => ProvisioningStatus::class,
            'provisioned_at' => 'datetime',
        ];
    }
}
