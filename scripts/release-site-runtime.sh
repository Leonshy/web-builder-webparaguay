#!/usr/bin/env bash
#
# Publica el paquete versionado de site-runtime como una rama desplegable
# y AUTOCONTENIDA: el servidor Plesk sólo hace `git pull` + migrate. No corre
# composer ni npm en el servidor.
#
#   scripts/release-site-runtime.sh 0.1.0
#
# La rama site-runtime-v<version> incluye:
#   - el código de packages/site-runtime en la raíz
#   - schema-pkg/  (copia real de packages/schema, sin symlink)
#   - vendor/      (composer install --no-dev -o ya resuelto)
#   - public/build/ (assets de vite ya compilados)
set -euo pipefail

VERSION="${1:?uso: release-site-runtime.sh <version>}"
BRANCH="site-runtime-v${VERSION}"
ROOT="$(git rev-parse --show-toplevel)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

echo "→ Preparando ${BRANCH} en ${WORK}"

# 1. Contenido de site-runtime en la raíz del release.
rsync -a --delete \
  --exclude '.git' --exclude 'vendor' --exclude 'node_modules' \
  --exclude 'public/build' --exclude 'storage/*.key' --exclude '.env' \
  "${ROOT}/packages/site-runtime/" "${WORK}/"

# 2. schema como copia real (no path-repo con symlink).
rsync -a --exclude 'vendor' --exclude '.git' "${ROOT}/packages/schema/" "${WORK}/schema-pkg/"

# 3. composer.json → apuntar el path repo a ./schema-pkg y fijar la versión.
php -r '
  $f = $argv[1];
  $c = json_decode(file_get_contents($f), true);
  $c["repositories"] = [["type" => "path", "url" => "./schema-pkg", "options" => ["symlink" => false]]];
  file_put_contents($f, json_encode($c, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
' "${WORK}/composer.json"

# 4. Dependencias y assets, ya resueltos, dentro del release.
#    `update webparaguay/schema` reescribe sólo esa entrada del lock (que
#    todavía apunta a ../schema) para que resuelva desde ./schema-pkg.
( cd "${WORK}" && composer update webparaguay/schema \
    --no-dev --no-interaction --optimize-autoloader --classmap-authoritative --no-audit )
( cd "${ROOT}/packages/site-runtime" && npm ci --silent && npm run build --silent )
rsync -a "${ROOT}/packages/site-runtime/public/build/" "${WORK}/public/build/"

# 5. .env.production de referencia (sin secretos).
cat > "${WORK}/.env.example" <<'ENV'
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
SITE_RUNTIME_INTERNAL_TOKEN=
SITE_RUNTIME_SITE_REF=
ENV

# 6. Commit huérfano en la rama de release y push.
#    El release SÍ versiona vendor/ y public/build/ (deploy sin composer/npm).
cd "${WORK}"
cat > .gitignore <<'IGN'
/.env
/storage/*.key
/storage/framework/cache/data/*
/storage/framework/sessions/*
/storage/framework/views/*
/storage/logs/*
/node_modules
IGN
git init -q
git checkout -q --orphan "${BRANCH}"
git add -A
git -c user.name="release" -c user.email="release@webparaguay.com" \
  commit -qm "site-runtime v${VERSION} (autocontenido)"
git remote add origin "$(git -C "${ROOT}" remote get-url origin)"
git push -f origin "${BRANCH}"

echo "✓ origin/${BRANCH} listo. Apuntá los subscriptions de Plesk a esta rama."
echo "  Deploy en el servidor:  git pull && php artisan migrate --force && php artisan config:cache"
