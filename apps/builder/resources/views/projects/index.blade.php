<x-projects.layout title="Proyectos">
    <h1>Proyectos</h1>
    <p class="mut">
        Organización: <strong>{{ $organization->name }}</strong>
        · consumo de IA acumulado: <strong>US$ {{ number_format($aiCostUsd, 4) }}</strong>
        (se mide en tokens; el cliente ve créditos)
    </p>

    @forelse($projects as $project)
        <div class="card">
            <strong><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></strong>
            <div class="mut">{{ $project->status }} @if($project->site) · sitio: {{ $project->site->name }} @endif</div>
        </div>
    @empty
        <p class="mut">Sin proyectos todavía.</p>
    @endforelse

    <form method="post" action="{{ route('projects.store') }}" style="margin-top:1rem">
        @csrf
        <input type="text" name="name" placeholder="Nombre del proyecto" required>
        <button class="btn">Crear proyecto</button>
    </form>
</x-projects.layout>
