# Aprovisionamiento automático de instancias

Al publicar, el alta de la instancia es automática. No se toca Plesk a mano.

## Flujo

1. **WHMCS** cobra y, con `AcceptOrder` + auto-setup, el módulo de Plesk crea la
   suscripción `<label><projectId>.sites.naranja.com.py`.
2. El builder, **por SSH** (`PleskInstanceConfigurator`, corriendo `plesk bin` /
   `plesk ext` en el servidor), sobre esa suscripción:
   - crea la base de datos + usuario (si existía, la recrea limpia),
   - fija el document root en `httpdocs/public` (`plesk bin site --update`),
   - configura el repositorio git al tag `site-runtime-v<version>`
     (`plesk ext git --create` con `-active-branch` y `-actions` que escriben el
     `.env` y corren `migrate` + `config:cache`),
   - despliega (`plesk ext git --deploy`),
   - emite Let's Encrypt (`plesk bin extension --exec letsencrypt cli.php`).
3. El builder siembra el sitio (`/internal/sites`) con el documento y crea el
   usuario dueño del CMS.
4. Si la instancia todavía no responde, **`builder:seed-pending`** (scheduler,
   cada minuto) lo reintenta hasta que está lista. Sin intervención.

Se usa SSH y no la API REST porque la API de Plesk no cubre git ni Let's Encrypt.
La plataforma sólo toca las suscripciones que nombra: el servidor puede alojar
otros clientes sin que esto los afecte.

## Configuración (una sola vez)

En el `.env` del builder:

```
PUBLISHING_HOSTING=whmcs
WHMCS_PAYMENT_MODE=auto
PLESK_SSH_HOST=<ip-del-vps>
PLESK_SSH_USER=root
PLESK_SSH_KEY=/ruta/a/la/llave_privada
PLESK_LETSENCRYPT_EMAIL=soporte@webparaguay.com
```

Generar la llave (en la máquina del builder) y autorizarla en el servidor:

```
ssh-keygen -t ed25519 -f ~/.ssh/webparaguay_plesk -N ""
# copiar ~/.ssh/webparaguay_plesk.pub y en el servidor:
echo "<contenido .pub>" >> ~/.ssh/authorized_keys
```

En producción: llave dedicada, usuario SSH restringido si se puede, y firewall
del puerto 22 a la IP del builder. El repo `site-runtime-v<version>` es público
(clonable por HTTPS sin llave).

El scheduler del builder tiene que estar corriendo: producción `* * * * * php
artisan schedule:run`; dev `php artisan schedule:work`.
