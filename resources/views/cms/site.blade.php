<x-cms.layout :title="$site->name">
    <div class="cms-crumbs"><a href="{{ route('cms.index') }}">Sitios</a> / {{ $site->name }}</div>
    <h1>{{ $site->name }}</h1>

    @if($schemaErrors)
        <div class="cms-err"><strong>El sitio no valida ahora mismo:</strong>
            <ul>@foreach($schemaErrors as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="cms-card">
        <h2>Páginas</h2>
        @foreach($site->pages as $page)
            <div class="cms-list-item">
                <div>
                    <strong><a href="{{ route('cms.page', $page) }}">{{ $page->title }}</a></strong>
                    <span class="cms-muted">/{{ $page->slug }}
                        @if($page->is_home) · inicio @endif
                        @unless($page->is_active) · inactiva @endunless
                        · {{ $page->sections->count() }} secciones</span>
                </div>
                <a class="cms-btn cms-btn--ghost" href="{{ route('cms.page', $page) }}">Editar</a>
            </div>
        @endforeach

        <form method="post" action="{{ route('cms.site.pages.store', $site) }}" class="cms-row" style="margin-top:1rem">
            @csrf
            <input type="text" name="title" placeholder="Título de la página nueva" required style="flex:1">
            <input type="text" name="slug" placeholder="slug-de-url" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required style="flex:1">
            <button class="cms-btn">Agregar página</button>
        </form>
    </div>

    <div class="cms-card">
        <h2>Marca, contacto y layout</h2>
        <form method="post" action="{{ route('cms.site.update', $site) }}">
            @csrf @method('PUT')
            <div class="cms-field">
                <label class="cms-field__label">Nombre del sitio</label>
                <input type="text" name="name" value="{{ $site->name }}" required>
            </div>
            <div class="cms-field">
                <label class="cms-field__label">Plantilla</label>
                <select name="template">
                    @foreach(['landing', 'institucional', 'catalogo', 'ecommerce'] as $t)
                        <option value="{{ $t }}" @selected($site->template === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <h3 style="font-size:0.95rem">theme · settings · layout (JSON, se valida al guardar)</h3>
            <p class="cms-muted">La marca es dato. El agente de Marca escribe estos objetos; acá se ajustan a mano.</p>
            @foreach(['theme' => $site->theme, 'settings' => $site->settings, 'layout' => $site->layout] as $key => $val)
                <div class="cms-field">
                    <label class="cms-field__label">{{ $key }}</label>
                    <textarea name="{{ $key }}__json" rows="{{ $key === 'settings' ? 8 : 6 }}">{{ json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</textarea>
                </div>
            @endforeach

            <button class="cms-btn">Guardar</button>
        </form>
    </div>

    <div class="cms-card">
        <h2>Enlaces de preview</h2>
        <p class="cms-muted">No indexables, compartibles, vencen a los 30 días.</p>
        @foreach($site->previewTokens as $t)
            <div class="cms-list-item">
                <code>{{ route('cms.preview', $t->token) }}</code>
                <span class="cms-muted">
                    @if($t->last_viewed_at) visto {{ $t->last_viewed_at->diffForHumans() }} @else sin visitas @endif
                </span>
            </div>
        @endforeach
        <form method="post" action="{{ route('cms.site.preview-tokens.store', $site) }}" style="margin-top:0.75rem">
            @csrf
            <button class="cms-btn cms-btn--ghost">Crear enlace de preview</button>
        </form>
    </div>
</x-cms.layout>
