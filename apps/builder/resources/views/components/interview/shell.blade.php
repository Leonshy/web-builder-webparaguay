@props(['project', 'draft', 'step' => null, 'title' => 'Entrevista'])
<x-projects.layout :title="$title">
    <p class="mut"><a href="{{ route('projects.show', $project) }}">← {{ $project->name }}</a></p>
    @if($step)
        <div style="display:flex;gap:6px;margin:12px 0 20px">
            @foreach(['brand' => 'Marca', 'purpose' => 'Propósito', 'content' => 'Contenido'] as $k => $label)
                @php($st = $draft->{$k.'_status'})
                <a class="wf-link" href="{{ route('interview', ['project' => $project, 'stage' => $k]) }}"
                   style="flex:1;text-align:center;font-size:12px;padding:6px;border-radius:6px;text-decoration:none;
                   background:{{ $st === 'confirmed' ? '#1f6f5c' : ($k === $step ? '#9cccbf' : '#e3e3e3') }};
                   color:{{ $st === 'confirmed' ? '#fff' : '#333' }}">
                   {{ $label }}
                   @if($st === 'needs_review') ⚠ @endif
                </a>
            @endforeach
        </div>
    @endif
    @if($errors->any())
        <div class="card" style="border-color:#c33;background:#fdf0f0">
            <strong>No se pudo generar el sitio válido.</strong>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    {{ $slot }}
</x-projects.layout>
