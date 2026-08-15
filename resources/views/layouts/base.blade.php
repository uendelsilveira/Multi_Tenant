<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sistema Moderno') }}</title>

    <!-- Previne flash de tema incorreto (FOUC) -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-slate-50 text-slate-900 dark:bg-[#0B1121] dark:text-slate-100 flex flex-col h-screen overflow-hidden antialiased transition-colors duration-300 selection:bg-primary/30 selection:text-primary"
      x-data="{
          sidebarOpen: false,
          profileOpen: false,
          darkMode: localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
          toggleDarkMode() {
              this.darkMode = !this.darkMode;
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
                  localStorage.theme = 'dark';
              } else {
                  document.documentElement.classList.remove('dark');
                  localStorage.theme = 'light';
              }
          }
      }">

    <!-- Background Decoration (Optional subtle gradients for deep tech feel) -->
    <div class="fixed inset-0 z-[-1] pointer-events-none hidden dark:block bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-indigo-900/20 via-[#0B1121] to-[#0B1121]"></div>

    <x-topbar />

    <!-- Container Principal (Sidebar + Main Content) -->
    <div class="flex flex-1 overflow-hidden relative">

        <!-- Overlay escuro para mobile quando a sidebar está aberta -->
        <div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-30 lg:hidden"
             @click="sidebarOpen = false"></div>

        <x-sidebar />

    <!-- Conteúdo Principal -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-transparent p-4 sm:p-6 lg:p-8 transition-colors duration-300 relative">
        <div class="w-full max-w-screen-2xl mx-auto space-y-6 lg:space-y-8">

            @if (isset($header))
            <!-- Cabeçalho da Página -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-200/60 dark:border-slate-800/60">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight">{{ $header }}</h1>
                    @if(isset($headerDescription))
                    <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm max-w-2xl">{{ $headerDescription }}</p>
                    @endif
                </div>
                @if(isset($headerActions))
                <div class="flex items-center gap-3">
                    {{ $headerActions }}
                </div>
                @endif
            </div>
            @endif

            <!-- Área de Conteúdo/Slot -->
            <div class="w-full">
                {{ $slot }}
            </div>

        </div>
    </main>
</div>

@stack('modals')
@livewireScripts
</body>
</html>
