<div>
    <x-slot name="header">
        Editar Tenant
    </x-slot>

    <x-slot name="headerDescription">
        Atualize os dados da instância. Note que o Identificador do Subdomínio (ID) não pode ser modificado.
    </x-slot>

    <x-slot name="headerActions">
        <a href="{{ route('tenants.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 dark:border-slate-600 text-sm font-semibold rounded-xl text-slate-700 dark:text-slate-300 bg-white/50 dark:bg-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all duration-300">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar
        </a>
    </x-slot>

    <div class="max-w-3xl mx-auto mt-6">
        <div class="relative bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200/60 dark:border-slate-800/60 rounded-3xl p-8 shadow-2xl overflow-hidden transition-all duration-300">
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/20 dark:bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <form wire:submit.prevent="updateTenant" class="relative z-10 space-y-8">
                
                <div class="space-y-6">
                    <!-- Tenant Name -->
                    <div class="group">
                        <label for="tenant_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-400 transition-colors">
                            Nome do Cliente ou Empresa
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <input wire:model="tenant_name" type="text" id="tenant_name" 
                                class="w-full pl-11 pr-4 py-3 bg-white/50 dark:bg-slate-800/50 border border-slate-300/50 dark:border-slate-700/50 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm text-slate-900 dark:text-white"
                                required>
                        </div>
                        @error('tenant_name') <span class="text-rose-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tenant ID (Readonly) -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-500 dark:text-slate-400 mb-2">
                            Identificador do Subdomínio (ID)
                        </label>
                        <div class="relative flex items-center opacity-70">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="text" value="{{ $tenantId }}" disabled
                                class="w-full pl-11 pr-32 py-3 bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-2xl cursor-not-allowed shadow-sm text-slate-500 dark:text-slate-400 font-mono text-sm">
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <span class="text-slate-400 text-sm font-medium">.{{ config('tenancy.central_domains')[0] ?? 'localhost' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200/50 dark:border-slate-800/50 flex justify-end">
                    <button type="submit" 
                        class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-sm font-bold rounded-2xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed">
                        <svg wire:loading wire:target="updateTenant" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Salvar Alterações</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
