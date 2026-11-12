<section>
    <header>
        <h3 class="text-base font-bold text-slate-900">Cambiar contraseña</h3>
        <p class="mt-1 text-sm text-slate-500">Usá una contraseña larga y difícil de adivinar.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Contraseña actual')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="update_password_password" :value="__('Nueva contraseña')" />
                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password_confirmation" :value="__('Confirmar nueva contraseña')" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-4">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                Actualizar contraseña
            </button>
        </div>
    </form>
</section>
