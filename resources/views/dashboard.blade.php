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

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                <section class="rounded-lg border border-slate-200 bg-white p-4 lg:col-span-2">
                    <div class="mb-4">
                        <h3 class="font-bold text-slate-900">Estado de los proyectos</h3>
                        <p class="text-sm text-slate-500">Distribución global según la etapa de cada proyecto.</p>
                    </div>
                    <x-project-status-chart :segments="$projectStatuses" />
                    <p class="mt-4 text-xs text-slate-500">
                        «Pendientes de aprobación» incluye las ideas publicadas que todavía no iniciaron su desarrollo.
                    </p>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-4">
                    <h3 class="font-bold text-slate-900">Acciones pendientes</h3>
                    <p class="text-sm text-slate-500">Lo que requiere tu revisión ahora.</p>

                    <div class="mt-4 space-y-3">
                        <a href="{{ route('assignments.index', ['status' => 'applied']) }}"
                            class="block rounded-lg border border-slate-200 p-3 transition hover:border-slate-400">
                            <p class="text-sm text-slate-500">Postulaciones por evaluar</p>
                            <p class="text-2xl font-bold text-slate-900">{{ $pendingApplicationsCount }}</p>
                        </a>
                        <a href="{{ route('progress.index') }}"
                            class="block rounded-lg border border-slate-200 p-3 transition hover:border-slate-400">
                            <p class="text-sm text-slate-500">Avances pendientes</p>
                            <p class="text-2xl font-bold text-slate-900">{{ $pendingUpdatesCount }}</p>
                        </a>
                    </div>
                </section>
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

            <section class="rounded-lg border border-slate-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                    <div>
                        <h3 class="font-bold text-slate-900">Postulaciones por evaluar</h3>
                        <p class="text-sm text-slate-500">Solicitudes de alumnos que todavía esperan tu decisión.</p>
                    </div>
                    @can('viewAny', App\Models\Assignment::class)
                    <a href="{{ route('assignments.index', ['status' => 'applied']) }}" class="text-sm text-slate-600 underline">Ver todas</a>
                    @endcan
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2">Alumno</th>
                                <th class="px-4 py-2">Proyecto</th>
                                <th class="px-4 py-2">Fecha de postulación</th>
                                <th class="px-4 py-2 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($pendingApplications as $application)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $application->student->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if ($application->projectIdea)
                                            <a href="{{ route('ideas.show', $application->projectIdea) }}" class="hover:underline">{{ $application->projectIdea->title }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $application->applied_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('assignments.index', ['status' => 'applied']) }}#postulacion-{{ $application->id }}"
                                            class="inline-flex rounded-md bg-slate-900 px-3 py-1.5 text-white hover:bg-slate-800">
                                            Evaluar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">No hay postulaciones pendientes de evaluación.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                    <div>
                        <h3 class="font-bold text-slate-900">Avances enviados</h3>
                        <p class="text-sm text-slate-500">Últimas entregas que esperan revisión.</p>
                    </div>
                    @can('viewAny', App\Models\ProjectUpdate::class)
                    <a href="{{ route('progress.index') }}" class="text-sm text-slate-600 underline">Ver todos</a>
                    @endcan
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2">Alumno</th>
                                <th class="px-4 py-2">Proyecto</th>
                                <th class="px-4 py-2">Fecha de envío</th>
                                <th class="px-4 py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($pendingUpdates as $update)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $update->student->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if ($update->projectIdea)
                                            <a href="{{ route('ideas.show', $update->projectIdea) }}" class="hover:underline">{{ $update->projectIdea->title }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $update->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <a href="{{ route('progress.index') }}#avance-{{ $update->id }}"
                                                class="rounded-md border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
                                                Revisar
                                            </a>
                                            @can('approve', $update)
                                                <form method="POST" action="{{ route('progress.approve', $update) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded-md bg-slate-900 px-3 py-1.5 text-white hover:bg-slate-800">Aprobar</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">No hay avances pendientes de revisión.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
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
