@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold leading-5 text-white shadow-sm hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-md px-3 py-2 text-sm font-medium leading-5 text-slate-700 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
