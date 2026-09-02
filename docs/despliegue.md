# Despliegue

## Principio

Todos los sitios publicados corren el **mismo paquete versionado del CMS**
(`packages/site-runtime`), desplegado por automatización. Nunca a mano.

## Cómo funciona

1. `scripts/release-site-runtime.sh <version>` hace `git subtree split` de
   `packages/site-runtime` y publica la rama `site-runtime-v<version>` en
   GitHub. Es un repo Laravel autocontenido en la raíz.
2. Al publicar un proyecto, `apps/builder` (vía `packages/provisioning`):
   - cobra (WHMCS) — *publicar = comprar*;
   - crea el subscription en Plesk;
   - configura el repositorio git del subscription apuntando a
     `site-runtime-v<version>` y dispara `git pull` + `migrate` + `config:cache`;
   - agrega el dominio (alias + DNS + Let's Encrypt).
3. El `.com.py` no bloquea: el sitio queda vivo en el subdominio de la
   plataforma y el dominio en trámite (NIC.py, 24–72 h), con tarea en el
   back-office. Al completarse se reapunta.

## Credenciales

`WHMCS_*` y `PLESK_*` **nunca** en el repo. Sólo por env, con **lista blanca de
IP** obligatoria del lado de cada proveedor. Ver `apps/builder/.env.example`.

Por defecto `PUBLISHING_BILLING=fake` y `PUBLISHING_HOSTING=fake`: el flujo
completo funciona sin tocar servicios externos (dev y CI).

## La rama de release es autocontenida

`scripts/release-site-runtime.sh <version>` produce `site-runtime-v<version>`
con **todo resuelto**: `schema-pkg/` (copia real de `packages/schema`),
`vendor/` (`composer install --no-dev -o`) y `public/build/` (assets de vite).

El servidor **no corre composer ni npm**. Deploy:

```
git pull && php artisan migrate --force && php artisan config:cache
```

En Plesk esto se configura como acción de deploy del repositorio git del
subscription (extensión Git), disparada por WHMCS al provisionar.
