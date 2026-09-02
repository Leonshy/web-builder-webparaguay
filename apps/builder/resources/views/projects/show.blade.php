<x-projects.layout :title="$project->name">
    <p class="mut"><a href="{{ route('projects.index') }}">← Proyectos</a></p>
    <h1>{{ $project->name }}</h1>
    <p class="mut">Estado: {{ $project->status }} · organización: {{ $project->organization->name }}</p>

    <div class="card">
        @php($draft = $project->interviewDraft)
        @if(! $draft || $draft->stage === 'welcome')
            <strong>Entrevista guiada</strong>
            <p class="mut">Marca → propósito → contenido. Al terminar, se genera el sitio.</p>
            <a class="wf-link" href="{{ route('interview', $project) }}"><span class="btn">Empezar la entrevista</span></a>
        @elseif($draft->stage === 'done')
            <strong>Sitio generado</strong>
            <p><a href="{{ route('interview.result', $project) }}">Ver el resultado y el preview</a></p>
        @else
            <strong>Entrevista en curso</strong> <span class="mut">(etapa: {{ $draft->stage }})</span>
            <p><a class="wf-link" href="{{ route('interview', $project) }}">Continuar</a></p>
        @endif
    </div>

    <h2 style="font-size:1.05rem">Consumo de IA de este proyecto</h2>
    @if($project->aiUsages->isEmpty())
        <p class="mut">Sin llamadas registradas. Con <code>BUILDER_GENERATOR=claude</code> cada generación queda acá.</p>
    @else
        <table>
            <thead><tr><th>Acción</th><th>Modelo</th><th>In</th><th>Out</th><th>Costo US$</th><th>Cuándo</th></tr></thead>
            <tbody>
            @foreach($project->aiUsages as $u)
                <tr>
                    <td>{{ $u->action }}</td><td>{{ $u->model }}</td>
                    <td>{{ $u->input_tokens }}</td><td>{{ $u->output_tokens }}</td>
                    <td>{{ number_format($u->costUsd(), 6) }}</td>
                    <td class="mut">{{ $u->occurred_at->diffForHumans() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</x-projects.layout>
