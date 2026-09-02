<x-interview.shell :project="$project" :draft="$draft" step="purpose" title="Etapa 2 · Propósito">
    <h1>El propósito del sitio</h1>
    @php($p = $draft->purpose ?? [])
    <form method="post" action="{{ route('interview.purpose', $project) }}" class="card">
        @csrf
        <p><label>¿A qué se dedica la empresa? (rubro)<br>
            <input type="text" name="industry" value="{{ $p['industry'] ?? '' }}" required style="width:100%"></label></p>
        <p><label>¿A quién le vende?<br>
            <input type="text" name="audience" value="{{ $p['audience'] ?? '' }}" placeholder="Industrias, familias, comercios…" style="width:100%"></label></p>
        <p><label>¿Qué querés que haga alguien que entra?<br>
            <select name="goal">
                <option value="contact" @selected(($p['goal'] ?? '') === 'contact')>Que me escriba / pida presupuesto</option>
                <option value="about" @selected(($p['goal'] ?? '') === 'about')>Que conozca la empresa</option>
                <option value="catalog" @selected(($p['goal'] ?? '') === 'catalog')>Que vea un catálogo de servicios</option>
            </select></label></p>
        <p><label>Tipo de sitio<br>
            <select name="template">
                <option value="landing" @selected(($p['template'] ?? 'landing') === 'landing')>Una sola página (landing)</option>
                <option value="institucional" @selected(($p['template'] ?? '') === 'institucional')>Varias páginas (institucional)</option>
            </select></label></p>
        <button class="btn">Confirmar y seguir</button>
    </form>
</x-interview.shell>
