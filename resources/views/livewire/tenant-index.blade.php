<div>
    <x-slot name="header">
        Tenants
    </x-slot>

    <x-slot name="headerDescription">
        Gerencie as instâncias, subdomínios e bancos de dados dos seus clientes.
    </x-slot>

    <x-slot name="headerActions">
        <a href="{{ route('tenants.create') }}" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all duration-300">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Tenant
        </a>
    </x-slot>

    <div class="max-w-7xl mx-auto mt-6">
        @if (session()->has('success'))
            <x-alert type="success" class="mb-6">
                {{ session('success') }}
            </x-alert>
        @endif

        <div class="bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200/60 dark:border-slate-800/60 rounded-3xl shadow-xl overflow-hidden transition-all duration-300">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold border-b border-slate-200/50 dark:border-slate-700/50">Cliente/Empresa</th>
                            <th class="px-6 py-4 font-semibold border-b border-slate-200/50 dark:border-slate-700/50">Subdomínio (ID)</th>
                            <th class="px-6 py-4 font-semibold border-b border-slate-200/50 dark:border-slate-700/50">Data de Criação</th>
                            <th class="px-6 py-4 font-semibold border-b border-slate-200/50 dark:border-slate-700/50 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/50 dark:divide-slate-700/50 text-sm">
                        @forelse ($tenants as $tenant)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900 dark:text-slate-100">
                                    {{ $tenant->tenant_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300 font-mono text-xs">
                                    {{ $tenant->id }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                    {{ $tenant->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <a href="{{ route('tenants.edit', $tenant->id) }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Editar
                                    </a>
                                    <button wire:click="confirmTenantDeletion('{{ $tenant->id }}')" class="inline-flex items-center text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 mb-4 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        <p>Nenhum tenant encontrado.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if ($tenants->hasPages())
                <div class="px-6 py-4 border-t border-slate-200/50 dark:border-slate-700/50">
                    {{ $tenants->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-confirmation-modal wire:model.live="confirmingTenantDeletion">
        <x-slot name="title">
            Excluir Tenant
        </x-slot>

        <x-slot name="content">
            Tem certeza de que deseja excluir este tenant? Todos os dados, banco de dados e arquivos associados a ele poderão ser permanentemente removidos. Esta ação não pode ser desfeita.
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingTenantDeletion')" wire:loading.attr="disabled">
                Cancelar
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="deleteTenant" wire:loading.attr="disabled">
                Excluir Tenant
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
