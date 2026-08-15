<?php

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class TenantIndex extends Component
{
    use WithPagination;

    public $confirmingTenantDeletion = null;

    public function confirmTenantDeletion($id)
    {
        $this->confirmingTenantDeletion = $id;
    }

    public function deleteTenant()
    {
        if ($this->confirmingTenantDeletion) {
            $tenant = Tenant::findOrFail($this->confirmingTenantDeletion);
            $tenant->delete();
            $this->confirmingTenantDeletion = null;
            session()->flash('success', 'Tenant excluído com sucesso!');
        }
    }

    #[Layout('layouts.base')]
    #[Title('Tenants')]
    public function render()
    {
        $tenants = Tenant::orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.tenant-index', ['tenants' => $tenants]);
    }
}
