@props([
    'updates' => [],
    'title' => 'Historial de avances',
    'subtitle' => null,
])

@php
    $updates = collect($updates)->sortByDesc('created_at');

    $statusLabels = [
        \App\Models\ProjectUpdate::STATUS_APPROVED => 'Aprobado',
        \App\Models\ProjectUpdate::STATUS_REJECTED => 'Rechazado',
        \App\Models\ProjectUpdate::STATUS_PENDING => 'Pendiente de revisión',
    ];

    $statusClasses = [
        \App\Models\ProjectUpdate::STATUS_APPROVED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        \App\Models\ProjectUpdate::STATUS_REJECTED => 'border-red-200 bg-red-50 text-red-700',
        \App\Models\ProjectUpdate::STATUS_PENDING => 'border-amber-200 bg-amber-50 text-amber-700',
    ];
@endphp

@if ($updates->isNotEmpty())
    <div class="space-y-2">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h4 class="text-sm font-bold text-slate-900">{{ $title }}</h4>
                @if ($subtitle)
                    <p class="text-xs text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            <span class="text-xs text-slate-500">
                {{ $updates->count() }} {{ $updates->count() === 1 ? 'registro' : 'registros' }}
            </span>
        </div>

        <div class="overflow-x-auto rounded-md border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Etapa</th>
                        <th class="px-3 py-2">Avance</th>
                        <th class="px-3 py-2">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($updates as $update)
                        <tr class="border-t border-slate-100 align-top">
                            <td class="whitespace-nowrap px-3 py-2 text-slate-600">
                                {{ $update->created_at?->format('d/m/Y') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 font-medium text-slate-700">
                                {{ $update->stageLabel() }}
                            </td>
                            <td class="px-3 py-2 text-slate-700">
                                <a href="#avance-{{ $update->id }}" class="hover:underline">
                                    {{ \Illuminate\Support\Str::limit($update->description, 100) }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $statusClasses[$update->status] ?? 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                    {{ $statusLabels[$update->status] ?? $update->status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
