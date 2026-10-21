<x-guest-layout>
    <h1 class="text-xl font-bold text-slate-900 text-center">Registrarse</h1>
    <p class="text-sm text-slate-500 text-center mt-1 mb-4">Banco de Ideas y Proyectos de Software</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Nombre y Apellido -->
        <div>
            <x-input-label for="name" :value="__('Nombre y Apellido')" />
            <x-text-input id="name" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Ej. Nombre y Apellido" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Correo Electrónico')" />
            <x-text-input id="email" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Selección de Rol -->
        <div class="mt-4">
            <x-input-label for="role" :value="__('¿Cómo vas a participar?')" />
            <select id="role" name="role" onchange="toggleInstitutionField()" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900 rounded-md shadow-sm text-sm text-slate-700" required>
                <option value="" disabled {{ old('role') ? '' : 'selected' }}>Selecciona tu perfil</option>
                <option value="beneficiario" {{ old('role') == 'beneficiario' ? 'selected' : '' }}>Beneficiario</option>
                <option value="profesor" {{ old('role') == 'profesor' ? 'selected' : '' }}>Profesor</option>
                <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>Estudiante</option>
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <!-- Nombre de la Institución (Opcional solo para Beneficiarios) -->
        <div class="mt-4" id="institution_container" style="display: {{ old('role') === 'beneficiario' ? 'block' : 'none' }};">
            <x-input-label for="institution_name" :value="__('Institución u organización (opcional, solo beneficiarios)')" />
            <x-text-input id="institution_name" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="text" name="institution_name" :value="old('institution_name')" placeholder="Ej. Nombre de la institución" />
            <x-input-error :messages="$errors->get('institution_name')" class="mt-2" />
        </div>

        <!-- Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirmar Contraseña -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-slate-500 hover:text-slate-900" href="{{ route('login') }}">{{ __('¿Ya tienes cuenta?') }}</a>
            <button class="px-5 py-2 bg-slate-900 text-white text-sm rounded-md">{{ __('Crear cuenta') }}</button>
        </div>

        <p class="mt-4 text-center text-sm text-slate-500"><a href="{{ route('ideas.index') }}" class="underline">Explorar proyectos</a> sin cuenta</p>
    </form>

    <!-- Script JavaScript para mostrar/ocultar el input de institución -->
    <script>
        function toggleInstitutionField() {
            const roleSelect = document.getElementById('role');
            const institutionContainer = document.getElementById('institution_container');
            
            if (roleSelect.value === 'beneficiario') {
                institutionContainer.style.display = 'block';
            } else {
                institutionContainer.style.display = 'none';
                document.getElementById('institution_name').value = ''; // Limpia el campo si cambia de opción
            }
        }
    </script>
</x-guest-layout>
