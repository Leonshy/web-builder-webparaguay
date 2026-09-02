<x-projects.layout title="Publicar">
    <p class="mut"><a href="{{ route('projects.show', $project) }}">← {{ $project->name }}</a></p>
    <h1>Publicar el sitio</h1>

    @if(session('ok'))<div class="card" style="border-color:#1f6f5c;background:#eef6f3"><strong>{{ session('ok') }}</strong></div>@endif
    @if($errors->any())
        <div class="card" style="border-color:#c33;background:#fdf0f0">
            <strong>No se publicó.</strong>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($project->status === 'published' && $project->site)
        <div class="card">
            <strong>En línea</strong>
            <p>Sitio: <a href="https://{{ $project->site->live_fqdn }}" target="_blank">{{ $project->site->live_fqdn }}</a></p>
            <p class="mut">Paquete del CMS: v{{ $project->site->runtime_version }} · desplegado por git</p>
            @if($project->site->domain_status === 'compy_pending')
                <p class="mut">Dominio <strong>{{ $project->site->pending_fqdn }}</strong>: en trámite en NIC.py (24–72 h).
                    El sitio ya está vivo en el subdominio; al completarse se reapunta.</p>
            @endif
        </div>
        @foreach($project->backofficeTasks->where('status', 'open') as $t)
            <div class="card">
                <span class="mut">Back-office ({{ $t->kind }}):</span> {{ $t->note }}
                @if($t->kind === 'domain_compy_register')
                    <div class="mut">Al completarse el registro:
                        <code>php artisan builder:complete-domain-task {{ $t->id }}</code></div>
                @endif
            </div>
        @endforeach
        @foreach($project->payments as $p)
            <div class="card mut">Pago: {{ $p->currency }} {{ number_format($p->amount, 0, ',', '.') }} · {{ $p->status }} · {{ $p->concept }}</div>
        @endforeach
    @elseif(! $project->site || ! $project->site->runtime_site_ref)
        <p class="mut">Primero completá la entrevista y generá el sitio.
            <a href="{{ route('interview', $project) }}">Ir a la entrevista</a>.</p>
    @else
        <p class="mut"><strong>Publicar = comprar.</strong> El sitio se cobra, se aprovisiona el hosting y se despliega el paquete del CMS.</p>

        <form method="post" action="{{ route('publish.billing', $project) }}" class="card">
            @csrf
            <p><strong>Datos de facturación</strong> <span class="mut">— los necesita el sistema de facturación</span></p>
            @php($o = $organization)
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <label>RUC o CI *<br><input type="text" name="billing_tax_id" value="{{ old('billing_tax_id', $o->billing_tax_id) }}" placeholder="80012345-6" required></label>
                <label>Razón social<br><input type="text" name="billing_company" value="{{ old('billing_company', $o->billing_company) }}"></label>
                <label>Teléfono *<br><input type="text" name="billing_phone" value="{{ old('billing_phone', $o->billing_phone) }}" placeholder="0981 123456" required></label>
                <label>Ciudad *<br><input type="text" name="billing_city" value="{{ old('billing_city', $o->billing_city) }}" required></label>
                <label style="grid-column:1/-1">Dirección *<br><input type="text" name="billing_address" value="{{ old('billing_address', $o->billing_address) }}" required></label>
                <label>Departamento<br><input type="text" name="billing_state" value="{{ old('billing_state', $o->billing_state) }}"></label>
                <label>Código postal<br><input type="text" name="billing_postcode" value="{{ old('billing_postcode', $o->billing_postcode) }}"></label>
                <label>País *<br>
                    <select name="billing_country" required>
                        <option value="PY" @selected(($o->billing_country ?: 'PY') === 'PY')>Paraguay</option>
                        <option value="AR" @selected($o->billing_country === 'AR')>Argentina</option>
                        <option value="BR" @selected($o->billing_country === 'BR')>Brasil</option>
                    </select>
                </label>
            </div>
            <button class="btn" style="margin-top:8px">Guardar datos</button>
            @if($organization->billingComplete())<span class="mut" style="margin-left:8px">✓ completo</span>@endif
        </form>

        <form method="post" action="{{ route('publish.store', $project) }}" class="card"
              @unless($organization->billingComplete()) style="opacity:.5;pointer-events:none" @endunless>
            @csrf
            <p><strong>Plan</strong></p>
            @foreach($plans as $code => $plan)
                <label class="card" style="display:block;cursor:pointer">
                    <input type="radio" name="plan" value="{{ $code }}" @checked($loop->first)>
                    {{ $plan->label }} — {{ $plan->price->format() }} / mes
                </label>
            @endforeach

            <p style="margin-top:12px"><strong>Dominio</strong></p>
            <label class="cms-check"><input type="radio" name="domain_kind" value="subdomain" checked> Subdominio de la plataforma (inmediato)</label>
            <label class="cms-check"><input type="radio" name="domain_kind" value="gtld"> Dominio .com / .net (automático)</label>
            <label class="cms-check"><input type="radio" name="domain_kind" value="compy"> Dominio .com.py (trámite manual, 24–72 h)</label>
            <p><input type="text" name="domain_value" placeholder="miempresa.com.py" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px"></p>

            <button class="btn">Pagar y publicar</button>
        </form>
    @endif
</x-projects.layout>
