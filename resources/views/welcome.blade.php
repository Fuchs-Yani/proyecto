<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Banco de Ideas y Proyectos de Software</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    {{-- NAV --}}
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <span class="font-bold text-slate-900">Banco de Ideas</span>
            <nav class="flex gap-3 text-sm">
                @auth
                    <a href="{{ url('/dashboard') }}" class="px-4 py-2 bg-slate-900 text-white rounded-md">Dashboard</a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="px-4 py-2 border border-slate-300 rounded-md">Ingresar</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="px-4 py-2 bg-slate-900 text-white rounded-md">Registrarse</a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    {{-- HERO --}}
    <section class="max-w-6xl mx-auto px-4 pt-10 pb-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-slate-900">"Banco de Ideas y Proyectos de Software"</h1>
        <p class="mt-2 text-slate-600">"Conectando estudiantes, docentes y beneficiarios para desarrollar soluciones tecnológicas reales"</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="#explorar" class="px-5 py-2.5 bg-slate-900 text-white rounded-md text-sm">Explorar Proyectos</a>
        </div>
    </section>

    {{-- STATS --}}
    <section class="max-w-6xl mx-auto px-4">
        <div class="bg-slate-800 text-slate-100 rounded-lg px-6 py-3 flex flex-wrap justify-center gap-x-8 gap-y-1 text-sm">
            <span><b>+30</b> Proyectos Desarrollados</span>
            <span><b>+100</b> Estudiantes Participantes</span>
            <span><b>+15</b> Instituciones Beneficiadas</span>
        </div>
    </section>

    {{-- 4 PILARES --}}
    <section class="max-w-6xl mx-auto px-4 py-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach([
            ['Desarrollo de software', 'Proyectos reales con alcance definido.'],
            ['Colaboración', 'Estudiantes y beneficiarios trabajando juntos.'],
            ['Seguimiento', 'Asignaciones con estados y avances.'],
            ['Documentación', 'Adjuntos y evidencias por proyecto.'],
        ] as [$t, $d])
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="w-8 h-8 rounded bg-slate-900 text-white flex items-center justify-center text-sm">▪</div>
                <h3 class="mt-2 font-semibold text-slate-900 text-sm">{{ $t }}</h3>
                <p class="text-xs text-slate-500 mt-1">{{ $d }}</p>
            </div>
        @endforeach
    </section>

    {{-- COMO FUNCIONA --}}
    <section class="max-w-6xl mx-auto px-4 py-4">
        <h2 class="text-2xl font-bold text-center text-slate-900">¿Cómo funciona?</h2>
        <div class="mt-4 grid md:grid-cols-3 gap-3">
            <div class="bg-white border border-slate-200 rounded-lg p-5 text-center">
                <div class="text-sm font-semibold text-slate-500">Beneficiarios</div>
                <div class="text-3xl mt-1">🏢</div>
                <p class="mt-2 text-sm bg-slate-100 rounded p-3">Publican o proponen una necesidad tecnológica real.</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-5 text-center">
                <div class="text-sm font-semibold text-slate-500">Alumnos</div>
                <div class="text-3xl mt-1">🎓</div>
                <p class="mt-2 text-sm bg-slate-100 rounded p-3">Exploran la lista, eligen el proyecto que más les interese y se postulan.</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-5 text-center">
                <div class="text-sm font-semibold text-slate-500">Profesores</div>
                <div class="text-3xl mt-1">📋</div>
                <p class="mt-2 text-sm bg-slate-100 rounded p-3">Guían la asignación, evalúan el desarrollo y aprueban los avances.</p>
            </div>
        </div>
    </section>

    {{-- EXPLORAR PROYECTOS: terminados --}}
    <section id="explorar" class="max-w-6xl mx-auto px-4 py-6 scroll-mt-20">
        <div class="bg-slate-900 text-white text-center rounded-lg py-2 font-semibold">Explorar Proyectos terminados</div>
        <div class="mt-3 space-y-3">
            @forelse($finished ?? [] as $p)
                <div class="flex bg-white border border-slate-200 rounded-lg overflow-hidden">
                    <div class="flex-1 p-4">
                        <div class="italic text-slate-900">{{ $p->title }}</div>
                        <div class="text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($p->description, 90) }}</div>
                        <div class="text-xs text-slate-400 mt-1">Por {{ $p->organizer->name ?? '' }} · Terminado</div>
                    </div>
                    <div class="w-40 md:w-64 bg-slate-200 flex items-center justify-center text-slate-400 text-xs">Terminado ✓</div>
                </div>
            @empty
                <p class="text-sm text-slate-500 bg-white border rounded p-4 text-center">Aún no hay proyectos terminados. <a href="{{ route('ideas.index') }}" class="underline text-slate-900">Ver ideas disponibles</a></p>
            @endforelse
            <div class="text-center"><a href="{{ route('ideas.index') }}" class="text-sm underline text-slate-700">Ver todas las ideas disponibles →</a></div>
        </div>
    </section>

    <footer class="bg-slate-900 text-slate-200 text-center text-sm py-3 mt-6">
        Instituto Sedes Sapientiae 2026
    </footer>
</body>
</html>
