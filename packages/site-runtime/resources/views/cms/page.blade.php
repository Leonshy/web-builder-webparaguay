<x-cms.layout :title="$page->title">
    <div class="cms-crumbs">
        <a href="{{ route('cms.index') }}">Sitios</a> /
        <a href="{{ route('cms.site', $page->site) }}">{{ $page->site->name }}</a> /
        {{ $page->title }}
    </div>
    <h1>{{ $page->title }}</h1>

    <div class="cms-card">
        <h2>Secciones</h2>
        <p class="cms-muted">El orden acá es el orden en la página. La navegación se deriva sola.</p>
        @foreach($page->sections as $section)
            <div class="cms-list-item">
                <div>
                    <strong><a href="{{ route('cms.section', $section) }}">{{ $section->type }}</a></strong>
                    <span class="cms-muted">· {{ $section->variant }}
                        @if($section->title) · “{{ $section->title }}” @endif
                        @unless($section->is_active) · oculta @endunless</span>
                </div>
                <div class="cms-row">
                    <form method="post" action="{{ route('cms.section.move', $section) }}">@csrf<input type="hidden" name="direction" value="up"><button class="cms-btn cms-btn--ghost" @disabled($loop->first)>↑</button></form>
                    <form method="post" action="{{ route('cms.section.move', $section) }}">@csrf<input type="hidden" name="direction" value="down"><button class="cms-btn cms-btn--ghost" @disabled($loop->last)>↓</button></form>
                    <a class="cms-btn cms-btn--ghost" href="{{ route('cms.section', $section) }}">Editar</a>
                </div>
            </div>
        @endforeach

        <form method="post" action="{{ route('cms.page.sections.store', $page) }}" class="cms-row" style="margin-top:1rem" id="add-section">
            @csrf
            <select name="type" id="add-type" required>
                <option value="">— tipo —</option>
                @foreach($sectionTypes as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
            </select>
            <select name="variant" id="add-variant" required disabled><option value="">— variante —</option></select>
            <button class="cms-btn">Agregar sección</button>
        </form>
    </div>

    <div class="cms-card">
        <h2>Datos de la página</h2>
        <form method="post" action="{{ route('cms.page.update', $page) }}">
            @csrf @method('PUT')
            <div class="cms-field"><label class="cms-field__label">Título</label><input type="text" name="title" value="{{ $page->title }}" required></div>
            <div class="cms-field"><label class="cms-field__label">Slug</label><input type="text" name="slug" value="{{ $page->slug }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required></div>
            <label class="cms-check"><input type="hidden" name="is_home" value="0"><input type="checkbox" name="is_home" value="1" @checked($page->is_home)> Es la página de inicio</label>
            <label class="cms-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($page->is_active)> Activa</label>
            <label class="cms-check"><input type="hidden" name="show_in_nav" value="0"><input type="checkbox" name="show_in_nav" value="1" @checked($page->show_in_nav)> Mostrar en la navegación</label>
            <div class="cms-field" style="margin-top:0.75rem"><label class="cms-field__label">SEO (JSON: title, description, noindex)</label>
                <textarea name="seo__json" rows="4">{{ json_encode($page->seo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
            </div>
            <div class="cms-row" style="margin-top:0.75rem">
                <button class="cms-btn">Guardar</button>
                @if($page->site->pages->count() > 1)
                    <button class="cms-btn cms-btn--danger" formaction="{{ route('cms.page.destroy', $page) }}" formmethod="post" onclick="return confirm('¿Eliminar la página y sus secciones?')">
                        @method('DELETE') Eliminar página
                    </button>
                @endif
            </div>
        </form>
    </div>

    <script>
        const variantsByType = @json(collect($sectionTypes)->mapWithKeys(fn ($t) => [$t => app(\App\Cms\SchemaForm::class)->variantsFor($t)]));
        const typeSel = document.getElementById('add-type');
        const varSel = document.getElementById('add-variant');
        typeSel?.addEventListener('change', () => {
            const vs = variantsByType[typeSel.value] || [];
            varSel.innerHTML = vs.map((v) => `<option value="${v}">${v}</option>`).join('');
            varSel.disabled = vs.length === 0;
        });
    </script>
</x-cms.layout>
