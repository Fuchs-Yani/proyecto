@props([
    'update',
    'showApprove' => false,
    'showStudent' => true,
    'showRepository' => true,
])

@php
    $approved = $update->isApproved();
    $rejected = $update->isRejected();
@endphp

<article id="avance-{{ $update->id }}" class="rounded-lg border border-slate-200 bg-white p-4 space-y-3">
    <header class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
            @if ($showStudent && $update->student)
                <x-avatar :user="$update->student" size="h-7 w-7" />
                <span class="font-semibold text-slate-900">{{ $update->student->name }}</span>
                <span aria-hidden="true">·</span>
            @endif
            <time datetime="{{ $update->created_at?->toIso8601String() }}">
                {{ $update->created_at?->format('d/m/Y H:i') }}
            </time>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($update->stage)
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-600">
                    {{ $update->stageLabel() }}
                </span>
            @endif

            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $approved ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($rejected ? 'border-red-200 bg-red-50 text-red-700' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                {{ $approved ? 'Aprobado' : ($rejected ? 'Rechazado' : 'Pendiente de revisión') }}
            </span>
        </div>
    </header>

    <p class="whitespace-pre-line text-sm text-slate-700">{{ $update->description }}</p>

    @if ($showRepository && $update->repository_url)
        <p class="text-sm">
            <span class="text-slate-500">Repositorio:</span>
            <a href="{{ $update->repository_url }}" target="_blank" rel="noopener noreferrer"
                class="break-all text-blue-700 underline">{{ $update->repository_url }}</a>
        </p>
    @endif

    @if ($update->images->isNotEmpty())
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($update->images as $image)
                <a href="{{ $image->url() }}" target="_blank" rel="noopener noreferrer"
                    class="block overflow-hidden rounded-md border border-slate-200 bg-slate-50"
                    title="{{ $image->original_name }}">
                    <img src="{{ $image->url() }}" alt="Imagen del avance: {{ $image->original_name }}"
                        class="h-32 w-full object-cover">
                </a>
            @endforeach
        </div>
    @endif

    @if ($showApprove && $update->isPending())
        <div class="flex flex-wrap items-center gap-2 pt-1">
            <form method="POST" action="{{ route('progress.approve', $update) }}">
                @csrf
                @method('PATCH')
                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                    Aprobar avance
                </button>
            </form>
            <form method="POST" action="{{ route('progress.reject', $update) }}"
                onsubmit="return confirm('¿Rechazar este avance? El alumno podrá enviar uno nuevo.');">
                @csrf
                @method('PATCH')
                <button class="rounded-md border border-red-300 px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                    Rechazar avance
                </button>
            </form>
        </div>
    @endif

    @if ($approved && $update->approved_at)
        <p class="text-xs text-slate-500">
            Aprobado el {{ $update->approved_at->format('d/m/Y H:i') }}@if ($update->approver) por {{ $update->approver->name }}@endif.
        </p>
    @elseif ($rejected)
        <p class="text-xs text-slate-500">
            Rechazado el {{ $update->updated_at?->format('d/m/Y H:i') }}. Podés enviar un nuevo avance.
        </p>
    @endif
</article>
