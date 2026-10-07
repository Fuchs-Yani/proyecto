<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Dashboard · {{ auth()->user()->getRoleNames()->join(', ') }}</h2>
    </x-slot>
    <div class="py-6 max-w-6xl mx-auto px-4 space-y-3">
        <div class="bg-slate-800 text-slate-100 rounded-lg px-6 py-3 text-sm text-center">Banco de Ideas y Proyectos de Software</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <a href="{{ route('ideas.index') }}" class="bg-white border border-slate-200 rounded-lg p-4"><div class="font-bold text-slate-900">Ideas</div><div class="text-sm text-slate-500">Listar y ver detalle. Todos los roles.</div></a>
            @can('viewAny', App\Models\Assignment::class)
            <a href="{{ route('assignments.index') }}" class="bg-white border border-slate-200 rounded-lg p-4"><div class="font-bold text-slate-900">Postulaciones</div><div class="text-sm text-slate-500">@role('student') Mis postulaciones @elserole('beneficiario') Recibidas en mis ideas @else Todas @endrole</div></a>
            @endcan
            <a href="/admin" class="bg-white border border-slate-200 rounded-lg p-4"><div class="font-bold text-slate-900">Admin Filament</div><div class="text-sm text-slate-500">Según permisos Shield.</div></a>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4 text-sm">
            <b>Qué ve cada rol:</b>
            <ul class="list-disc ml-5 text-slate-600">
                <li><b>student:</b> ideas available, postularse/retirar, descargar adjuntos.</li>
                <li><b>beneficiario:</b> ideas, postulaciones de sus ideas, subir/borrar adjuntos propios.</li>
                <li><b>admin:</b> todo + Filament.</li>
            </ul>
        </div>
    </div>
</x-app-layout>
