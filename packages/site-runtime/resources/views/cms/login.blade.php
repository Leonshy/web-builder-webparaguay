<x-cms.layout title="Ingresar">
    <div class="cms-card" style="max-width:22rem;margin:3rem auto">
        <h1>Ingresar al CMS</h1>
        <p class="cms-muted">Con el usuario y la contraseña que te dio la plataforma al publicar.</p>

        @if($errors->any())
            <div class="cms-err"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="{{ route('login.attempt') }}">
            @csrf
            <div class="cms-field">
                <label class="cms-field__label" for="email">Correo</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="cms-field">
                <label class="cms-field__label" for="password">Contraseña</label>
                <input type="password" name="password" id="password" required>
            </div>
            <label class="cms-check" style="margin-bottom:0.9rem">
                <input type="checkbox" name="remember" value="1"> Recordarme
            </label>
            <button class="cms-btn" type="submit">Ingresar</button>
        </form>
    </div>
</x-cms.layout>
