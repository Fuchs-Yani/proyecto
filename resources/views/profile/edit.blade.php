<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Mi perfil</h2>
            <p class="text-sm text-slate-500">Actualizá tus datos personales, tu foto y tu contraseña.</p>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 space-y-5">
        @if (session('status') === 'profile-updated')
            <x-flash-toast message="Perfil actualizado correctamente." />
        @elseif (session('status') === 'password-updated')
            <x-flash-toast message="Contraseña actualizada correctamente." />
        @endif

        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 pb-5">
                <x-avatar :user="$user" size="h-20 w-20" />

                <div class="min-w-0">
                    <p class="truncate text-lg font-bold text-slate-900">{{ $user->name }}</p>
                    <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>

                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        @if (filled($user->roleLabel()))
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 font-semibold text-slate-700">
                                {{ $user->roleLabel() }}
                            </span>
                        @endif

                        @if ($user->career_year)
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 font-semibold text-slate-700">
                                {{ $user->career_year }}° año
                            </span>
                        @endif

                        @if (filled($user->institution_name))
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 font-semibold text-slate-700">
                                {{ $user->institution_name }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-5">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5">
            @include('profile.partials.update-password-form')
        </section>

        <section class="rounded-lg border border-red-200 bg-white p-5">
            @include('profile.partials.delete-user-form')
        </section>
    </div>
</x-app-layout>
