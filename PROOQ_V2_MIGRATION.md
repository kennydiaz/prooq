# 🚀 PROOQ V2 — Plan de Migración

> Plan formal para reescribir el sitio actual (`prooq/`) como **prooqV2** sobre un stack moderno y migrarlo de **GoDaddy → Hostinger**.
> Documento de trabajo. Fecha de inicio: 2026-05-25.

---

## 1. Objetivos

| # | Objetivo | Métrica de éxito |
|---|----------|------------------|
| 1 | Eliminar duplicación de código entre sucursales | 1 sola fuente de `Header`, `Footer`, `ChatFab`, `CountriesMenu` |
| 2 | Mejorar performance del sitio | Lighthouse ≥ 95 en Performance/SEO/A11y |
| 3 | Modernizar el flujo de desarrollo | Build local + git push → deploy automático |
| 4 | Migrar persistencia a base relacional | Cero archivos JSON/SQLite en producción |
| 5 | Mover hosting GoDaddy → Hostinger | Mismo dominio, sin downtime perceptible |
| 6 | Mantener N8N + chat existente | Webhooks siguen funcionando sin cambios |

**No objetivos** (fuera de alcance V2):
- Rediseño visual completo (mantenemos identidad actual).
- App móvil nativa.
- Sistema de autenticación de clientes (queda para V3).

---

## 2. Stack final

### Frontend
| Tecnología | Versión | Para qué |
|------------|---------|----------|
| **Astro** | 5.x | Generador estático multi‑página, ideal para multi‑sucursal con componentes compartidos |
| **Tailwind CSS** | 4.x | Estilos utility‑first, sin `tailwind.config.js` |
| **TypeScript** | 5.x | Type safety en componentes y APIs |
| **Alpine.js** | 3.x | Interactividad ligera en islands (chat fab, menús) |
| **Leaflet** | 1.9.x | Mapa interactivo (igual que hoy) |

### Backend
| Tecnología | Versión | Para qué |
|------------|---------|----------|
| **PHP** | 8.3 | Endpoints de formularios, descargas, proxy chat |
| **Slim Framework** | 4.x | Router HTTP ligero (1 archivo, sin overhead) |
| **PDO + MySQL** | — | Acceso a base de datos |
| **Composer** | 2.x | Gestión de dependencias PHP |

### Datos
| Tecnología | Para qué |
|------------|----------|
| **MySQL 8** (Hostinger) | Base relacional única (`prooq_v2`) |
| **phpMyAdmin** (hPanel) | Administración visual |
| **Migraciones SQL versionadas** | Carpeta `db/migrations/` con `001_init.sql`, etc. |

### Infraestructura
| Tecnología | Para qué |
|------------|----------|
| **Hostinger Business** | Hosting compartido con PHP 8.3, MySQL, Node (build), Git, SSL gratis |
| **Cloudflare** (DNS + CDN) | DNS, caché de assets, WAF gratis |
| **GitHub Actions** | CI: lint + build + deploy via SSH/FTP a Hostinger |
| **N8N** | Sin cambios — sigue en su servidor actual |
| **Sentry** (plan gratis) | Tracking de errores frontend y PHP |
| **Plausible / Umami** | Analytics ligero, sin cookies |

### Herramientas dev
- **pnpm** 9.x (más rápido que npm, monorepo nativo)
- **Biome** (lint + format, reemplaza ESLint + Prettier)
- **Vitest** (tests unitarios donde aplique)
- **Playwright** (tests E2E críticos: chat, formularios)

---

## 3. Arquitectura nueva (monorepo)

