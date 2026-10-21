<nav x-data="{ open: false }" class="bg-white border-b border-slate-200">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-6">
                <a href="/" class="font-bold text-slate-900">Banco de Ideas</a>
                <div class="hidden sm:flex gap-4 text-sm">
                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    @endauth
                    <x-nav-link :href="route('ideas.index')" :active="request()->routeIs('ideas.*')">
                        {{ auth()->check() ? 'Ideas' : 'Proyectos' }}
                    </x-nav-link>
                    @auth
                        @can('viewAny', App\Models\Assignment::class)
                        <x-nav-link :href="route('assignments.index')" :active="request()->routeIs('assignments.*')">Postulaciones</x-nav-link>
                        @endcan
                    @endauth
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-3">
                @auth
                    <span class="text-xs bg-slate-100 border border-slate-200 rounded px-2 py-1">{{ auth()->user()->getRoleNames()->join(', ') }}</span>
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="text-sm text-slate-600">{{ Auth::user()->name }}</button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-dropdown-link>
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
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
        @endauth
        <x-responsive-nav-link :href="route('ideas.index')" :active="request()->routeIs('ideas.*')">
            {{ auth()->check() ? 'Ideas' : 'Proyectos terminados' }}
        </x-responsive-nav-link>
        @auth
            @can('viewAny', App\Models\Assignment::class)
            <x-responsive-nav-link :href="route('assignments.index')" :active="request()->routeIs('assignments.*')">Postulaciones</x-responsive-nav-link>
            @endcan
        @else
            <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">Ingresar</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">Registrarse</x-responsive-nav-link>
        @endauth
    </div>
</nav>
