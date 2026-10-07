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
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-md mx-auto px-4 py-3 flex items-center justify-between">
                <a href="/" class="font-bold text-slate-900">Banco de Ideas</a>
                <a href="/" class="text-sm text-slate-500 underline">Volver al inicio</a>
            </div>
        </header>
        <div class="min-h-screen flex flex-col items-center pt-10 px-4">
            <div class="w-full sm:max-w-md bg-white border border-slate-200 rounded-lg px-6 py-6 shadow-sm">
                {{ $slot }}
            </div>
            <p class="mt-6 text-xs text-slate-500">Instituto Sedes Sapientiae 2026</p>
        </div>
    </body>
</html>
