# Despliegue

## Principio

Todos los sitios publicados corren el **mismo paquete versionado del CMS**
(`packages/site-runtime`), desplegado por automatización. Nunca a mano.

## Cómo funciona

1. `scripts/release-site-runtime.sh <version>` hace `git subtree split` de
   `packages/site-runtime` y publica la rama `site-runtime-v<version>` en
   GitHub. Es un repo Laravel autocontenido en la raíz.
2. Al publicar un proyecto, `apps/builder` (vía `packages/provisioning`):
   - **`PUBLISHING_HOSTING=whmcs`**: coloca una orden del producto de hosting
     `WHMCS_PRODUCT_ID` en WHMCS. WHMCS provisiona la suscripción en Plesk
     (módulo de servidor). Una suscripción por sitio (aislación §5.9).
     - `WHMCS_PAYMENT_MODE=manual`: la orden queda con factura impaga; un admin
       confirma el pago y acepta la orden en WHMCS; `php artisan
       builder:activate-order {projectId}` termina la publicación (siembra el
       sitio en la instancia + lo pone en línea).
     - `WHMCS_PAYMENT_MODE=auto`: se asume el pago cobrado aparte (Bancard);
       el builder registra el pago en la factura y acepta la orden en el acto.
   - **`PUBLISHING_HOSTING=fake`** (dev/CI): simula todo sin tocar servicios.
   - El **service plan de Plesk** que usa el producto debe clonar
     `site-runtime-v<version>` al crear la suscripción (extensión Git), con
     acciones de deploy `php artisan migrate --force` + `config:cache`.
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
