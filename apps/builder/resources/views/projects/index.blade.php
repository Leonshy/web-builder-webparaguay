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
            <div class="mut">{{ $project->status }} @if($project->site) · sitio: {{ $project->site->name }} @endif
                @if($project->status === 'published' && $project->site?->cmsUrl())
                    · <a href="{{ $project->site->cmsUrl() }}" target="_blank">Gestionar mi sitio ↗</a>
                @endif
            </div>
        </div>
    @empty
        <p class="mut">Sin proyectos todavía.</p>
    @endforelse

    @if($errors->any())
        <p class="mut" style="color:#c33">{{ $errors->first() }}</p>
    @endif

    @if($canStart)
        <form method="post" action="{{ route('projects.store') }}" style="margin-top:1rem">
            @csrf
            <input type="text" name="name" placeholder="Nombre del proyecto" required>
            <button class="btn">Crear proyecto</button>
        </form>
    @else
        <p class="mut" style="margin-top:1rem">El plan gratuito permite un proyecto activo. Publicá el actual para empezar otro.</p>
    @endif
</x-projects.layout>
