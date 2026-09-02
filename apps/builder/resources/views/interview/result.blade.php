<x-projects.layout title="Sitio generado">
    <p class="mut"><a href="{{ route('projects.show', $project) }}">← {{ $project->name }}</a></p>
    <h1>Tu sitio está listo</h1>
    @if($project->site)
        <div class="card">
            <strong>{{ $project->site->name }}</strong>
            <p class="mut">Estado del proyecto: {{ $project->status }}</p>
            <p>Referencia en site-runtime: <code>{{ $project->site->runtime_site_ref }}</code></p>
            @if($project->site->preview_url)
                <p><a href="{{ $project->site->preview_url }}" target="_blank" rel="noopener">Abrir el preview</a>
                    <span class="mut">(no indexable, compartible)</span></p>
            @endif
        </div>

        @if($project->status === 'generated')
            <a class="wf-link" href="{{ route('publish.show', $project) }}"><span class="btn">Publicar el sitio</span></a>
        @elseif($project->status === 'awaiting_payment')
            <p><a href="{{ route('publish.show', $project) }}">Publicación en curso — esperando confirmación de pago</a></p>
        @elseif($project->status === 'published')
            <p><a href="{{ route('publish.show', $project) }}">En línea ↗</a></p>
        @endif
        <p class="mut" style="margin-top:8px">El preview y la edición corren en site-runtime. La publicación cobra y aprovisiona el hosting.</p>
    @else
        <p class="mut">Todavía no se generó. <a href="{{ route('interview', ['project' => $project, 'stage' => 'review']) }}">Ir a la revisión</a>.</p>
    @endif
</x-projects.layout>
