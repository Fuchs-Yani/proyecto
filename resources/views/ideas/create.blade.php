<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Nueva idea</h2>
        <p class="text-sm text-slate-500">Publica una necesidad o propuesta para que los estudiantes se postulen.</p>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4">
        <form method="POST" action="{{ route('ideas.store') }}" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-lg p-5 space-y-4">
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
                    placeholder="Contanos con tus palabras qué necesitás, para qué lo usarías y cómo te gustaría que funcione.">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="attachments" :value="__('Documentos e imágenes de apoyo (opcional)')" />
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <input id="attachments" name="attachments[]" type="file" multiple
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.gif" class="peer sr-only" />
                    <label for="attachments" class="inline-flex cursor-pointer items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 peer-focus-visible:ring-2 peer-focus-visible:ring-slate-400 peer-focus-visible:ring-offset-2">
                        Seleccionar archivos
                    </label>
                    <span id="attachments-file-name" class="text-sm text-slate-600">Ningún archivo seleccionado</span>
                </div>
                <p class="text-xs text-slate-500 mt-2">Podés adjuntar PDFs o imágenes (JPG, PNG, WEBP o GIF) que ayuden a entender mejor tu idea. Máximo 10 MB por archivo.</p>
                @foreach ($errors->getMessages() as $key => $messages)
                    @if (str_starts_with($key, 'attachments'))
                        @foreach ($messages as $message)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @endforeach
                    @endif
                @endforeach
            </div>

            <p class="text-xs text-slate-500">La idea se publicará como <b>Disponible</b> para que los estudiantes puedan postularse.</p>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('ideas.index') }}" class="px-4 py-2 border border-slate-300 rounded-md text-sm">Cancelar</a>
                <button class="px-5 py-2 bg-slate-900 text-white text-sm rounded-md">Publicar idea</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('attachments')?.addEventListener('change', function () {
            const output = document.getElementById('attachments-file-name');

            if (!output) {
                return;
            }

            if (!this.files.length) {
                output.textContent = 'Ningún archivo seleccionado';
                return;
            }

            output.textContent = Array.from(this.files).map(file => file.name).join(', ');
        });
    </script>
</x-app-layout>
