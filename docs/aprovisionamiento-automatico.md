# Aprovisionamiento automático de instancias

Al publicar, todo el alta de la instancia es automática. No se toca Plesk a mano.

## Flujo

1. **WHMCS** cobra y, con `AcceptOrder` + auto-setup, el módulo de Plesk crea la
   suscripción `<label><projectId>.sites.naranja.com.py`.
2. El builder, por la **API REST de Plesk** (`PleskInstanceConfigurator`,
   pasarela de CLI `/api/v2/cli/*`), sobre esa suscripción:
   - crea la base de datos + usuario,
   - fija el document root en `httpdocs/public`,
   - configura el repositorio git al tag `site-runtime-v<version>` con acciones
     de deploy que escriben el `.env` (APP_KEY nuevo, credenciales de la BD,
     `SITE_RUNTIME_INTERNAL_TOKEN`) y corren `migrate` + `config:cache`,
   - dispara el deploy,
   - emite el certificado Let's Encrypt.
3. El builder siembra el sitio (`/internal/sites`) con el documento y crea el
   usuario dueño del CMS.
4. Si la instancia todavía no responde (git/SSL en curso), queda una tarea y
   **`builder:seed-pending`** (scheduler, cada minuto) lo reintenta hasta que
   está lista. Sin intervención.

La plataforma sólo toca las suscripciones que creó: el servidor puede alojar
otros clientes sin que esto los afecte.

## Configuración (una sola vez)

En el `.env` del builder:

```
PUBLISHING_HOSTING=whmcs
WHMCS_PAYMENT_MODE=auto
PLESK_URL=https://<ip-del-vps>:8443
PLESK_API_KEY=<clave>
PLESK_LETSENCRYPT_EMAIL=soporte@webparaguay.com
```

La API key se genera en el servidor Plesk (con lista blanca de IP del builder):

```
plesk bin secret_key -c -ip-address <ip-del-builder> -description "webparaguay-builder"
```

Requisitos en Plesk: extensiones **Git** y **Let's Encrypt** instaladas; el
repo `site-runtime-v<version>` es público (clonable por HTTPS sin llave).

El scheduler del builder tiene que estar corriendo: en producción, cron
`* * * * * php artisan schedule:run`; en dev, `php artisan schedule:work`.
