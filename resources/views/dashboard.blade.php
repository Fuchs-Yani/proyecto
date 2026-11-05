<x-app-layout>
    <x-slot name="header">
        @if (auth()->user()->hasAnyRole(['student', 'profesor']))
            <h2 class="text-xl font-bold text-slate-900">Panel</h2>
        @elseif (auth()->user()->hasRole('beneficiario'))
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-bold text-slate-900">Panel</h2>
                <div class="flex items-center gap-2">
                    <a href="{{ route('ideas.mine') }}" class="px-4 py-2 border border-slate-300 rounded-md text-sm whitespace-nowrap">Mis ideas</a>
                    @can('create', App\Models\ProjectIdea::class)
                        <a href="{{ route('ideas.create') }}" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm whitespace-nowrap">Nueva idea</a>
                    @endcan
                </div>
            </div>
        @else
            <h2 class="text-xl font-bold text-slate-900">Panel · {{ auth()->user()->roleLabel() }}</h2>
        @endif
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        @if (auth()->user()->hasRole('student'))
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-900">Ideas publicadas</h3>
                    <p class="text-sm text-slate-500">Explora las ideas disponibles y postulate a la que te interese.</p>
                </div>
            </div>

            @include('ideas._list', ['ideas' => $ideas])
        @elseif (auth()->user()->hasRole('profesor'))
            <div>
                <h2 class="text-xl font-bold text-slate-900">Hola, {{ auth()->user()->name }}</h2>
                <p class="text-sm text-slate-500">Este es el resumen del banco de ideas y proyectos.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <a href="{{ route('professor.students') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                    <p class="text-sm text-slate-500">Alumnos</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['students'] }}</p>
                </a>
                @can('viewAny', App\Models\Assignment::class)
                <a href="{{ route('assignments.index') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                    <p class="text-sm text-slate-500">Postulaciones</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['applications'] }}</p>
                </a>
                @endcan
                <a href="{{ route('ideas.index') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                    <p class="text-sm text-slate-500">Proyectos</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['ideas'] }}</p>
                </a>
                <a href="{{ route('ideas.index') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                    <p class="text-sm text-slate-500">Ideas disponibles</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['available'] }}</p>
                </a>
            </div>
        @elseif (auth()->user()->hasRole('beneficiario'))
            {{-- El beneficiario navega desde el menú superior; el dashboard no muestra módulos. --}}
        @elseif (auth()->user()->hasRole('admin'))
            <div class="bg-slate-800 text-slate-100 rounded-lg px-6 py-3 text-sm text-center">Banco de Ideas y Proyectos de Software</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <a href="{{ route('ideas.index') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                    <div class="font-bold text-slate-900">Ideas</div>
                    <div class="text-sm text-slate-500">Lista y revisa el detalle de las ideas y proyectos.</div>
                </a>
                @can('viewAny', App\Models\Assignment::class)
                <a href="{{ route('assignments.index') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                    <div class="font-bold text-slate-900">Postulaciones</div>
                    <div class="text-sm text-slate-500">Todas las postulaciones</div>
                </a>
                @endcan
                <a href="{{ route('profile.edit') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                    <div class="font-bold text-slate-900">Editar perfil</div>
                    <div class="text-sm text-slate-500">Actualiza tus datos y tu contraseña.</div>
                </a>
                <a href="/admin" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                    <div class="font-bold text-slate-900">Administración</div>
                    <div class="text-sm text-slate-500">Accedé al panel de administración.</div>
                </a>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4 text-sm">
                <b>Qué ve cada rol:</b>
                <ul class="list-disc ml-5 text-slate-600">
                    <li><b>Estudiante:</b> ideas disponibles, postularse/retirar, descargar adjuntos.</li>
                    <li><b>Beneficiario:</b> ideas, postulaciones de sus ideas, subir/borrar adjuntos propios.</li>
                    <li><b>Profesor:</b> ideas, adjuntos y seguimiento de postulaciones.</li>
                    <li><b>Administrador:</b> todo + panel de administración.</li>
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
