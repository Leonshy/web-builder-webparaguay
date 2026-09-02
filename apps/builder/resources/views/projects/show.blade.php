<x-projects.layout :title="$project->name">
    <p class="mut"><a href="{{ route('projects.index') }}">← Proyectos</a></p>
    <h1>{{ $project->name }}</h1>
    <p class="mut">Estado: {{ $project->status }} · organización: {{ $project->organization->name }}</p>

    <h2 style="font-size:1.05rem">Consumo de IA de este proyecto</h2>
    @if($project->aiUsages->isEmpty())
        <p class="mut">Sin llamadas registradas. La entrevista guiada y los agentes llegan en la Tarea 4.</p>
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
