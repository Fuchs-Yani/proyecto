<x-app-layout>
    <x-slot name="header">
        @if (auth()->user()->hasAnyRole(['student', 'profesor']))
            <h2 class="text-xl font-bold text-slate-900">Dashboard</h2>
        @elseif (auth()->user()->hasRole('beneficiario'))
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-bold text-slate-900">Dashboard</h2>
                @can('create', App\Models\ProjectIdea::class)
                    <a href="{{ route('ideas.create') }}" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm whitespace-nowrap">Nueva idea</a>
                @endcan
            </div>
        @else
            <h2 class="text-xl font-bold text-slate-900">Dashboard · {{ auth()->user()->roleLabel() }}</h2>
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
        @elseif (auth()->user()->hasAnyRole(['profesor', 'beneficiario']))
            {{-- Profesor y beneficiario navegan desde el menú superior; el dashboard no muestra módulos. --}}
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
                    <div class="font-bold text-slate-900">Admin Filament</div>
                    <div class="text-sm text-slate-500">Panel de administración.</div>
                </a>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4 text-sm">
                <b>Qué ve cada rol:</b>
                <ul class="list-disc ml-5 text-slate-600">
                    <li><b>student:</b> ideas disponibles, postularse/retirar, descargar adjuntos.</li>
                    <li><b>beneficiario:</b> ideas, postulaciones de sus ideas, subir/borrar adjuntos propios.</li>
                    <li><b>profesor:</b> ideas, adjuntos y seguimiento de postulaciones.</li>
                    <li><b>admin:</b> todo + panel Filament.</li>
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
