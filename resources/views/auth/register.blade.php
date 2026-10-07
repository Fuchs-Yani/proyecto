<x-guest-layout>
    <h1 class="text-xl font-bold text-slate-900 text-center">Registrarse</h1>
    <p class="text-sm text-slate-500 text-center mt-1 mb-4">Banco de Ideas y Proyectos de Software</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-slate-500 hover:text-slate-900" href="{{ route('login') }}">{{ __('Already registered?') }}</a>
            <button class="px-5 py-2 bg-slate-900 text-white text-sm rounded-md">{{ __('Register') }}</button>
        </div>
        <p class="mt-4 text-center text-sm text-slate-500"><a href="{{ route('ideas.index') }}" class="underline">Explorar proyectos</a> sin cuenta</p>
    </form>
</x-guest-layout>
