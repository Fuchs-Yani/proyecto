<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Alumnos</h2>
            <p class="text-sm text-slate-500">Estudiantes registrados en IDEATÓN.</p>
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto px-4">
        <form method="GET" action="{{ route('professor.students') }}" class="mb-3 flex flex-wrap items-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" spellcheck="false"
                placeholder="Buscar por nombre o email…"
                class="w-full sm:w-80 rounded-md border border-slate-300 p-2 text-sm" />
            <button class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Buscar</button>
            @if (request()->filled('search'))
                <a href="{{ route('professor.students') }}" class="px-4 py-2 border border-slate-300 rounded-md text-sm">Limpiar</a>
            @endif
        </form>

        @if (request()->filled('search'))
            <p class="mb-3 text-sm text-slate-500">Resultados para «{{ request('search') }}»: {{ $students->total() }}.</p>
        @endif

        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3 text-center">Año de carrera</th>
                        <th class="px-4 py-3 text-center">Postulaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $student->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $student->email }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">
                                {{ $student->career_year ? $student->career_year.'° año' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex min-w-8 justify-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $student->assignments_count }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">{{ request()->filled('search') ? 'No se encontraron alumnos con ese criterio.' : 'Todavía no hay alumnos registrados.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $students->links() }}</div>
    </div>
</x-app-layout>
