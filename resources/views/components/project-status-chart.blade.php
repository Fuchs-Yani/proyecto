@props([
    'segments' => [],
    'centerLabel' => 'proyectos',
    'ariaLabel' => null,
])

@php
    $segments = collect($segments);
    $total = (int) $segments->sum('value');
    $description = $segments->map(fn ($segment) => $segment['value'].' '.$segment['label'])->implode(', ');
    $ariaLabel = $ariaLabel ?? 'Distribución de '.$centerLabel.': '.$description;
    $radius = 70;
    $circumference = 2 * M_PI * $radius;
    $dashOffset = 0.0;
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-6 sm:flex-row']) }}>
    <div class="relative h-44 w-44 shrink-0">
        <svg viewBox="0 0 200 200" class="h-44 w-44 -rotate-90" role="img"
            aria-label="{{ $ariaLabel }}">
            <circle cx="100" cy="100" r="{{ $radius }}" fill="none" stroke="#e2e8f0" stroke-width="26" />

            @if ($total > 0)
                @foreach ($segments as $segment)
                    @continue($segment['value'] <= 0)
                    @php
                        $length = $circumference * ($segment['value'] / $total);
                    @endphp
                    <circle cx="100" cy="100" r="{{ $radius }}" fill="none"
                        stroke="{{ $segment['color'] }}" stroke-width="26"
                        stroke-dasharray="{{ $length }} {{ $circumference - $length }}"
                        stroke-dashoffset="{{ -$dashOffset }}">
                        <title>{{ $segment['label'] }}: {{ $segment['value'] }}</title>
                    </circle>
                    @php
                        $dashOffset += $length;
                    @endphp
                @endforeach
            @endif
        </svg>

        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-2xl font-bold text-slate-900">{{ $total }}</span>
            <span class="text-xs text-slate-500">{{ $centerLabel }}</span>
        </div>
    </div>

    <ul class="w-full space-y-2">
        @foreach ($segments as $segment)
            @php
                $percentage = $total > 0 ? round(($segment['value'] / $total) * 100, 1) : 0;
            @endphp
            <li class="flex items-center justify-between gap-3 text-sm">
                <span class="flex min-w-0 items-center gap-2">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $segment['color'] }}"></span>
                    <span class="truncate text-slate-700">{{ $segment['label'] }}</span>
                </span>
                <span class="shrink-0 font-semibold text-slate-900">
                    {{ $segment['value'] }}
                    <span class="text-xs font-normal text-slate-500">({{ $percentage }}%)</span>
                </span>
            </li>
        @endforeach
    </ul>
</div>
