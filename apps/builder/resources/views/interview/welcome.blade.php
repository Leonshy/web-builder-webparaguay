<x-interview.shell :project="$project" :draft="$draft" title="Empecemos">
    <h1>Armemos tu sitio en 3 pasos</h1>
    <div class="card"><strong>1 · Tu marca</strong><p class="mut">Colores y tipografía. Si no tenés, te propongo opciones por rubro.</p></div>
    <div class="card"><strong>2 · El propósito</strong><p class="mut">Rubro, a quién te dirigís y para qué es el sitio.</p></div>
    <div class="card"><strong>3 · El contenido</strong><p class="mut">Servicios, textos y datos de contacto.</p></div>
    <form method="post" action="{{ route('interview.purpose', $project) }}" style="display:none"></form>
    <a class="wf-link" href="{{ route('interview', ['project' => $project, 'stage' => 'purpose']) }}"><div class="btn">Empezar</div></a>
    <p class="mut" style="margin-top:8px">Cada paso se guarda solo. Podés cerrar y volver.</p>
</x-interview.shell>
