# site-runtime

Renderer + (a futuro) CMS. Paquete versionado que corre en cada servidor Plesk.
Depende sólo de `packages/schema` (ADR-001). **Nunca importa de `apps/builder`.**

## Qué hace hoy (Tarea 1)

Recibe un JSON validado contra `site.schema.json` y lo pinta con componentes
Blade. Sin base de datos: el JSON se lee de un archivo.

- **Contrato → CSS**: `App\Rendering\Theme\ThemeHelper` convierte
  `theme.colors/typography/shape` en variables `--wp-*` en `:root`. El agente
  sólo acierta 4 colores; el resto se deriva y se ajusta para contraste AA.
- **Íconos**: `App\Rendering\IconRegistry` — 46 claves cerradas, fallback por
  tipo de sección. No falla, no muestra roto, no inventa.
- **Navegación**: `App\Rendering\Navigation` la deriva de las páginas activas y
  las anclas de sección. Nunca se escribe a mano.
- **Botón / imagen / ítem**: componentes compartidos (`x-site.button`,
  `x-site.image`, `x-site.item`).
- **richtext**: `App\Rendering\HtmlSanitizer` — allowlist cerrada, siempre.
- **Despachador**: `x-site.section` lee `type`/`variant` y renderiza
  `resources/views/sections/{type}/{variant}.blade.php`.

### Tipos implementados

`hero` · `page_header` · `media_text` · `feature_list` · `cta_banner` · `stats`
(todas sus variantes). Los otros 8 tipos muestran un aviso en modo debug y se
implementan en la Tarea 2.

## Desarrollo

```bash
composer install && npm install && npm run build
php artisan serve      # http://localhost:8000/preview
php artisan test
```

`/preview` renderiza `resources/schema/example-site.json` (espejo de
`schema/example-site.json` en la raíz del repo).

## Decisiones no especificadas

Ver `../../docs/decisiones-tarea-1.md`.
