@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-md bg-slate-900 px-3 py-2 text-start text-base font-semibold text-white hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 transition duration-150 ease-in-out'
            : 'block w-full rounded-md px-3 py-2 text-start text-base font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
