<x-app-layout>
    <x-slot name="header">{{ $projectIdea->title }}</x-slot>
    <p>{{ $projectIdea->description }}</p>
    <p>Organizador: {{ $projectIdea->organizer->name ?? '' }}</p>
    @if(!$alreadyApplied)
        <form method="POST" action="{{ route('assignments.store', $projectIdea) }}">@csrf<button>Postularme</button></form>
    @else
        <p>Ya postulado.</p>
    @endif
    <h3>Adjuntos</h3>
    @foreach($projectIdea->attachments as $a)
        <div>{{ $a->file_name }} - <a href="{{ route('attachments.download', $a) }}">Descargar</a></div>
    @endforeach
</x-app-layout>
