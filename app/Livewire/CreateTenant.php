<?php

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateTenant extends Component
{
    #[Validate('required|string|min:3|max:50|unique:tenants,id|alpha_dash')]
    public $tenant_id = '';

    #[Validate('required|string|max:255')]
    public $tenant_name = '';

    public function createTenant()
    {
        $this->validate();

        $tenant = Tenant::create([
            'id' => $this->tenant_id,
            'tenant_name' => $this->tenant_name,
        ]);

        $domainBase = config('tenancy.central_domains')[0] ?? 'localhost';

        $tenant->domains()->create([
            'domain' => $this->tenant_id.'.'.$domainBase,
        ]);

        session()->flash('success', 'Tenant criado com sucesso!');

        return redirect()->route('tenants.index');
    }

    #[Layout('layouts.base')]
    #[Title('Novo Tenant')]
    public function render()
    {
        return view('livewire.create-tenant');
    }
}
