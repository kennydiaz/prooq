# PROOQ V2

Monorepo de [prooq.com](https://prooq.com) — portal raíz + 4 sucursales país, panel admin (CMS), componentes compartidos y backend PHP unificado.

> Plan completo y fases en [PROOQ_V2_MIGRATION.md](PROOQ_V2_MIGRATION.md).
> Documentación del sitio V1 (que se está migrando) en [LEGACY_V1.md](LEGACY_V1.md).

---

## 🌎 Sucursales y apps

| App | Carpeta | URL pública | DB (`country`) | Fondo animado |
|-----|---------|-------------|----------------|---------------|
| 🌐 Portal raíz | [apps/portal/](apps/portal/) | `prooq.com` | — | Warp / hiperespacio |
| 🇵🇦 Panamá | [apps/pty/](apps/pty/) | `prooq.com/pty/` | `PA` | Starfield |
| 🇺🇸 Estados Unidos | [apps/usa/](apps/usa/) | `prooq.com/usa/` | `US` | Red de constelaciones |
| 🇪🇸 España | [apps/esp/](apps/esp/) | `prooq.com/esp/` | `ES` | Nebulosa pulsante |
| 🇻🇪 Venezuela | [apps/ven/](apps/ven/) | `prooq.com/ven/` | `VE` | Lluvia de meteoros |
| 🛠️ Admin (CMS) | [apps/admin/](apps/admin/) | `prooq.com/admin` | — | — |

**Convención:** nombre de carpeta == path de la sucursal en producción (3 letras). DB usa ISO‑2. La API queda separada en `api.prooq.com`.

---

## 🛠️ Panel admin (CMS) — `prooq.com/admin`

Astro estático + sesión por cookie (httponly) contra la API. Crece **por módulos** (nav lateral izquierdo, array `modules` en `AdminLayout`):

- **📊 Dashboard** — analytics de visitas: KPIs (totales, hoy, 7d, IPs únicas, países), gráficas (visitas por día y por país, Chart.js) y tabla de visitas recientes con **IP + país**.
- **🖼️ Galería** — subir / ordenar / publicar imágenes por país (o globales).
- **👥 Equipo** — miembros del equipo; relación **muchos‑a‑muchos** con las sucursales (un miembro puede estar en varias; cada país muestra los suyos).

> Agregar un módulo nuevo: sumar entrada al array `modules` en `apps/admin/src/layouts/AdminLayout.astro` + crear `apps/admin/src/pages/<modulo>.astro` + su ruta admin en `api/` y su cliente en `packages/data-client/`.

Los sitios públicos consumen el CMS en **build‑time** (`getGallery`, `getTeam`) con fallback al contenido hardcodeado; los cambios del CMS se reflejan tras un **rebuild/redeploy**.

---

## 🏗️ Estructura

```
prooqV2/
├── apps/                # Astro 5 estático
│   ├── portal/          # prooq.com — selector de sucursales
│   ├── pty/ usa/ esp/ ven/   # sucursales país
│   └── admin/           # prooq.com/admin — panel CMS (Dashboard, Galería, Equipo)
│
├── packages/
│   ├── config/          # tsconfig, biome, tokens TS, tipos Country, SOCIAL_LINKS
│   ├── styles/          # Tailwind v4 (@theme tokens + globals + sections)
│   ├── ui/              # componentes Astro: base (Header, Footer, ChatFab, CountriesMenu,
│   │                    #   HeroParallax, NeonPriceSticker, SocialLinks), fondos animados
│   │                    #   (Warp, Starfield, ConstellationNetwork, Nebula, MeteorShower) y VisitTracker
│   └── data-client/     # cliente TS tipado para la API: clients, downloads, chat,
│                        #   gallery, stats, track, team
│
├── api/                 # backend PHP + Slim 4 (api.prooq.com)
│                        #   rutas: clients, downloads, chat, admin (login/logout/me),
│                        #   gallery, track, stats, team · middleware: Cors, RateLimit, AdminAuth
├── db/migrations/       # 8 migraciones MySQL (001_init … 008_team)
├── scripts/             # deploy + migración de datos del V1
└── .github/workflows/   # ci.yml (lint + typecheck + build) + deploy.yml
```

---

## 🚀 Quickstart (frontend)

**Requisitos:** Node 20+, pnpm 9+.

```bash
pnpm install          # dependencias del workspace
pnpm dev              # todas las apps en paralelo (cada una en su puerto 43xx)
pnpm build            # build de producción
pnpm lint             # biome check
pnpm typecheck        # astro check por app
```

Trabajar en una sola app:

```bash
pnpm --filter @prooq/pty dev      # solo Panamá  -> /pty
pnpm --filter @prooq/admin dev    # solo admin   -> /admin
```

> Antes de hacer push: `pnpm lint && pnpm typecheck && pnpm build`. El CI los corre y aborta en el primer fallo. Biome formatea estricto (incluye el frontmatter `.astro`) y `astro check` usa `noUncheckedIndexedAccess`.

---

## 🔌 Backend local (API PHP + MySQL)

El panel admin y el contenido dinámico (galería/equipo/visitas) necesitan la API + base de datos. En local (XAMPP):

```bash
# 1. Dependencias PHP (PHP 8.2 corre bien aunque composer.json pida ^8.3)
cd api && composer install --ignore-platform-req=php && cd ..

# 2. Base de datos + migraciones (MySQL/MariaDB de XAMPP, root sin password)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS prooq_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
for f in db/migrations/*.sql; do mysql -u root prooq_v2 < "$f"; done

# 3. Levantar la API
php -S localhost:8787 -t api/public api/public/index.php

# 4. Apuntar las apps a la API local (.env por app — gitignored)
echo "PUBLIC_API_URL=http://localhost:8787" > apps/admin/.env
#   (idem en apps/pty, apps/usa, apps/esp, apps/ven para galería/equipo en dev)
```

**Usuario admin:** no hay usuario por defecto; se inserta en `admin_users` (password con `password_hash`, bcrypt). Las credenciales de producción viven en la BD de Hostinger.

Variables de entorno: ver [.env.example](.env.example) (raíz) y [api/.env.example](api/.env.example).

---

## 🧰 Stack

| Capa | Tecnología |
|------|------------|
| Frontend | Astro 5 + Tailwind 4 + TypeScript 5 |
| Gráficas | Chart.js 4 (Dashboard) |
| Backend | PHP 8 + Slim 4 + PDO/MySQL |
| Datos | MySQL / MariaDB |
| Infra | Hostinger + Cloudflare + GitHub Actions |
| Tooling | pnpm + Turborepo + Biome |

Detalle completo en §2 de [PROOQ_V2_MIGRATION.md](PROOQ_V2_MIGRATION.md).

---

## 📞 Contacto

- 🌐 [prooq.com](https://prooq.com)
- 📧 info@prooq.com
- 📍 Ciudad de Panamá, Panamá

© 2026 **PROOQ S.A.**
