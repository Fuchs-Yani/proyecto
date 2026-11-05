<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Alumnos</h2>
            <p class="text-sm text-slate-500">Estudiantes registrados en el banco de ideas y proyectos.</p>
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto px-4">
        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Institución</th>
                        <th class="px-4 py-3 text-center">Postulaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $student->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $student->email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $student->institution_name ?: '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex min-w-8 justify-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $student->assignments_count }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">Todavía no hay alumnos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $students->links() }}</div>
    </div>
</x-app-layout>
