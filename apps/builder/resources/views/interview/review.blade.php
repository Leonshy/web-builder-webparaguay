<x-interview.shell :project="$project" :draft="$draft" step="content" title="Revisión">
    <h1>Repasá antes de generar</h1>
    @php($allOk = $draft->allConfirmed())
    @foreach(['brand' => 'Marca', 'purpose' => 'Propósito', 'content' => 'Contenido'] as $k => $label)
        @php($st = $draft->{$k.'_status'})
        <div class="card">
            <strong>{{ $label }}</strong>
            <span style="font-size:12px;padding:2px 6px;border-radius:4px;background:{{ $st === 'confirmed' ? '#d9efe8' : '#fbe9d0' }}">
                {{ $st === 'confirmed' ? 'ok' : 'revisar' }}
            </span>
            <form method="post" action="{{ route('interview.reopen', ['project' => $project, 'stage' => $k]) }}" style="display:inline">
                @csrf<button class="btn" style="background:#fff;color:#1f6f5c;border:1px solid #1f6f5c;padding:2px 8px;font-size:12px">Editar</button>
            </form>
            @foreach($draft->{$k}['assumptions'] ?? [] as $a)<div class="mut" style="margin-top:4px">· asumido: {{ $a }}</div>@endforeach
        </div>
    @endforeach

    <form method="post" action="{{ route('interview.generate', $project) }}" style="margin-top:12px">
        @csrf
        <button class="btn" @disabled(!$allOk) style="{{ $allOk ? '' : 'background:#ccc' }}">
            {{ $allOk ? 'Generar mi sitio' : 'Reconfirmá las etapas marcadas para revisar' }}
        </button>
    </form>
    <p class="mut" style="margin-top:6px">La IA arma un JSON validado contra el contrato. El sitio lo pinta código escrito y auditado por el equipo.</p>
</x-interview.shell>
