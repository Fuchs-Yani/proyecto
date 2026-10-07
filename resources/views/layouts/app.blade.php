<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Banco de Ideas y Proyectos</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-800">
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
    </body>
</html>
