@props(['ctx', 'section'])
@php
    $view = "sections.{$section->type()}.{$section->variant()}";
    $exists = $section->type() !== '' && $section->variant() !== '' && view()->exists($view);
@endphp
@if($exists)
    @include($view, ['ctx' => $ctx, 'section' => $section, 'content' => $section->content()])
@elseif(config('app.debug'))
    <section class="wp-section wp-container" style="border:1px dashed var(--wp-border);color:var(--wp-text-muted)">
        <p><strong>Sección aún no implementada:</strong>
            <code>{{ $section->type() ?: '(sin type)' }} / {{ $section->variant() ?: '(sin variant)' }}</code>
            &mdash; se implementa en la tarea siguiente.</p>
    </section>
@endif
