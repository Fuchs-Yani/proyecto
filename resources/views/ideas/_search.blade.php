<form method="GET" action="{{ $action }}" class="flex flex-wrap items-center gap-2">
    <input type="search" name="search" value="{{ request('search') }}"
        placeholder="Buscar por nombre de idea, beneficiario o etiqueta…"
        class="w-full sm:w-72 rounded-md border border-slate-300 p-2 text-sm" />

    <select name="tag" class="w-full sm:w-48 rounded-md border border-slate-300 p-2 text-sm">
        <option value="">Todas las etiquetas</option>
        @foreach (($tags ?? collect()) as $tag)
            <option value="{{ $tag->id }}" @selected((string) request('tag') === (string) $tag->id)>{{ $tag->name }}</option>
        @endforeach
    </select>

    <button class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Buscar</button>
    @if (request()->filled('search') || request()->filled('tag'))
        <a href="{{ $action }}" class="px-4 py-2 border border-slate-300 rounded-md text-sm">Limpiar</a>
    @endif
</form>

@if (request()->filled('search') || request()->filled('tag'))
    <p class="text-sm text-slate-500">
        @if (request()->filled('search'))
            Resultados para «{{ request('search') }}»
        @else
            Resultados
        @endif
        @if (request()->filled('tag'))
            con etiqueta «{{ optional(($tags ?? collect())->firstWhere('id', (int) request('tag')))->name ?? request('tag') }}»
        @endif
        : {{ $ideas->total() }}.
    </p>
@endif
