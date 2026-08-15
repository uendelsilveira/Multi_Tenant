<?php

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class EditTenant extends Component
{
    public $tenantId;

    public $tenant_name;

    protected function rules()
    {
        return [
            'tenant_name' => 'required|string|max:255',
        ];
    }

    public function mount($tenant)
    {
        $tenantModel = Tenant::findOrFail($tenant);
        $this->tenantId = $tenantModel->id;
        $this->tenant_name = $tenantModel->tenant_name ?? $tenantModel->id;
    }

    public function updateTenant()
    {
        $this->validate();

        $tenantModel = Tenant::findOrFail($this->tenantId);
        $tenantModel->update([
            'tenant_name' => $this->tenant_name,
        ]);

        session()->flash('success', 'Tenant atualizado com sucesso!');

        return redirect()->route('tenants.index');
    }

    #[Layout('layouts.base')]
    #[Title('Editar Tenant')]
    public function render()
    {
        return view('livewire.edit-tenant');
    }
}