```
prooq-v2/
├── apps/
│   ├── portal/                 # Portal raíz (prooq.com) — Astro
│   ├── pty/                    # pty.prooq.com — Astro + APIs PHP (Panamá)
│   ├── usa/                    # usa.prooq.com — Astro (Estados Unidos)
│   ├── esp/                    # esp.prooq.com — Astro (España)
│   └── ven/                    # ven.prooq.com — Astro (Venezuela)
│
├── packages/
│   ├── ui/                     # Componentes Astro compartidos
│   │   ├── Header.astro
│   │   ├── Footer.astro
│   │   ├── ChatFab.astro
│   │   ├── CountriesMenu.astro
│   │   ├── HeroParallax.astro
│   │   └── NeonPriceSticker.astro
│   ├── styles/                 # Tailwind base + tokens compartidos
│   │   ├── tokens.css          # colores, fuentes, breakpoints
│   │   └── globals.css
│   ├── config/                 # Configuración compartida
│   │   ├── tsconfig.base.json
│   │   ├── biome.json
│   │   └── tailwind.preset.ts
│   └── data-client/            # Cliente TS para las APIs PHP
│       ├── clients.ts
│       ├── downloads.ts
│       └── chat.ts
│
├── api/                        # Backend PHP (Slim)
│   ├── public/
│   │   └── index.php           # Front controller
│   ├── src/
│   │   ├── Routes/
│   │   │   ├── clients.php
│   │   │   ├── downloads.php
│   │   │   ├── ebop.php        # Formulario e-BOP
│   │   │   └── chat.php        # Proxy a N8N
│   │   ├── Db/
│   │   │   └── Connection.php
│   │   └── Middleware/
│   │       ├── Cors.php
│   │       └── RateLimit.php
│   ├── composer.json
│   └── .env.example            # DB_HOST, DB_NAME, N8N_WEBHOOK, etc.
│
├── db/
│   ├── migrations/
│   │   ├── 001_init.sql
│   │   ├── 002_clients.sql
│   │   ├── 003_downloads.sql
│   │   ├── 004_ebop_requests.sql
│   │   └── 005_chat_logs.sql
│   └── seeds/
│       ├── clients.sql         # Migrados desde panama/data/clients.json
│       └── downloads.sql       # Migrados desde panama/data/downloads.json
│
├── scripts/
│   ├── deploy.sh               # SSH + rsync a Hostinger
│   ├── migrate-json-to-sql.ts  # One-shot: JSON → SQL inserts
│   └── sqlite-export.ts        # One-shot: prooq.db → SQL inserts
│
├── .github/
│   └── workflows/
│       ├── ci.yml              # Lint + build + tests en cada PR
│       └── deploy.yml          # Deploy a Hostinger en merge a main
│
├── pnpm-workspace.yaml
├── package.json
├── turbo.json                  # Orquestador de tareas (opcional)
├── .env.example
├── .gitignore
├── README.md
└── PROOQ_V2_MIGRATION.md       # Este documento
```

**Por qué monorepo:** un solo `pnpm install`, un solo deploy, componentes compartidos en `packages/ui/` que se importan desde cualquier app con `import { ChatFab } from '@prooq/ui'`.

**Convención de naming** (decidida 2026-05-25):

| Capa | Identificador | Valor por sucursal |
|------|--------------|---------------------|
| Carpeta en `apps/` | prefijo de 3 letras = subdominio | `pty`, `usa`, `esp`, `ven` |
| Subdominio público | igual al folder | `pty.prooq.com`, `usa.prooq.com`, `esp.prooq.com`, `ven.prooq.com` |
| DB (`country` column) | ISO‑3166 alpha‑2 | `PA`, `US`, `ES`, `VE` |

El alcance funcional es idéntico entre las 4 sucursales — solo cambian datos puntuales (equipo, dirección, formularios locales). Las carpetas no representan diferencias de producto, solo de despliegue y contenido.

---

## 4. Fases de migración

### Fase 0 — Setup (Semana 1)
- [ ] Crear cuenta Hostinger (plan **Business Web Hosting** mínimo).
- [ ] Crear repo `prooq-v2` en GitHub.
- [ ] Scaffold monorepo (ver §10 "Archivos para arrancar").
- [ ] Configurar SSH keys Hostinger ↔ GitHub Actions.
- [ ] Configurar Cloudflare con dominio `prooq.com` (todavía apuntando a GoDaddy).

