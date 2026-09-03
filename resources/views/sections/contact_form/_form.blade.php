@php
    $c = $content;
    $fields = $c['fields'] ?? ['name', 'email', 'phone', 'message'];
    $labels = [
        'name' => 'Nombre', 'last_name' => 'Apellido', 'email' => 'Correo electrónico',
        'phone' => 'Teléfono', 'company' => 'Empresa', 'subject' => 'Asunto',
        'message' => 'Mensaje', 'newsletter' => 'Quiero recibir novedades',
    ];
    $sent = session('contacto_enviado');
@endphp
<form class="wp-form" method="post" action="{{ route('contact.submit') }}">
    @csrf
    @if($sent)
        <p class="wp-form__ok" role="status">{{ $c['success_message'] ?? 'Gracias, recibimos tu mensaje. Te respondemos a la brevedad.' }}</p>
    @endif
    <div class="wp-form__grid">
        @foreach($fields as $field)
            @continue(!isset($labels[$field]))
            <div @class(['wp-form__row', 'wp-form__row--full' => in_array($field, ['message', 'subject', 'newsletter'], true)])>
                @if($field === 'message')
                    <label for="f-{{ $field }}">{{ $labels[$field] }}</label>
                    <textarea id="f-{{ $field }}" name="{{ $field }}" rows="4"></textarea>
                @elseif($field === 'newsletter')
                    <label class="wp-form__check"><input type="checkbox" id="f-{{ $field }}" name="{{ $field }}" value="1"> {{ $labels[$field] }}</label>
                @else
                    <label for="f-{{ $field }}">{{ $labels[$field] }}</label>
                    <input id="f-{{ $field }}" name="{{ $field }}"
                        type="{{ $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text') }}">
                @endif
            </div>
        @endforeach
    </div>
    @if($c['consent_required'] ?? true)
        <label class="wp-form__check"><input type="checkbox" name="consent" value="1" required> Acepto ser contactado según la política de privacidad.</label>
    @endif
    <button type="submit" class="wp-btn wp-btn--primary">{{ $c['submit_label'] ?? 'Enviar' }}</button>
    <p class="wp-form__note">El envío, la validación y la protección anti-spam son gestionados por webparaguay.</p>
</form>
