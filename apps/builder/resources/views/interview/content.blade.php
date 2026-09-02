<x-interview.shell :project="$project" :draft="$draft" step="content" title="Etapa 3 · Contenido">
    <h1>El contenido</h1>
    @php($c = $draft->content ?? [])
    <form method="post" action="{{ route('interview.content', $project) }}" class="card">
        @csrf
        <p><label>Nombre de la empresa<br><input type="text" name="business_name" value="{{ $c['business_name'] ?? '' }}" required style="width:100%"></label></p>
        <p><label>Frase corta (tagline)<br><input type="text" name="tagline" value="{{ $c['tagline'] ?? '' }}" maxlength="120" style="width:100%"></label></p>
        <p><label>Servicios (uno por línea, formato <code>Nombre: descripción</code>)<br>
            <textarea name="services_raw" rows="5" style="width:100%">@foreach($c['services'] ?? [] as $s){{ $s['name'] }}@if(!empty($s['description'])): {{ $s['description'] }}@endif
@endforeach</textarea></label></p>
        <p><label>Sobre la empresa (opcional)<br><textarea name="about_text" rows="4" style="width:100%">{{ $c['about_text'] ?? '' }}</textarea></label></p>
        <div style="display:grid;gap:6px;grid-template-columns:1fr 1fr">
            <label>Email<br><input type="email" name="email" value="{{ $c['email'] ?? '' }}"></label>
            <label>Teléfono<br><input type="text" name="phone" value="{{ $c['phone'] ?? '' }}"></label>
            <label>WhatsApp<br><input type="text" name="whatsapp" value="{{ $c['whatsapp'] ?? '' }}"></label>
            <label>Horario<br><input type="text" name="schedule" value="{{ $c['schedule'] ?? '' }}"></label>
        </div>
        <p><label>Dirección<br><input type="text" name="address" value="{{ $c['address'] ?? '' }}" style="width:100%"></label></p>
        <p><label>Textos de referencia (opcional, se usan como insumo, no se copian literal)<br>
            <textarea name="reference_texts" rows="3" style="width:100%">{{ $c['reference_texts'][0] ?? '' }}</textarea></label></p>
        <button class="btn">Confirmar</button>
    </form>
</x-interview.shell>
