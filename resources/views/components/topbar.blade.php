<header class="h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between px-4 lg:px-6 shrink-0 z-30 transition-colors duration-200 shadow-sm dark:shadow-none">

    <!-- Esquerda: Toggle Mobile & Logo -->
    <div class="flex items-center gap-4">
        <!-- Botão Hamburger (Mobile) -->
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 focus:outline-none transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <!-- Logo -->
        <div class="flex items-center gap-2 select-none group cursor-pointer">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-primary flex items-center justify-center text-white font-extrabold text-lg shadow-[0_0_15px_rgba(21,87,255,0.4)] group-hover:shadow-[0_0_20px_rgba(21,87,255,0.6)] transition-all duration-300">
                S
            </div>
            <span class="text-xl font-bold tracking-tight text-slate-800 dark:text-white transition-colors ml-1">
                App<span class="text-primary drop-shadow-[0_0_8px_rgba(21,87,255,0.3)]">System</span>
            </span>
        </div>
    </div>

    <!-- Direita: Avatar & Dropdown -->
    <div class="relative">
        <button @click="profileOpen = !profileOpen" @click.outside="profileOpen = false" class="flex items-center gap-3 focus:outline-none group">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200 group-hover:text-primary dark:group-hover:text-white transition-colors">{{ auth()->user()->name ?? 'Usuário Logado' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 transition-colors">{{ auth()->user()->role ?? 'Admin' }}</p>
            </div>
            <img src="{{ auth()->user()->profile_photo_url ?? 'https://ui-avatars.com/api/?name=Admin&background=1557FF&color=fff' }}" alt="Avatar do Usuário" class="w-10 h-10 rounded-full border-2 border-slate-200 dark:border-slate-700 group-hover:border-primary transition-all object-cover">
        </button>

        <!-- Dropdown Menu -->
        <div x-show="profileOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 py-1 z-50 transition-colors duration-200">

            <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-primary dark:hover:text-white transition-colors">Meu Perfil</a>

            <button @click="toggleDarkMode()" class="w-full text-left flex items-center justify-between px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-primary dark:hover:text-white transition-colors">
                <span>Modo Escuro</span>
                <!-- Ícone Sol (aparece no modo dark) -->
                <svg x-show="darkMode" class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path></svg>
                <!-- Ícone Lua (aparece no modo light) -->
                <svg x-show="!darkMode" class="w-4 h-4 text-slate-500" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
            </button>

            <div class="border-t border-slate-200 dark:border-slate-700 my-1"></div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                    Sair do Sistema
                </button>
            </form>
        </div>
    </div>
</header>
