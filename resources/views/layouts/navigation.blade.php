<nav x-data="{ open: false }" class="bg-white border-b border-slate-200">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-6">
                <a href="/" class="font-bold text-slate-900">IDEATÓN</a>
                <div class="hidden sm:flex gap-4 text-sm">
                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Panel</x-nav-link>
                    @endauth
                    <x-nav-link :href="route('ideas.index')" :active="request()->routeIs('ideas.index', 'ideas.show', 'ideas.create')">
                        {{ auth()->check() ? 'Ideas' : 'Proyectos' }}
                    </x-nav-link>
                    @auth
                        @role('beneficiario')
                        <x-nav-link :href="route('ideas.mine')" :active="request()->routeIs('ideas.mine')">Mis ideas</x-nav-link>
                        @endrole
                        @hasanyrole('profesor|admin')
                        <x-nav-link :href="route('professor.students')" :active="request()->routeIs('professor.students')">Alumnos</x-nav-link>
                        @endhasanyrole
                        @can('viewAny', App\Models\Assignment::class)
                        <x-nav-link :href="route('assignments.index')" :active="request()->routeIs('assignments.*')">Postulaciones</x-nav-link>
                        @endcan
                        @can('viewAny', App\Models\ProjectUpdate::class)
                        <x-nav-link :href="route('progress.index')" :active="request()->routeIs('progress.*')">Avances</x-nav-link>
                        @endcan
                    @endauth
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-3">
                @auth
                    <x-notification-bell class="p-2" />
                    <span class="text-xs bg-slate-100 border border-slate-200 rounded px-2 py-1">{{ auth()->user()->roleLabel() }}</span>
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 rounded-md px-2 py-1 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                <x-avatar :user="Auth::user()" size="h-8 w-8" />
                                <span class="hidden md:inline">{{ Auth::user()->name }}</span>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">Editar perfil</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="px-3 py-1.5 border border-slate-300 rounded-md text-sm">Ingresar</a>
                    <a href="{{ route('register') }}" class="px-3 py-1.5 bg-slate-900 text-white rounded-md text-sm">Registrarse</a>
                @endauth
            </div>
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="p-2 text-slate-500">☰</button>
            </div>
        </div>
    </div>
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-200 px-4 py-2 space-y-1 text-sm">
        @auth
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Panel</x-responsive-nav-link>
        @endauth
        <x-responsive-nav-link :href="route('ideas.index')" :active="request()->routeIs('ideas.index', 'ideas.show', 'ideas.create')">
            {{ auth()->check() ? 'Ideas' : 'Proyectos terminados' }}
        </x-responsive-nav-link>
        @auth
            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                Notificaciones
                @if (auth()->user()->unreadNotifications()->count() > 0)
                    ({{ auth()->user()->unreadNotifications()->count() }})
                @endif
            </x-responsive-nav-link>
            @role('beneficiario')
            <x-responsive-nav-link :href="route('ideas.mine')" :active="request()->routeIs('ideas.mine')">Mis ideas</x-responsive-nav-link>
            @endrole
            @hasanyrole('profesor|admin')
            <x-responsive-nav-link :href="route('professor.students')" :active="request()->routeIs('professor.students')">Alumnos</x-responsive-nav-link>
            @endhasanyrole
            @can('viewAny', App\Models\Assignment::class)
            <x-responsive-nav-link :href="route('assignments.index')" :active="request()->routeIs('assignments.*')">Postulaciones</x-responsive-nav-link>
            @endcan
            @can('viewAny', App\Models\ProjectUpdate::class)
            <x-responsive-nav-link :href="route('progress.index')" :active="request()->routeIs('progress.*')">Avances</x-responsive-nav-link>
            @endcan
        @else
            <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">Ingresar</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">Registrarse</x-responsive-nav-link>
        @endauth
    </div>
</nav>
