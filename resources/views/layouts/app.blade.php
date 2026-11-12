<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>IDEATÓN</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-800">
        @if (auth()->check() && auth()->user()->hasRole('profesor'))
            <div x-data="{ open: false }" class="min-h-screen lg:flex">
                @include('layouts.professor-sidebar')

                <div class="flex min-w-0 flex-1 flex-col">
                    <div class="flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4 lg:hidden">
                        <a href="/" class="font-bold text-slate-900">IDEATÓN</a>
                        <button type="button" @click="open = true" class="rounded-md p-2 text-slate-600 hover:bg-slate-100" aria-label="Abrir menú">☰</button>
                    </div>

                    @isset($header)
                        @unless (request()->routeIs('dashboard'))
                            <header class="bg-white border-b border-slate-200">
                                <div class="max-w-6xl mx-auto py-4 px-4">
                                    {{ $header }}
                                </div>
                            </header>
                        @endunless
                    @endisset

                    <main class="flex-1">{{ $slot }}</main>

                    <footer class="bg-slate-900 text-slate-200 text-center text-sm py-3 mt-6">Instituto Sedes Sapientiae 2026</footer>
                </div>
            </div>
        @else
            <div class="min-h-screen flex flex-col">
                @include('layouts.navigation')

                @isset($header)
                    <header class="bg-white border-b border-slate-200">
                        <div class="max-w-6xl mx-auto py-4 px-4">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-1">{{ $slot }}</main>

                <footer class="bg-slate-900 text-slate-200 text-center text-sm py-3 mt-6">Instituto Sedes Sapientiae 2026</footer>
            </div>
        @endif

        <script>
            // Evita que un aviso viejo reaparezca al volver atrás (restauración desde caché del navegador).
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    document.querySelectorAll('[data-flash-toast]').forEach(function (toast) {
                        toast.remove();
                    });
                }
            });
        </script>
    </body>
</html>
