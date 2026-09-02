#!/usr/bin/env bash
# Publica el paquete versionado de site-runtime como una rama desplegable.
# El servidor Plesk hace `git pull` de esta rama. Nunca se despliega a mano.
#
#   scripts/release-site-runtime.sh 0.1.0
set -euo pipefail
VERSION="${1:?uso: release-site-runtime.sh <version>}"
BRANCH="site-runtime-v${VERSION}"

git rev-parse --verify HEAD >/dev/null
git subtree split --prefix=packages/site-runtime -b "${BRANCH}"
git push -f origin "${BRANCH}"
git branch -D "${BRANCH}"
echo "Listo: origin/${BRANCH} — apuntá los subscriptions de Plesk a esta rama."
