# PROOQ V2

Monorepo de [prooq.com](https://prooq.com) — portal raíz + 4 sucursales país, componentes compartidos y backend PHP unificado.

> Plan completo y fases en [PROOQ_V2_MIGRATION.md](PROOQ_V2_MIGRATION.md).
> Documentación del sitio V1 (que se está migrando) en [LEGACY_V1.md](LEGACY_V1.md).

---

## 🌎 Sucursales

| País | Carpeta | URL pública | DB (`country`) |
|------|---------|------------|----------------|
| 🇵🇦 Panamá | [apps/pty/](apps/pty/) | `prooq.com/pty/` | `PA` |
| 🇺🇸 Estados Unidos | [apps/usa/](apps/usa/) | `prooq.com/usa/` | `US` |
| 🇪🇸 España | [apps/esp/](apps/esp/) | `prooq.com/esp/` | `ES` |
| 🇻🇪 Venezuela | [apps/ven/](apps/ven/) | `prooq.com/ven/` | `VE` |
| 🌐 Portal raíz | [apps/portal/](apps/portal/) | `prooq.com` | — |

**Convención:** nombre de carpeta == path de la sucursal en producción (3 letras). DB usa ISO‑2. API queda separada en `api.prooq.com`.

---

## 🏗️ Estructura

```
prooqV2/
├── apps/                # ✅ scaffold completo (Astro 5 estático, BaseLayout + index)
│   ├── portal/          # prooq.com — selector de sucursales con video
│   ├── pty/             # prooq.com/pty (Panamá — la más completa)
│   ├── usa/             # prooq.com/usa
│   ├── esp/             # prooq.com/esp
│   └── ven/             # prooq.com/ven
│
├── packages/            # ✅ scaffold completo
│   ├── config/          # tsconfig, biome, tokens TS, tipos Country, SOCIAL_LINKS
│   ├── styles/          # Tailwind v4 (@theme tokens + globals + reset)
│   ├── ui/              # 7 componentes Astro (Header, Footer, ChatFab, CountriesMenu, HeroParallax, NeonPriceSticker, SocialLinks)
│   └── data-client/     # cliente TS tipado para api.prooq.com (clients, downloads, chat)
│
├── api/                 # ✅ backend PHP 8.3 + Slim 4 (api.prooq.com) — 4 rutas + CORS + RateLimit
├── db/                  # ✅ 5 migraciones MySQL + carpeta seeds (vacía)
├── scripts/             # ✅ deploy.sh + migrate-json-to-sql.ts + sqlite-export.ts
└── .github/workflows/   # ✅ ci.yml (lint+typecheck+build+composer) + deploy.yml (rsync a Hostinger)
```

---

## 🚀 Quickstart

**Requisitos:** Node 20+ (`nvm use`), pnpm 9+, PHP 8.3 (solo para `api/`).

```bash
# 1. Instalar dependencias del workspace
pnpm install

# 2. Configurar entorno
cp .env.example .env

# 3. Levantar todas las apps en paralelo
pnpm dev

# 4. Build de producción
pnpm build

# 5. Lint + format
pnpm lint
pnpm format
```

### Trabajar en una sola sucursal

```bash
pnpm --filter @prooq/pty dev     # solo Panamá
pnpm --filter @prooq/esp build   # solo España
```

---

## 🛠️ Stack

| Capa | Tecnología |
|------|------------|
| Frontend | Astro 5 + Tailwind 4 + TypeScript 5 + Alpine.js 3 |
| Backend | PHP 8.3 + Slim 4 + PDO/MySQL |
| Datos | MySQL 8 (Hostinger) |
| Infra | Hostinger Business + Cloudflare + GitHub Actions |
| Tooling | pnpm + Turborepo + Biome + Vitest + Playwright |

Detalle completo en §2 de [PROOQ_V2_MIGRATION.md](PROOQ_V2_MIGRATION.md).

---

## 📞 Contacto

- 🌐 [prooq.com](https://prooq.com)
- 📧 info@prooq.com
- 📍 Ciudad de Panamá, Panamá

© 2026 **PROOQ S.A.**
