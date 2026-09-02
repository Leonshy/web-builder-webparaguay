<x-interview.shell :project="$project" :draft="$draft" step="brand" title="Etapa 1 · Marca">
    <h1>Tu marca</h1>
    @php($b = $draft->brand ?? [])
    <p class="mut">Si tenés logo o manual de marca, por ahora cargá los colores a mano. Si no, elegí una propuesta.</p>

    <form method="post" action="{{ route('interview.brand', $project) }}">
        @csrf
        <div style="display:grid;gap:10px;grid-template-columns:1fr 1fr">
        @foreach($proposals as $i => $pal)
            <label class="card" style="cursor:pointer">
                <input type="radio" name="_pick" onclick="pick(this, {{ json_encode($pal['colors']) }}, '{{ $pal['pairing'] }}')">
                <strong>{{ $pal['name'] }}</strong>
                <div style="display:flex;gap:4px;margin:6px 0">
                    @foreach($pal['colors'] as $c)<span style="width:22px;height:22px;border-radius:4px;background:{{ $c }};border:1px solid #0002"></span>@endforeach
                </div>
                <span class="mut">{{ $pal['pairing'] }}</span>
            </label>
        @endforeach
        </div>

        <div class="card" style="margin-top:10px">
            <strong>Ajuste fino</strong>
            <div style="display:grid;gap:6px;grid-template-columns:1fr 1fr;margin-top:6px">
                @foreach(['primary' => 'Primario', 'accent' => 'Acento', 'background' => 'Fondo', 'text' => 'Texto'] as $k => $label)
                    <label>{{ $label }}<br><input type="text" id="f-{{ $k }}" name="{{ $k }}"
                        value="{{ $b['colors'][$k] ?? ($proposals[0]['colors'][$k]) }}" pattern="#[0-9a-fA-F]{6}" required></label>
                @endforeach
            </div>
            <label style="display:block;margin-top:8px">Tipografía
                <select id="f-pairing" name="pairing">
                    @foreach(['manrope','playfair-inter','fraunces-inter','sora-inter','space-grotesk-ibm-plex','dm-serif-dm-sans','poppins-work-sans','libre-franklin-lora'] as $pr)
                        <option value="{{ $pr }}" @selected(($b['typography']['pairing'] ?? $proposals[0]['pairing']) === $pr)>{{ $pr }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <input type="hidden" name="source" value="palette">
        <button class="btn" style="margin-top:10px">Confirmar y seguir</button>
    </form>

    <form method="post" action="{{ route('interview.brand.regenerate', $project) }}" style="margin-top:8px">
        @csrf<button class="btn" style="background:#fff;color:#1f6f5c;border:1.5px solid #1f6f5c" @disabled($draft->palette_regenerations >= 3)>Mostrame otras paletas</button>
    </form>

    <script>
    function pick(el, colors, pairing) {
        for (const k in colors) document.getElementById('f-' + k).value = colors[k];
        document.getElementById('f-pairing').value = pairing;
    }
    </script>
</x-interview.shell>
