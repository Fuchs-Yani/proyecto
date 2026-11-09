{{-- Sidebar exclusivo para el rol profesor. El estado `open` vive en layouts/app.blade.php --}}
<div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden"></div>

<aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0"
    :class="open ? 'translate-x-0' : ''">
    <div class="flex h-16 items-center justify-between border-b border-slate-100 px-5">
        <a href="/" class="text-xl font-bold text-slate-900">Banco de Ideas</a>
        <button type="button" @click="open = false" class="rounded p-1 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Cerrar menú">✕</button>
    </div>

    <div class="flex items-center gap-3 px-5 py-6">
        @if (auth()->user()->profilePhotoUrl())
            <x-avatar :user="auth()->user()" size="h-14 w-14" />
        @else
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7">
                    <path d="M11.7 2.805a.75.75 0 0 1 .6 0A60.65 60.65 0 0 1 22.83 8.72a.75.75 0 0 1-.231 1.337c-2.396.836-4.735 1.809-6.995 2.905a2.25 2.25 0 0 1-1.99 0C11.9 11.78 9.56 10.807 7.165 9.97a.75.75 0 0 1-.231-1.337A60.65 60.65 0 0 1 11.7 2.805Z" />
                    <path d="M13.06 15.473a48.45 48.45 0 0 1 7.666-3.282c.134 1.414.22 2.843.255 4.285a.75.75 0 0 1-.46.71 47.87 47.87 0 0 0-8.105 4.342.75.75 0 0 1-.832 0 47.87 47.87 0 0 0-8.104-4.342.75.75 0 0 1-.461-.71c.035-1.442.121-2.871.255-4.285A48.45 48.45 0 0 1 10.94 15.473a48.45 48.45 0 0 1 1.06-.378.75.75 0 0 1 1.06.378Z" />
                </svg>
            </div>
        @endif
        <div class="min-w-0">
            <p class="font-bold text-slate-900">Vista Profesor</p>
            <p class="truncate text-sm text-slate-600">{{ auth()->user()->name }}</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4">
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ request()->routeIs('dashboard') ? 'bg-blue-100 font-semibold text-blue-900' : 'text-slate-700 hover:bg-slate-100' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            Panel
        </a>

        @hasanyrole('profesor|admin')
        <a href="{{ route('professor.students') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ request()->routeIs('professor.students') ? 'bg-blue-100 font-semibold text-blue-900' : 'text-slate-700 hover:bg-slate-100' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
            Alumnos
        </a>
        @endhasanyrole

        @can('viewAny', App\Models\Assignment::class)
        <a href="{{ route('assignments.index') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ request()->routeIs('assignments.*') ? 'bg-blue-100 font-semibold text-blue-900' : 'text-slate-700 hover:bg-slate-100' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            Postulaciones
        </a>
        @endcan

        @can('viewAny', App\Models\ProjectUpdate::class)
        <a href="{{ route('progress.index') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ request()->routeIs('progress.*') ? 'bg-blue-100 font-semibold text-blue-900' : 'text-slate-700 hover:bg-slate-100' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            </svg>
            Avances
        </a>
        @endcan

        <x-notification-bell :show-label="true" class="w-full px-3 py-2.5" />

        <a href="{{ route('ideas.index') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ request()->routeIs('ideas.*') ? 'bg-blue-100 font-semibold text-blue-900' : 'text-slate-700 hover:bg-slate-100' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
            </svg>
            Proyectos
        </a>
    </nav>

    <div class="space-y-1 border-t border-slate-100 p-3">
        <a href="{{ route('profile.edit') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
            Editar perfil
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                </svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>
