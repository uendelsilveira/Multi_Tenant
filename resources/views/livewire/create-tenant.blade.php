<div>
    <x-slot name="header">
        Criar Novo Tenant
    </x-slot>

    <x-slot name="headerDescription">
        Provisione uma nova instância isolada para o seu cliente com subdomínio próprio e banco de dados exclusivo.
    </x-slot>

    <div class="max-w-3xl mx-auto mt-6">
        <div class="relative bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200/60 dark:border-slate-800/60 rounded-3xl p-8 shadow-2xl overflow-hidden transition-all duration-300 hover:shadow-indigo-500/10">
            <!-- Decorative gradient orb -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/20 dark:bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <form wire:submit.prevent="createTenant" class="relative z-10 space-y-8">
                
                @if (session()->has('success'))
                    <div class="p-4 bg-emerald-50/80 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl text-emerald-600 dark:text-emerald-400 text-sm flex items-center space-x-3 mb-6 animate-pulse">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

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
                            <input wire:model="tenant_name" type="text" id="tenant_name" placeholder="Ex: Empresa Acme LTDA"
                                class="w-full pl-11 pr-4 py-3 bg-white/50 dark:bg-slate-800/50 border border-slate-300/50 dark:border-slate-700/50 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm text-slate-900 dark:text-white placeholder-slate-400"
                                required>
                        </div>
                        @error('tenant_name') <span class="text-rose-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tenant ID (Subdomain) -->
                    <div class="group">
                        <label for="tenant_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-400 transition-colors">
                            Identificador do Subdomínio
                        </label>
                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <input wire:model="tenant_id" type="text" id="tenant_id" placeholder="Ex: acme"
                                class="w-full pl-11 pr-32 py-3 bg-white/50 dark:bg-slate-800/50 border border-slate-300/50 dark:border-slate-700/50 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm text-slate-900 dark:text-white placeholder-slate-400 font-mono text-sm"
                                required>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <span class="text-slate-500 dark:text-slate-400 text-sm font-medium">.{{ config('tenancy.central_domains')[0] ?? 'localhost' }}</span>
                            </div>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-2 ml-1">Utilize apenas letras, números e hífens. Sem espaços.</p>
                        @error('tenant_id') <span class="text-rose-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200/50 dark:border-slate-800/50 flex justify-end">
                    <button type="submit" 
                        class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-sm font-bold rounded-2xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed">
                        <svg wire:loading wire:target="createTenant" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Provisionar Tenant</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