### Fase 1 — Componentes compartidos (Semana 2-3)
- [ ] Extraer `Header`, `Footer`, `ChatFab`, `CountriesMenu`, `HeroParallax` a `packages/ui/`.
- [ ] Migrar paleta y tipografía a `packages/styles/tokens.css`.
- [ ] Setup Tailwind v4 con preset compartido.
- [ ] Pruebas visuales con Playwright (screenshots base).

### Fase 2 — España (Semana 4) 🇪🇸 *piloto*
**Es la más simple — solo `index.html` estático.** Sirve para validar el flujo end‑to‑end.
- [ ] Recrear `apps/esp/` en Astro consumiendo `@prooq/ui`.
- [ ] Migrar imágenes a `apps/esp/public/`.
- [ ] Build local + deploy a `esp-v2.prooq.com` (subdominio temporal).
- [ ] Validación manual + Lighthouse.

### Fase 3 — EE. UU. y Venezuela (Semana 5-6) 🇺🇸 🇻🇪
- [ ] Estructura espejo (ambas son casi idénticas).
- [ ] Migrar `servicios.php`, `gallery.php`, `downloads.php` → páginas Astro que llaman a `api.prooq.com`.
- [ ] Deploy a `usa-v2.prooq.com` y `ven-v2.prooq.com`.

### Fase 4 — Panamá (Semana 7-9) 🇵🇦 *la más compleja*
- [ ] Páginas básicas (index, servicios, gallery) como las otras.
- [ ] Formulario **e‑BOP** → endpoint PHP `/api/ebop`.
- [ ] **Clientes** → endpoint PHP `/api/clients` (lee de MySQL).
- [ ] **Descargas** → endpoint PHP `/api/downloads` + `dl.php` para servir archivos.
- [ ] **HubCore** → evaluar si se mantiene como SPA aparte o se integra.
- [ ] Deploy a `pty-v2.prooq.com`.

### Fase 5 — Migración de datos (paralela a Fase 4)
- [ ] Crear schema en MySQL (ejecutar `db/migrations/*.sql`).
- [ ] Script `migrate-json-to-sql.ts`:
  - Lee `panama/data/clients.json` → INSERT en `clients`.
  - Lee `panama/data/downloads.json` → INSERT en `downloads`.
- [ ] Script `sqlite-export.ts`:
  - Lee `panama/data/prooq.db` (SQLite) → INSERT en tablas correspondientes.
- [ ] Validación: contar filas, comparar con originales.

### Fase 6 — Portal raíz + DNS cutover (Semana 10)
- [ ] Recrear `apps/portal/` (el `index.html` raíz con el video).
- [ ] Deploy a `v2.prooq.com`.
- [ ] **Pruebas de regresión completas** en todos los subdominios `*-v2`.
- [ ] **Cutover DNS** en Cloudflare:
  1. Bajar TTL a 60s 24h antes.
  2. Cambiar registros A/CNAME GoDaddy → Hostinger.
  3. Verificar propagación.
  4. Mantener servidor viejo encendido 7 días.

### Fase 7 — Post‑migración (Semana 11+)
- [ ] Activar Sentry en producción.
- [ ] Instalar Plausible/Umami.
- [ ] Configurar backups automáticos MySQL en Hostinger.
- [ ] Apagar y descargar respaldo de GoDaddy.
- [ ] Documentar runbook para nuevo equipo.

---

## 5. Configuración Hostinger

