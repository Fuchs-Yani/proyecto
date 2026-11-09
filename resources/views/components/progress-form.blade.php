@props(['assignment'])

@php
    $hasErrors = $errors->hasAny(['stage', 'description', 'repository_url', 'images', 'images.*']);
    $stages = \App\Models\ProjectUpdate::STAGES;
@endphp

<div x-data="{ open: @js($hasErrors) }" class="space-y-2">
    <button type="button" @click="open = ! open"
        class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Agregar avance
    </button>

    <form x-show="open" x-cloak method="POST" action="{{ route('progress.store', $assignment) }}"
        enctype="multipart/form-data" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
        @csrf

        <div>
            <label for="stage-{{ $assignment->id }}" class="block text-sm font-medium text-slate-700">
                Etapa del proyecto
            </label>
            <select id="stage-{{ $assignment->id }}" name="stage" required
                class="mt-1 block w-full rounded-md border border-slate-300 p-2 text-sm">
                @foreach ($stages as $value => $label)
                    <option value="{{ $value }}" @selected(old('stage', \App\Models\ProjectUpdate::STAGE_ANALYSIS) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('stage')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description-{{ $assignment->id }}" class="block text-sm font-medium text-slate-700">
                Descripción del avance
            </label>
            <textarea id="description-{{ $assignment->id }}" name="description" rows="4" required spellcheck="false"
                placeholder="Contanos en lenguaje natural qué avanzaste, qué dificultades encontraste y qué sigue…"
                class="mt-1 block w-full rounded-md border border-slate-300 p-2 text-sm">{{ old('description') }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="repository-url-{{ $assignment->id }}" class="block text-sm font-medium text-slate-700">
                URL del repositorio <span class="font-normal text-slate-500">(opcional)</span>
            </label>
            <input id="repository-url-{{ $assignment->id }}" name="repository_url" type="url" spellcheck="false"
                value="{{ old('repository_url') }}" placeholder="https://github.com/usuario/repositorio"
                class="mt-1 block w-full rounded-md border border-slate-300 p-2 text-sm">
            @error('repository_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div x-data="{ fileName: 'Ningún archivo seleccionado' }">
            <span class="block text-sm font-medium text-slate-700">
                Imágenes <span class="font-normal text-slate-500">(opcional)</span>
            </span>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <input id="progress-images-{{ $assignment->id }}" name="images[]" type="file" multiple
                    accept=".jpg,.jpeg,.png,.webp,.gif" class="peer sr-only"
                    x-on:change="fileName = $event.target.files.length
                        ? Array.from($event.target.files).map(file => file.name).join(', ')
                        : 'Ningún archivo seleccionado'">
                <label for="progress-images-{{ $assignment->id }}"
                    class="inline-flex cursor-pointer items-center rounded-md border border-slate-300 bg-white px-4 py-1.5 text-sm transition hover:bg-slate-50 peer-focus-visible:ring-2 peer-focus-visible:ring-slate-400 peer-focus-visible:ring-offset-2">
                    Seleccionar imágenes
                </label>
                <span class="text-sm text-slate-600" x-text="fileName">Ningún archivo seleccionado</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                JPG, PNG, WEBP o GIF · máximo {{ \App\Models\ProjectUpdate::MAX_IMAGES }} imágenes de 5 MB cada una.
            </p>
            @error('images')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('images.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                Enviar avance
            </button>
            <button type="button" @click="open = false"
                class="rounded-md border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
        </div>

        <p class="text-xs text-slate-500">
            El avance quedará pendiente hasta que el profesor lo apruebe.
        </p>
    </form>
</div>
