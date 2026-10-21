<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Dashboard · {{ auth()->user()->getRoleNames()->join(', ') }}</h2>
    </x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        <div class="bg-slate-800 text-slate-100 rounded-lg px-6 py-3 text-sm text-center">Banco de Ideas y Proyectos de Software</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <a href="{{ route('ideas.index') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                <div class="font-bold text-slate-900">Ideas</div>
                <div class="text-sm text-slate-500">Lista y revisa el detalle de las ideas y proyectos.</div>
            </a>
            @role('beneficiario')
            <a href="{{ route('ideas.create') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                <div class="font-bold text-slate-900">Nueva idea</div>
                <div class="text-sm text-slate-500">Publica una necesidad o propuesta para los estudiantes.</div>
            </a>
            @endrole
            @can('viewAny', App\Models\Assignment::class)
            <a href="{{ route('assignments.index') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                <div class="font-bold text-slate-900">Postulaciones</div>
                <div class="text-sm text-slate-500">@role('student') Revisa el estado de tus postulaciones @elserole('beneficiario') Postulaciones recibidas en tus ideas @else Todas las postulaciones @endrole</div>
            </a>
            @endcan
            <a href="{{ route('profile.edit') }}" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                <div class="font-bold text-slate-900">Editar perfil</div>
                <div class="text-sm text-slate-500">Actualiza tus datos y tu contraseña.</div>
            </a>
            @role('admin')
            <a href="/admin" class="bg-white border border-slate-200 rounded-lg p-4 hover:border-slate-400">
                <div class="font-bold text-slate-900">Admin Filament</div>
                <div class="text-sm text-slate-500">Panel de administración.</div>
            </a>
            @endrole
        </div>
        @role('admin')
        <div class="bg-white border border-slate-200 rounded-lg p-4 text-sm">
            <b>Qué ve cada rol:</b>
            <ul class="list-disc ml-5 text-slate-600">
                <li><b>student:</b> ideas disponibles, postularse/retirar, descargar adjuntos.</li>
                <li><b>beneficiario:</b> ideas, postulaciones de sus ideas, subir/borrar adjuntos propios.</li>
                <li><b>profesor:</b> ideas, adjuntos y seguimiento de postulaciones.</li>
                <li><b>admin:</b> todo + panel Filament.</li>
            </ul>
        </div>
        @endrole
    </div>
</x-app-layout>
