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

Los **14 tipos** con todas sus variantes (41):
`hero` · `page_header` · `media_text` · `feature_list` · `cta_banner` ·
`contact_form` · `stats` · `rich_text` · `gallery` · `entity_grid` ·
`testimonials` · `faq` · `pricing_plans` · `video`.

- `contact_form`: los datos salen de `site.settings`. El envío es un **stub**
  (`ContactStubController`); captcha, validación y rate limiting son del sistema.
- `entity_grid`: sólo `source: "manual"` en el MVP. Los demás valores muestran
  un aviso en debug y son el punto de extensión de la v1.
- `video`: YouTube y Vimeo. Si la URL no parsea, la sección no se renderiza.

## Desarrollo

```bash
composer install && npm install && npm run build
php artisan serve          # http://localhost:8000/preview
php artisan test           # unit + feature
npm run regression         # regresión visual de las 41 variantes (Playwright)
npm run regression:update  # regenera las referencias
```

- `/preview` → `resources/schema/example-site.json` (plantilla institucional).
- `/variants/{tipo}` → `resources/schema/variants-gallery.json`, una página por
  tipo con todas sus variantes. Fixture de la regresión visual.

## Decisiones no especificadas

`../../docs/decisiones-tarea-1.md` y `../../docs/decisiones-tarea-2.md`.
