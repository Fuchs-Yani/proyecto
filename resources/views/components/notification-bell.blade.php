@props([
    'showLabel' => false,
])

@php
    $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
@endphp

<a href="{{ route('notifications.index') }}"
    {{ $attributes->merge(['class' => 'relative inline-flex items-center gap-2 rounded-md text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-900']) }}
    aria-label="Notificaciones{{ $unreadCount > 0 ? ' ('.$unreadCount.' sin leer)' : '' }}"
    title="Notificaciones">
    <span class="relative inline-flex">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -right-2 -top-2 inline-flex min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-4 text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </span>

    @if ($showLabel)
        <span>Notificaciones</span>
    @endif
</a>
