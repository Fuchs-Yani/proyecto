@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-slate-950']) }}>
    {{ $value ?? $slot }}
</label>
