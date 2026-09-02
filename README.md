# web-builder-webparaguay

Plataforma donde una PYME conversa con una IA, ve su sitio web completo
generado y lo publica en un clic, alojado en infraestructura de webparaguay
con un CMS autoadministrable.

> **El principio que ordena todo:** la IA configura, el código lo escriben las
> personas. La salida de un agente es siempre un JSON validado contra
> `packages/schema/site.schema.json`.

## Estructura (ADR-001)

```
packages/schema/        contrato JSON + validador + introspector (webparaguay/schema)
packages/site-runtime/  renderer + CMS → paquete versionado, corre en Plesk del cliente
packages/provisioning/  capa de abstracción sobre WHMCS + Plesk + deploy por git
apps/builder/           cuentas, proyectos, entrevista guiada, generación, publicación
docs/                   contrato de secciones (anexo) + ADR de arquitectura
schema/                 espejo publicado del contrato
```

Dependencias, en una sola dirección: `schema → nada`,
`site-runtime → schema`, `provisioning → schema`, `builder → todos`.
**`site-runtime` nunca importa de `builder`** (frontera de seguridad).

Documentación de arquitectura: [`docs/adr-001-monorepo-con-fronteras.md`](docs/adr-001-monorepo-con-fronteras.md).
Contrato de secciones y variantes: [`docs/anexo-a-catalogo-secciones.md`](docs/anexo-a-catalogo-secciones.md).

## Estado

| Área | Estado |
|---|---|
| Renderer — 14 tipos de sección, 41 variantes | ✅ |
| Regresión visual de las 41 variantes (Playwright) | ✅ |
| Persistencia + CMS derivado del esquema (site-runtime) | ✅ |
| Jerarquía de cuentas + medición de consumo de IA (builder) | ✅ |
| Entrevista guiada + pipeline de generación + handoff a site-runtime | ✅ |
| Capa de publicación (WHMCS + Plesk, deploy por git pull) | 🚧 |
| Generador con IA en vivo (`ClaudeGenerator`, necesita API key) | wireado, sin ejercitar |
| Auth real en el builder | pendiente |

## Desarrollo

```bash
# contrato
cd packages/schema && composer install && ./vendor/bin/phpunit
python3 schema/validar.py schema/example-site.json

# renderer + CMS
cd packages/site-runtime
composer install && npm install && npm run build
php artisan migrate --seed
php artisan serve            # /cms  ·  /preview  ·  /variants/{tipo}
php artisan test
npm run regression          # regresión visual de las 41 variantes

# builder
cd apps/builder
composer install
php artisan migrate
php artisan serve            # /projects
php artisan test
```

## Despliegue

Los sitios publicados corren el **mismo paquete versionado del CMS**
(`packages/site-runtime`), desplegado por automatización: el servidor Plesk
hace `git pull` del tag correspondiente. Nunca se despliega a mano.
`packages/provisioning` orquesta cobro → alta de hosting → deploy → dominio.
