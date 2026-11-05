@props(['user' => null, 'size' => 'h-9 w-9'])

@php
    $user = $user ?? auth()->user();
    $url = $user?->profilePhotoUrl();
    $initials = collect(explode(' ', trim((string) $user?->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

@if ($url)
    <img src="{{ $url }}" alt="Foto de perfil de {{ $user->name }}"
        {{ $attributes->merge(['class' => $size.' rounded-full object-cover border border-slate-200']) }}>
@else
    <span aria-hidden="true"
        {{ $attributes->merge(['class' => $size.' inline-flex shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700']) }}>
        {{ $initials !== '' ? $initials : '?' }}
    </span>
@endif
