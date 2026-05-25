#!/usr/bin/env bash
set -euo pipefail

# Deploy manual a Hostinger — usar solo como fallback cuando GitHub Actions falle.
# Requiere: HOSTINGER_HOST y HOSTINGER_USER en el entorno + clave SSH configurada.

HOST="${HOSTINGER_HOST:?HOSTINGER_HOST no definido}"
USER="${HOSTINGER_USER:?HOSTINGER_USER no definido}"
SSH="${USER}@${HOST}"
REMOTE_BASE="${HOSTINGER_BASE:-~/domains}"

echo "▶ Building monorepo..."
pnpm install --frozen-lockfile
pnpm build

echo "▶ Deploying portal → ${SSH}:~/public_html/"
rsync -av --delete apps/portal/dist/ "${SSH}:~/public_html/"

for country in pty usa esp ven; do
    target="${REMOTE_BASE}/${country}.prooq.com/public_html/"
    echo "▶ Deploying ${country} → ${SSH}:${target}"
    rsync -av --delete "apps/${country}/dist/" "${SSH}:${target}"
done

echo "▶ Deploying API → ${SSH}:${REMOTE_BASE}/api.prooq.com/"
rsync -av --delete --exclude '.env' api/public/ "${SSH}:${REMOTE_BASE}/api.prooq.com/public_html/"
rsync -av --delete api/src/ "${SSH}:${REMOTE_BASE}/api.prooq.com/src/"
rsync -av api/composer.json api/composer.lock "${SSH}:${REMOTE_BASE}/api.prooq.com/"

echo "▶ Installing PHP deps on remote..."
ssh "${SSH}" "cd ${REMOTE_BASE}/api.prooq.com && composer install --no-dev --optimize-autoloader"

echo "✓ Done."
