<section class="space-y-4">
    <header>
        <h3 class="text-base font-bold text-red-700">Eliminar cuenta</h3>
        <p class="mt-1 text-sm text-slate-500">
            Al eliminar tu cuenta se borran tus datos y proyectos. Esta acción no se puede deshacer.
        </p>
    </header>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50"
    >
        Eliminar cuenta
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-slate-900">¿Seguro que querés eliminar tu cuenta?</h2>

            <p class="mt-2 text-sm text-slate-500">
                Se borrarán todos tus datos de forma permanente. Ingresá tu contraseña para confirmar.
            </p>

            <div class="mt-5">
                <x-input-label for="password" value="{{ __('Contraseña') }}" class="sr-only" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                    placeholder="{{ __('Contraseña') }}"
                    autocomplete="current-password"
                    required
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50"
                >
                    Cancelar
                </button>

                <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">
                    Eliminar cuenta
                </button>
            </div>
        </form>
    </x-modal>
</section>