### Plan recomendado
**Business Web Hosting** ($3.99–$8.99/mes según promo):
- 100 sitios web
- PHP 8.3, MySQL 8 ilimitado
- Node.js disponible (para builds en hPanel)
- SSL gratis (Let's Encrypt)
- Git deployment integrado
- 200 GB NVMe + CDN incluido
- LiteSpeed Web Server (más rápido que Apache para archivos estáticos)

### Setup inicial en hPanel
1. **Crear cuenta de hosting** y asignar dominio principal `prooq.com`.
2. **Subdominios** (uno por sucursal):
   - `pty.prooq.com`
   - `usa.prooq.com`
   - `esp.prooq.com`
   - `ven.prooq.com`
   - `api.prooq.com` (backend PHP unificado)
3. **MySQL**: crear base `u123_prooq_v2` + usuario con privilegios.
4. **Git deployment**: conectar repo GitHub, configurar webhook a rama `main`.
5. **SSL**: activar Let's Encrypt para todos los subdominios.
6. **Cron**: agendar backups MySQL diarios a las 03:00.

### Estructura en el servidor
```
public_html/                    # → prooq.com (apps/portal/dist/)
pty.prooq.com/                  # → apps/pty/dist/
usa.prooq.com/                  # → apps/usa/dist/
esp.prooq.com/                  # → apps/esp/dist/
ven.prooq.com/                  # → apps/ven/dist/
api.prooq.com/                  # → api/public/ (PHP)
```

---

## 6. Esquema de base de datos (MySQL)

`db/migrations/001_init.sql`:
```sql
CREATE DATABASE prooq_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE prooq_v2;
```

`db/migrations/002_clients.sql`:
```sql
CREATE TABLE clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  logo_url VARCHAR(500),
  website VARCHAR(500),
  country CHAR(2),               -- PA, US, ES, VE
  industry VARCHAR(100),
  display_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

`db/migrations/003_downloads.sql`:
```sql
CREATE TABLE downloads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  filename VARCHAR(255) NOT NULL,
  file_size_bytes BIGINT,
  mime_type VARCHAR(100),
  country CHAR(2),
  download_count INT DEFAULT 0,
  is_public TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

`db/migrations/004_ebop_requests.sql`:
```sql
CREATE TABLE ebop_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(255) NOT NULL,
  contact_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  message TEXT,
  status ENUM('new','contacted','closed') DEFAULT 'new',
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status_created (status, created_at)
);
```

`db/migrations/005_chat_logs.sql`:
```sql
CREATE TABLE chat_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  conversation_id VARCHAR(64) NOT NULL,
  role ENUM('user','assistant') NOT NULL,
  message TEXT NOT NULL,
  source_site CHAR(2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conversation (conversation_id)
);
```

---

## 7. Estrategia DNS (dos opciones)

### Opción A — `v2.prooq.com` en paralelo (cero riesgo)
1. Hostinger sirve `v2.prooq.com` y subdominios `*-v2.prooq.com`.
2. GoDaddy sigue sirviendo `prooq.com` real durante toda la migración.
3. Cuando V2 esté 100% validado, en Cloudflare se cambia el registro A de `prooq.com` a Hostinger.
4. Rollback: revertir el A record (segundos).

### Opción B — Cutover directo
1. Trabajar todo en local + staging Hostinger.
2. Día del lanzamiento: cambiar nameservers GoDaddy → Cloudflare → Hostinger.
3. Más rápido, pero ventana de riesgo de 24-48h.

**Recomendación: Opción A.**

---

## 8. CI/CD con GitHub Actions

`.github/workflows/deploy.yml`:
```yaml
name: Deploy to Hostinger
on:
  push:
    branches: [main]

jobs:
  build-deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: pnpm/action-setup@v3
        with: { version: 9 }
      - uses: actions/setup-node@v4
        with: { node-version: 20, cache: pnpm }
      - run: pnpm install --frozen-lockfile
      - run: pnpm build
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.HOSTINGER_HOST }}
          username: ${{ secrets.HOSTINGER_USER }}
          key: ${{ secrets.HOSTINGER_SSH_KEY }}
          script: |
            cd ~/prooq-v2 && git pull
            rsync -av apps/portal/dist/ ~/public_html/
            rsync -av apps/pty/dist/    ~/domains/pty.prooq.com/public_html/
            rsync -av apps/usa/dist/    ~/domains/usa.prooq.com/public_html/
            rsync -av apps/esp/dist/    ~/domains/esp.prooq.com/public_html/
            rsync -av apps/ven/dist/    ~/domains/ven.prooq.com/public_html/
            cd ~/domains/api.prooq.com/public_html && composer install --no-dev
```

---

## 9. Plan de rollback

| Si falla… | Rollback |
|-----------|----------|
| Build de Astro | GitHub Action no despliega, último build sigue activo |
| Deploy a Hostinger | `rsync` mantiene timestamp; restaurar carpeta `_backup/` previa |
| Migración DNS | Revertir A record en Cloudflare (60s TTL) |
| Migración de datos | Dump MySQL antes de cada migración + GoDaddy intacto 7 días |
| Endpoint PHP roto | Mantener `prooq.com/api-legacy/` apuntando al PHP viejo durante 30 días |

---

## 10. 📦 Archivos para arrancar `prooqV2` (bootstrap)

Esta es la lista exacta de archivos a crear en el repo nuevo `prooq-v2/` el día 1. Sin contenido todavía — solo el esqueleto vacío para luego ir llenando.

### Raíz del repo
- `package.json` — workspace root + scripts (`build`, `dev`, `lint`)
- `pnpm-workspace.yaml` — declara `apps/*` y `packages/*`
- `turbo.json` — pipeline (`build` depende de `^build`)
- `tsconfig.json` — extends `packages/config/tsconfig.base.json`
- `biome.json` — config de lint + format
- `.gitignore` — `node_modules`, `dist`, `.env`, `.astro`
- `.env.example` — variables públicas (`PUBLIC_API_URL`, etc.)
- `README.md` — quick start del monorepo
- `.nvmrc` — `20`

### `.github/workflows/`
- `ci.yml` — lint + typecheck + build en cada PR
- `deploy.yml` — deploy a Hostinger en push a `main`

### `packages/config/`
- `package.json`
- `tsconfig.base.json`
- `biome.json`
- `tailwind.preset.ts`

### `packages/styles/`
- `package.json`
- `tokens.css` — variables CSS (colores, fuentes, spacing)
- `globals.css` — `@import "tailwindcss"` + resets

### `packages/ui/`
- `package.json`
- `astro.config.ts` (mínimo para que los `.astro` compilen)
- `src/Header.astro`
- `src/Footer.astro`
- `src/ChatFab.astro`
- `src/CountriesMenu.astro`
- `src/HeroParallax.astro`
- `src/NeonPriceSticker.astro`
- `src/SocialLinks.astro` — **componente unificado con las 8 redes** (ver §14)
- `src/index.ts` — re‑exports

### `packages/data-client/`
- `package.json`
- `src/clients.ts`
- `src/downloads.ts`
- `src/chat.ts`
- `src/index.ts`

### `apps/portal/` (y mismo esqueleto en `pty/`, `usa/`, `esp/`, `ven/`)
- `package.json`
- `astro.config.mjs`
- `tsconfig.json`
- `src/pages/index.astro`
- `src/layouts/BaseLayout.astro`
- `public/` (vacío, para imágenes y `world.mp4`)

### `api/`
- `composer.json`
- `public/index.php` — front controller Slim
- `public/.htaccess` — rewrite a `index.php`
- `src/Routes/clients.php`
- `src/Routes/downloads.php`
- `src/Routes/ebop.php`
- `src/Routes/chat.php`
- `src/Db/Connection.php`
- `src/Middleware/Cors.php`
- `src/Middleware/RateLimit.php`
- `.env.example`

### `db/`
- `migrations/001_init.sql`
- `migrations/002_clients.sql`
- `migrations/003_downloads.sql`
- `migrations/004_ebop_requests.sql`
- `migrations/005_chat_logs.sql`
- `seeds/.gitkeep`

### `scripts/`
- `deploy.sh`
- `migrate-json-to-sql.ts`
- `sqlite-export.ts`

**Total**: ~55 archivos para tener el monorepo funcional en vacío.

---

## 11. Timeline resumido

| Semana | Hito |
|--------|------|
| 1 | Setup Hostinger + repo + monorepo vacío |
| 2-3 | Componentes compartidos (`packages/ui`) |
| 4 | España migrada (piloto) |
| 5-6 | USA + Venezuela migrados |
| 7-9 | Panamá migrado + API PHP + datos |
| 10 | Portal raíz + cutover DNS |
| 11+ | Monitoreo, backups, apagado GoDaddy |

**Esfuerzo estimado**: ~10 semanas a media dedicación (1 dev part‑time).

---

## 12. Costos mensuales estimados

| Servicio | Costo |
|----------|-------|
| Hostinger Business (anual) | ~$4/mes |
| Cloudflare DNS + CDN | $0 (plan free) |
| GitHub | $0 (repo público o privado free) |
| Sentry | $0 (free tier: 5k errores/mes) |
| Plausible self‑hosted | $0 (se hostea en Hostinger) |
| Dominio `prooq.com` (Hostinger) | ~$10/año |
| **Total mensual** | **~$5/mes** |

Vs. GoDaddy actual (~$10–15/mes según plan), ahorras ~50% y duplicas capacidades.

---

## 13. Redes sociales — fuente única

En V2, **un solo componente** `<SocialLinks />` en `packages/ui/src/SocialLinks.astro` consume esta config (`packages/config/social.ts`) y se inyecta en el footer de cada sucursal:

```ts
// packages/config/social.ts
export const SOCIAL_LINKS = [
  { name: 'YouTube',        handle: '@prooqsa',          url: 'https://www.youtube.com/@prooqsa',           icon: 'youtube'   },
  { name: 'Instagram',      handle: '@prooq',            url: 'https://www.instagram.com/prooq/',           icon: 'instagram' },
  { name: 'Facebook',       handle: '/prooq',            url: 'https://www.facebook.com/prooq/',            icon: 'facebook'  },
  { name: 'X (Twitter)',    handle: '@prooqllc',         url: 'https://x.com/prooqllc',                     icon: 'x'         },
  { name: 'LinkedIn',       handle: 'company/75542387',  url: 'https://www.linkedin.com/company/75542387/', icon: 'linkedin'  },
  { name: 'TikTok',         handle: '@prooqsa',          url: 'https://www.tiktok.com/@prooqsa',            icon: 'tiktok'    },
  { name: 'GitHub',         handle: '/prooq',            url: 'https://github.com/prooq',                   icon: 'github'    },
  { name: 'Google Business',handle: 'share',             url: 'https://share.google/FAt5Z7HtEK2NiKFsK',     icon: 'google'    },
  { name: 'WhatsApp',       handle: '+507 6208-2617',    url: 'https://wa.me/50762082617',                  icon: 'whatsapp'  },
] as const;
```

**Problemas que resuelve:**
- Hoy las sucursales muestran **7** redes, [social.html](social.html) muestra **8** (Google Business solo está ahí).
- [espana/index.html](espana/index.html) **no tiene** sección de redes sociales — solo WhatsApp como contacto.
- Cualquier cambio de handle hoy requiere editar 3+ archivos manualmente.

**Total: 9 redes/canales oficiales** (decidido el 2026-05-25 — incluir WhatsApp Business como 9ª).
> ⚠️ Confirmar número de WhatsApp definitivo (actual asumido: `+507 6208-2617`).

---

## 14. Decisiones pendientes

- [ ] Plan exacto de Hostinger (Business vs Cloud Startup).
- [ ] ¿`api.prooq.com` separado o `prooq.com/api`? (recomendado: separado para cache rules).
- [ ] ¿Migrar HubCore en V2 o V3?
- [ ] ¿Mover `info_agent_prompt.txt` a la base de datos para edición sin deploy?
- [ ] Estrategia de imágenes: ¿optimización en build con `astro:assets` o CDN externo?

---

© 2026 PROOQ S.A. — Plan de migración prooqV2.
