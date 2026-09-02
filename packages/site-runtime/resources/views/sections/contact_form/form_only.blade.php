@php($c = $content)
<x-site.section-shell :ctx="$ctx" :section="$section" inner="wp-container wp-container-narrow">
    @include('sections.contact_form._form', ['content' => $c])
</x-site.section-shell>
