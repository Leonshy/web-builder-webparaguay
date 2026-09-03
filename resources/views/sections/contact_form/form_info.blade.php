@php($c = $content)
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-contact wp-contact--split">
        <div>@include('sections.contact_form._form', ['content' => $c])</div>
        <div>@include('sections.contact_form._info', ['c' => $c])</div>
    </div>
</x-site.section-shell>
