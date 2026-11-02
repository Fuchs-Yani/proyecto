@props(['message' => null])

@php
    $message ??= session('status');
@endphp

@if (filled($message))
    <div
        x-data="{ visible: true }"
        x-init="setTimeout(() => visible = false, 4000)"
        x-show="visible"
        x-transition.opacity.duration.300ms
        data-flash-toast
        role="status"
        aria-live="polite"
        class="fixed bottom-4 left-4 right-4 z-50 flex items-start gap-3 rounded-lg border border-emerald-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-lg sm:left-auto sm:max-w-sm"
    >
        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
        <p class="flex-1">{{ $message }}</p>
        <button type="button" @click="visible = false" class="shrink-0 text-lg leading-none text-slate-400 hover:text-slate-600" aria-label="Cerrar">&times;</button>
    </div>
@endif
