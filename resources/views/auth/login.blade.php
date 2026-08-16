<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Edit.AI') }} - Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=kodchasan:400,500,600,700,800&display=swap" rel="stylesheet"/>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body
    class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center p-4 antialiased relative overflow-hidden">

<!-- Efeitos de Brilho no Background (Blur) -->
<div
    class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-primary/20 rounded-full blur-[120px] pointer-events-none"></div>
<div
    class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-primary/10 rounded-full blur-[100px] pointer-events-none"></div>

<!-- Container Principal do Login -->
<div class="w-full max-w-md bg-gray-800 border border-gray-700 rounded-2xl shadow-2xl p-6 sm:p-8 relative z-10">

    <!-- Logo e Cabeçalho -->
    <div class="text-center mb-8">
        <div class="flex justify-center mb-6">
            <x-logo class="w-48 h-auto"/>
        </div>
        <h1 class="text-xl font-bold text-white">Acesse sua conta</h1>
        <p class="text-sm text-gray-400 mt-1">Bem-vindo de volta! Por favor, insira seus dados.</p>
    </div>

    <x-validation-errors class="mb-4"/>

    @session('status')
    <div class="mb-4 font-medium text-sm text-green-400 bg-green-900/30 border border-green-800 rounded-lg p-3">
        {{ $value }}
    </div>
    @endsession


    <!-- Formulário de Login Tradicional -->
    <form action="{{ route('login') }}" method="POST" class="space-y-4">
        @csrf

        <!-- Campo E-mail -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">E-mail corporativo</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nome@empresa.com.br"
                   required autofocus autocomplete="username"
                   class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary placeholder-gray-500 transition-colors">
        </div>

        <!-- Campo Senha com Toggle via Alpine.js -->
        <div x-data="{ showPassword: false }">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium text-gray-300">Senha</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm text-primary hover:text-[#3b72ff] font-medium transition-colors">Esqueceu a
                        senha?</a>
                @endif
            </div>
            <div class="relative">
                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" placeholder="••••••••"
                       required autocomplete="current-password"
                       class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg pl-4 pr-11 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary placeholder-gray-500 transition-colors">

                <!-- Botão Mostrar/Ocultar Senha -->
                <button type="button" @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white focus:outline-none">
                    <!-- Ícone Olho Fechado -->
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                    </svg>
                    <!-- Ícone Olho Aberto -->
                    <svg x-show="showPassword" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Lembre-me -->
        <div class="block mt-4">
            <label for="remember_me" class="flex items-center">
                <x-checkbox id="remember_me" name="remember"/>
                <span class="ms-2 text-sm text-gray-400">{{ __('Manter conectado') }}</span>
            </label>
        </div>

        <!-- Botão Submit -->
        <button type="submit"
                class="w-full bg-primary hover:bg-[#0c40d1] text-white font-medium py-2.5 rounded-lg transition-colors shadow-lg shadow-primary/20 mt-4 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-gray-800">
            Entrar na plataforma
        </button>
    </form>
</div>

@livewireScripts
</body>
</html>
