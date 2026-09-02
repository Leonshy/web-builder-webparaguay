<x-cms.layout :title="$section->type">
    <div class="cms-crumbs">
        <a href="{{ route('cms.index') }}">Sitios</a> /
        <a href="{{ route('cms.site', $section->page->site) }}">{{ $section->page->site->name }}</a> /
        <a href="{{ route('cms.page', $section->page) }}">{{ $section->page->title }}</a> /
        {{ $section->type }}
    </div>
    <h1>{{ $section->type }}</h1>

    <div class="cms-card">
        <h2>Variante</h2>
        <p class="cms-muted">Cambiar de variante conserva todo el contenido. Los campos que la variante nueva no use quedan guardados.</p>
        <form method="post" action="{{ route('cms.section.variant', $section) }}">
            @csrf @method('PUT')
            <select name="variant" data-variant-select>
                @foreach($variants as $v)<option value="{{ $v }}" @selected($section->variant === $v)>{{ $v }}</option>@endforeach
            </select>
            <noscript><button class="cms-btn">Cambiar</button></noscript>
        </form>
    </div>

    <form method="post" action="{{ route('cms.section.update', $section) }}">
        @csrf @method('PUT')

        <div class="cms-card">
            <h2>Cabecera de la sección</h2>
            <label class="cms-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($section->is_active)> Sección activa</label>
            <div style="margin-top:0.75rem">
                @foreach($envelopeFields as $f)
                    @php($val = $f['name'] === 'background_image' ? $section->background_image : $section->{$f['name']})
                    <x-cms.field :field="$f" :name="'envelope['.$f['name'].']'" :value="$val" />
                @endforeach
            </div>
        </div>

        <div class="cms-card">
            <h2>Contenido</h2>
            @forelse($contentFields as $f)
                <x-cms.field :field="$f" :name="'content['.$f['name'].']'" :value="data_get($section->content, $f['name'])" />
            @empty
                <p class="cms-muted">Este tipo no tiene campos de contenido.</p>
            @endforelse
        </div>

        <div class="cms-row">
            <button class="cms-btn">Guardar sección</button>
            @if($section->page->sections->count() > 1)
                <button class="cms-btn cms-btn--danger" formaction="{{ route('cms.section.destroy', $section) }}" onclick="return confirm('¿Eliminar la sección?')">
                    @method('DELETE') Eliminar
                </button>
            @endif
        </div>
    </form>

    <div class="cms-card" style="margin-top:1rem">
        <h2>JSON persistido</h2>
        <pre class="cms-muted" style="white-space:pre-wrap;font-size:0.8rem">{{ json_encode($section->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</x-cms.layout>
