<x-guest-layout>
    <h1 class="text-xl font-bold text-slate-900 text-center">Ingresar</h1>
    <p class="text-sm text-slate-500 text-center mt-1 mb-4">Banco de Ideas y Proyectos de Software</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('Remember me') }}</span>
            </label>
        </div>
        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-slate-500 hover:text-slate-900" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif
            <button class="px-5 py-2 bg-slate-900 text-white text-sm rounded-md">{{ __('Log in') }}</button>
        </div>
        <p class="mt-4 text-center text-sm text-slate-500">¿No tienes cuenta? <a href="{{ route('register') }}" class="underline text-slate-900">Registrarse</a> · <a href="{{ route('ideas.index') }}" class="underline">Explorar proyectos</a></p>
    </form>
</x-guest-layout>
