# web-builder-webparaguay

Plataforma donde una PYME conversa con una IA, ve su sitio web completo
generado y lo publica en un clic, alojado en infraestructura de webparaguay
con un CMS autoadministrable.

> **El principio que ordena todo:** la IA configura, el código lo escriben las
> personas. La salida de un agente es siempre un JSON validado contra
> `packages/schema/site.schema.json`.

Leé [`CLAUDE.md`](CLAUDE.md) antes de tocar código.

## Estructura (ADR-001)

```
packages/schema/       contrato JSON + validador + introspector (webparaguay/schema)
packages/site-runtime/  renderer + CMS → corre en Plesk del cliente
apps/builder/           cuentas, proyectos, medición de consumo de IA
docs/                   legajo, anexos, decisiones, flujos UX
schema/                 espejo publicado del contrato
```

Dependencias, en una sola dirección: `schema → nada`,
`site-runtime → schema`, `builder → schema`.
**`site-runtime` nunca importa de `builder`** (frontera de seguridad).

## Estado

| Tarea | Qué | Estado |
|---|---|---|
| 1 | Andamiaje + renderer (6 tipos) | ✅ |
| 1-fix | Menú móvil accesible | ✅ |
| 2 | Los 14 tipos, 41 variantes + regresión visual | ✅ |
| — | `packages/schema` extraído | ✅ |
| 3 | Persistencia + CMS derivado del esquema (site-runtime) | ✅ |
| 3 | Jerarquía de cuentas + medición de IA (builder) | ✅ |
| 4 | Entrevista guiada — diseño (`docs/ux-flows/`) | ✅ |
| 4 | Entrevista + pipeline de generación + handoff a site-runtime | ✅ |
| — | Generador con IA en vivo (`ClaudeGenerator`, necesita API key) | wireado, sin ejercitar |
| — | Auth real en el builder | pendiente |
| — | Capa de abstracción de publicación (WHMCS + Plesk) | pendiente |

Decisiones no especificadas: `docs/decisiones-tarea-1.md`,
`docs/decisiones-tarea-2.md`, `docs/decisiones-tarea-3-4.md`.

## Desarrollo

```bash
# renderer + CMS
cd packages/site-runtime
composer install && npm install && npm run build
php artisan migrate --seed
php artisan serve            # /cms  ·  /preview  ·  /variants/{tipo}
php artisan test             # 38
npm run regression           # 43 (regresión visual de las 41 variantes)

# builder
cd apps/builder
composer install
php artisan migrate
php artisan serve             # /projects
php artisan test             # 7

# contrato
cd packages/schema && composer install && ./vendor/bin/phpunit   # 4
python3 schema/validar.py schema/example-site.json
```
