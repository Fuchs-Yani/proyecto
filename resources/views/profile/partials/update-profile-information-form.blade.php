<section>
    <header>
        <h3 class="text-base font-bold text-slate-900">Datos personales</h3>
        <p class="mt-1 text-sm text-slate-500">Actualizá tu nombre, correo y foto de perfil.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-5 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label :value="__('Foto de perfil')" />
            <div class="mt-2 flex flex-wrap items-center gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <x-avatar :user="$user" size="h-16 w-16" />

                <div class="min-w-0 flex-1 space-y-2">
                    <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" class="peer sr-only">
                    <div class="flex flex-wrap items-center gap-3">
                        <label for="profile_photo" class="inline-flex cursor-pointer items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 peer-focus-visible:ring-2 peer-focus-visible:ring-slate-400 peer-focus-visible:ring-offset-2">
                            {{ $user->profile_photo_path ? 'Cambiar foto' : 'Subir foto' }}
                        </label>
                        <span id="profile_photo_name" class="text-sm text-slate-600">Ningún archivo seleccionado</span>
                    </div>
                    <p class="text-xs text-slate-500">JPG, PNG, WEBP o GIF · máximo 2 MB. Es opcional.</p>

                    @if ($user->profile_photo_path)
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remove_profile_photo" value="1" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                            Quitar foto actual
                        </label>
                    @endif

                    <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('Nombre y apellido')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Correo electrónico')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p>
                    Tu correo electrónico no está verificado.
                    <button form="send-verification" class="font-semibold underline underline-offset-2 hover:text-amber-950">
                        Reenviar correo de verificación
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-semibold">Se envió un nuevo enlace de verificación.</p>
                @endif
            </div>
        @endif

        @if ($user->hasRole('beneficiario') || filled($user->institution_name))
            <div>
                <x-input-label for="institution_name" :value="__('Institución u organización')" />
                <x-text-input id="institution_name" name="institution_name" type="text" class="mt-1 block w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" :value="old('institution_name', $user->institution_name)" autocomplete="organization" placeholder="Ej. Nombre de la institución" />
                <p class="mt-1 text-xs text-slate-500">Nombre de la institución u organización que representás.</p>
                <x-input-error class="mt-2" :messages="$errors->get('institution_name')" />
            </div>
        @endif

        @if ($user->hasRole('student'))
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Año de la carrera</p>
                <p class="mt-1 text-sm font-medium text-slate-900">
                    {{ $user->career_year ? $user->career_year.'° año' : 'Sin definir' }}
                </p>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-4">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                Guardar cambios
            </button>
        </div>
    </form>
</section>

<script>
    document.getElementById('profile_photo')?.addEventListener('change', function () {
        const output = document.getElementById('profile_photo_name');

        if (! output) {
            return;
        }

        output.textContent = this.files.length
            ? Array.from(this.files).map(file => file.name).join(', ')
            : 'Ningún archivo seleccionado';
    });
</script>
