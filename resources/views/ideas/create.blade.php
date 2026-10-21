<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Nueva idea</h2>
        <p class="text-sm text-slate-500">Publica una necesidad o propuesta para que los estudiantes se postulen.</p>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4">
        <form method="POST" action="{{ route('ideas.store') }}" class="bg-white border border-slate-200 rounded-lg p-5 space-y-4">
            @csrf

            <div>
                <x-input-label for="title" :value="__('Título')" />
                <x-text-input id="title" name="title" type="text" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                    :value="old('title')" required autofocus placeholder="Ej. Sistema de reservas para el comedor" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="description" :value="__('Descripción')" />
                <textarea id="description" name="description" rows="6" required
                    class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900 rounded-md shadow-sm text-sm"
                    placeholder="Describe la necesidad, el alcance esperado y cualquier requisito importante.">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <p class="text-xs text-slate-500">La idea se publicará como <b>Disponible</b> para que los estudiantes puedan postularse.</p>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('ideas.index') }}" class="px-4 py-2 border border-slate-300 rounded-md text-sm">Cancelar</a>
                <button class="px-5 py-2 bg-slate-900 text-white text-sm rounded-md">Publicar idea</button>
            </div>
        </form>
    </div>
</x-app-layout>
