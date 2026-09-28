#!/usr/bin/env bash
set -euo pipefail

# RETIRADO 2026-09-27. Este respaldo quedó desfasado: publica la API en
# ~/domains/api.prooq.com (la real está en ~/domains/prooq.com/public_html/api,
# docroot api/public) y usa `rsync --delete`, que en esas rutas borraría archivos
# en producción. El único despliegue válido es .github/workflows/deploy.yml
# (Actions → Deploy → Run workflow si hace falta relanzarlo a mano).
echo "scripts/deploy.sh está retirado: usa el workflow .github/workflows/deploy.yml" >&2
exit 1

# Deploy manual a Hostinger — usar solo como fallback cuando GitHub Actions falle.
# Requiere: HOSTINGER_HOST y HOSTINGER_USER en el entorno + clave SSH configurada.

HOST="${HOSTINGER_HOST:?HOSTINGER_HOST no definido}"
USER="${HOSTINGER_USER:?HOSTINGER_USER no definido}"
SSH="${USER}@${HOST}"
API_REMOTE="${HOSTINGER_API:-~/domains/api.prooq.com}"

# IMPORTANTE: el API y las migraciones van ANTES del build. El blog es contenido
# de BD: cada app lo consulta al API en vivo durante `pnpm build`. Si
# construyéramos primero, el API aún no tendría la ruta /api/blog ni la tabla
# poblada y el blog saldría vacío. Orden: deps → API → migrar → build → frontends.

echo "▶ Installing JS deps..."
pnpm install --frozen-lockfile

echo "▶ Deploying API → ${SSH}:${API_REMOTE}/"
rsync -av --delete --exclude '.env' api/public/ "${SSH}:${API_REMOTE}/public_html/"
rsync -av --delete api/src/ "${SSH}:${API_REMOTE}/src/"
rsync -av --delete api/bin/ "${SSH}:${API_REMOTE}/bin/"
rsync -av --delete db/migrations/ "${SSH}:${API_REMOTE}/migrations/"
rsync -av api/composer.json api/composer.lock "${SSH}:${API_REMOTE}/"

echo "▶ Installing PHP deps + running migrations on remote..."
ssh "${SSH}" "cd ${API_REMOTE} && composer install --no-dev --optimize-autoloader && php bin/migrate.php"

echo "▶ Building monorepo (el blog se trae del API ya actualizado)..."
pnpm build

echo "▶ Deploying portal → ${SSH}:~/public_html/"
rsync -av --delete --exclude 'pty' --exclude 'usa' --exclude 'esp' --exclude 'ven' --exclude 'api' \
    apps/portal/dist/ "${SSH}:~/public_html/"

for country in pty usa esp ven; do
    target="~/public_html/${country}/"
    echo "▶ Deploying ${country} → ${SSH}:${target}"
    rsync -av --delete "apps/${country}/dist/" "${SSH}:${target}"
done

echo "✓ Done."
