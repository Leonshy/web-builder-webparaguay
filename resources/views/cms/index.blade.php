<x-cms.layout title="Sitios">
    <h1>Sitios</h1>
    @forelse($sites as $site)
        <div class="cms-list-item">
            <div>
                <strong><a href="{{ route('cms.site', $site) }}">{{ $site->name }}</a></strong>
                <div class="cms-muted">{{ $site->template }} · {{ $site->pages_count }} páginas
                    @if($site->published_at) · publicado {{ $site->published_at->format('d/m/Y') }} @endif
                </div>
            </div>
            <a class="cms-btn cms-btn--ghost" href="{{ route('cms.site', $site) }}">Editar</a>
        </div>
    @empty
        <p class="cms-muted">No hay sitios todavía. Se siembra uno con <code>php artisan db:seed</code>.</p>
    @endforelse
</x-cms.layout>
