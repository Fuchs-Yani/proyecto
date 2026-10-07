<x-app-layout>
    <x-slot name="header">Ideas disponibles</x-slot>
    @foreach($ideas as $idea)
        <div><a href="{{ route('ideas.show', $idea) }}">{{ $idea->title }}</a> - {{ $idea->organizer->name ?? '' }}</div>
    @endforeach
    {{ $ideas->links() }}
</x-app-layout>
