#!/usr/bin/env bash
set -euo pipefail

# Deploy manual a Hostinger — usar solo como fallback cuando GitHub Actions falle.
# Requiere: HOSTINGER_HOST y HOSTINGER_USER en el entorno + clave SSH configurada.

HOST="${HOSTINGER_HOST:?HOSTINGER_HOST no definido}"
USER="${HOSTINGER_USER:?HOSTINGER_USER no definido}"
SSH="${USER}@${HOST}"
API_REMOTE="${HOSTINGER_API:-~/domains/api.prooq.com}"

echo "▶ Building monorepo..."
pnpm install --frozen-lockfile
pnpm build

echo "▶ Deploying portal → ${SSH}:~/public_html/"
rsync -av --delete --exclude 'pty' --exclude 'usa' --exclude 'esp' --exclude 'ven' --exclude 'api' \
    apps/portal/dist/ "${SSH}:~/public_html/"

for country in pty usa esp ven; do
    target="~/public_html/${country}/"
    echo "▶ Deploying ${country} → ${SSH}:${target}"
    rsync -av --delete "apps/${country}/dist/" "${SSH}:${target}"
done

echo "▶ Deploying API → ${SSH}:${API_REMOTE}/"
rsync -av --delete --exclude '.env' api/public/ "${SSH}:${API_REMOTE}/public_html/"
rsync -av --delete api/src/ "${SSH}:${API_REMOTE}/src/"
rsync -av api/composer.json api/composer.lock "${SSH}:${API_REMOTE}/"

echo "▶ Installing PHP deps on remote..."
ssh "${SSH}" "cd ${API_REMOTE} && composer install --no-dev --optimize-autoloader"

echo "✓ Done."
