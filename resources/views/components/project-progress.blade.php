@props([
    'updates' => [],
    'title' => 'Progreso del proyecto',
    'subtitle' => 'Calculado según las etapas aprobadas por el profesor.',
])

@php
    $stages = \App\Models\ProjectUpdate::STAGES;
    $updates = collect($updates);
    $approvedUpdates = $updates->filter(fn ($update) => $update->isApproved());

    $stageRows = collect($stages)->map(function (string $label, string $key) use ($updates) {
        $stageUpdates = $updates->where('stage', $key);
        $approvedUpdate = $stageUpdates
            ->filter(fn ($update) => $update->isApproved())
            ->sortByDesc(fn ($update) => $update->approved_at ?? $update->created_at)
            ->first();

        if ($approvedUpdate) {
            $state = 'approved';
        } elseif ($stageUpdates->contains(fn ($update) => $update->isPending())) {
            $state = 'pending';
        } elseif ($stageUpdates->contains(fn ($update) => $update->isRejected())) {
            $state = 'rejected';
        } else {
            $state = 'empty';
        }

        return [
            'key' => $key,
            'label' => $label,
            'state' => $state,
            'approved_at' => $approvedUpdate?->approved_at,
        ];
    })->values();

    $approvedStages = $stageRows->where('state', 'approved')->count();
    $totalStages = $stageRows->count();
    $percentage = $totalStages > 0
        ? (int) round(($approvedStages / $totalStages) * 100)
        : 0;
    $percentage = max(0, min(100, $percentage));
    $latestStage = $stageRows->reverse()->firstWhere('state', 'approved');

    $barColors = [
        'approved' => 'bg-emerald-500',
        'pending' => 'bg-amber-400',
        'rejected' => 'bg-red-400',
        'empty' => 'bg-slate-200',
    ];

    $cardColors = [
        'approved' => 'border-emerald-200 bg-emerald-50/70 text-emerald-900',
        'pending' => 'border-amber-200 bg-amber-50/70 text-amber-900',
        'rejected' => 'border-red-200 bg-red-50/70 text-red-900',
        'empty' => 'border-slate-200 bg-slate-50 text-slate-600',
    ];

    $stateLabels = [
        'approved' => 'Aprobada',
        'pending' => 'Pendiente',
        'rejected' => 'Rechazada',
        'empty' => 'Sin avances',
    ];
@endphp

<section {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-4 space-y-3']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-bold text-slate-900">{{ $title }}</h3>
            <p class="text-sm text-slate-500">{{ $subtitle }}</p>
        </div>
        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-700">
            {{ $percentage }}%
        </span>
    </div>

    <div class="flex gap-1" role="progressbar"
        aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"
        aria-label="{{ $title }}: {{ $percentage }}%">
        @foreach ($stageRows as $stage)
            <div class="h-2.5 flex-1 rounded-full {{ $barColors[$stage['state']] }}"></div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
        <span>
            Etapas aprobadas: {{ $approvedStages }} de {{ $totalStages }}
            · Avances aprobados: {{ $approvedUpdates->count() }}
        </span>
        @if ($latestStage)
            <span>
                Última etapa aprobada: {{ $latestStage['label'] }}
                @if ($latestStage['approved_at'])
                    · {{ $latestStage['approved_at']->format('d/m/Y H:i') }}
                @endif
            </span>
        @else
            <span>Todavía no hay etapas aprobadas.</span>
        @endif
    </div>

    <ol class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($stageRows as $stage)
            <li class="rounded-md border px-2 py-2 {{ $cardColors[$stage['state']] }}">
                <div class="flex items-center gap-1.5">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white {{ $barColors[$stage['state']] }}">
                        {{ $stage['state'] === 'approved' ? '✓' : $loop->iteration }}
                    </span>
                    <span class="text-xs font-semibold">{{ $stage['label'] }}</span>
                </div>
                <p class="mt-1 text-[11px]">{{ $stateLabels[$stage['state']] }}</p>
            </li>
        @endforeach
    </ol>
</section>
