# PROOQ — Sitio Web de Presencia Global

Sitio corporativo de **PROOQ** con video de fondo (`world.mp4`) que actúa como portal de entrada a cada una de las sucursales del grupo. Cada sucursal es un sub‑sitio independiente con su propia identidad, contenidos y stack.

🌐 **Producción**: <https://prooq.com>

---

## 🌎 Sucursales

| País | Ciudad | Subdominio | Stack | Carpeta |
|------|--------|------------|-------|---------|
| 🇵🇦 Panamá | Ciudad de Panamá | `pty.prooq.com` | PHP + JSON/SQLite | [panama/](panama/) |
| 🇺🇸 Estados Unidos | Chicago | `usa.prooq.com` | HTML + PHP | [eeuu/](eeuu/) |
| 🇪🇸 España | Cádiz / Andalucía | `esp.prooq.com` | HTML estático | [espana/](espana/) |
| 🇻🇪 Venezuela | Caracas | — | HTML + PHP | [venezuela/](venezuela/) |

El portal raíz ([index.html](index.html)) muestra una tarjeta por sucursal sobre el video del globo terráqueo y enlaza al `index` de cada carpeta.

---

## 🗂️ Estructura del repositorio

```
prooq/
├── index.html              # Portal raíz con selector de sucursales (video de fondo)
├── social.html             # Landing de redes sociales
├── script.js               # Interactividad del portal raíz
├── styles.css              # Estilos fuente del portal
├── styles.min.css          # Estilos minificados (en uso por index.html)
├── world.mp4               # Video de fondo del globo terráqueo
├── README.md
│
├── images/                 # Recursos compartidos del portal
│   ├── logo.png, logo2.png, logo3.png
│   ├── prooq_digital_2.png
│   ├── pty.jpg, usa.jpg, esp.jpg, ven.jpg          # Fotos de sucursales
│   └── ptyflag.png, usaflag.jpg, espflag.jpg, venflag.jpg
│
├── panama/                 # 🇵🇦 Sucursal PHP (la más completa)
├── eeuu/                   # 🇺🇸 Sucursal HTML + PHP
├── espana/                 # 🇪🇸 Sucursal HTML estática
└── venezuela/              # 🇻🇪 Sucursal HTML + PHP
```

---

## 🇵🇦 Panamá — `panama/`

Sucursal principal y más completa. Incluye gestión de clientes, descargas dinámicas, formularios y un módulo *hubcore*.

```
panama/
├── index.php                       # Home con hero, servicios y chat
├── servicios.php                   # Catálogo de servicios
├── gallery.php                     # Galería de proyectos
├── clientes.php                    # Listado de clientes (lee data/clients.json)
├── downloads.php                   # Centro de descargas
├── dl.php                          # Endpoint de descarga (proxy)
├── hubcore.php                     # Módulo HubCore
├── e-bop.html                      # Landing e‑BOP
├── solicitud-ebop.php              # Formulario de solicitud e‑BOP
├── logout-afluencia.php            # Cierre de sesión (afluencia)
├── setup-afluencia.sql             # Script de creación de tablas afluencia
├── boton_chat.html                 # Snippet del botón flotante de chat
├── chat-widget-standalone.html     # Widget de chat portable (copy/paste)
├── favicon-generator.html          # Utilidad de generación de favicons
├── manifest.json                   # PWA manifest
├── info_agent_prompt.txt           # Prompt del agente N8N
│
├── css/
│   ├── styles.css, header.css
│   ├── hero-parallax.css
│   ├── neon-price-sticker.css
│   └── components/                 # countries-menu, etc.
├── js/
│   ├── parallax.js, nav.js
│   ├── chat.js, chat-fab.js        # Widget de chat con N8N
│   └── components/
├── includes/
│   └── nav.php                     # Navegación reutilizable
├── data/
│   ├── clients.json                # Catálogo de clientes
│   ├── downloads.json              # Catálogo de descargables
│   └── prooq.db                    # Base SQLite local
├── images/                         # Fotos del equipo, servicios, hero
├── downloads/                      # Archivos descargables
└── videos/                         # Recursos de video
```

**Características destacadas**
- Chat flotante integrado con **N8N** (webhook + respuestas IA).
- PWA con `manifest.json` y favicon multiformato.
- Mapa interactivo con **Leaflet.js**.
- Datos servidos desde JSON + base **SQLite** (`data/prooq.db`).
- Tipografías Montserrat + Orbitron (Google Fonts).

---

## 🇺🇸 Estados Unidos — `eeuu/`

```
eeuu/
├── index.html                      # Home estático
├── servicios.php, gallery.php, downloads.php
├── boton_chat.html, chat-widget-standalone.html
├── favicon-generator.html, manifest.json
├── info_agent_prompt.txt
├── css/  (styles, header, hero-parallax, neon-price-sticker, components/)
├── js/   (parallax, nav, chat, chat-fab, components/)
├── images/
└── downloads/
```

Mismo patrón visual que Panamá pero con `index.html` estático. Tres páginas dinámicas en PHP (`servicios`, `gallery`, `downloads`) y el mismo widget de chat.

---

## 🇪🇸 España — `espana/`

Sucursal más liviana, **100 % estática**:

```
espana/
├── index.html
├── styles.css
├── chat-fab.js              # Botón flotante de chat
└── images/
```

Enfocada en portafolio web y agentes inteligentes para clientes en Cádiz / Andalucía.

---

## 🇻🇪 Venezuela — `venezuela/`

Espejo estructural de la sucursal de EE. UU.:

```
venezuela/
├── index.html
├── servicios.php, gallery.php, downloads.php
├── boton_chat.html, chat-widget-standalone.html
├── favicon-generator.html, manifest.json
├── info_agent_prompt.txt
├── css/  (styles, header, hero-parallax, neon-price-sticker, components/)
├── js/   (parallax, nav, chat, chat-fab, components/)
├── images/
└── downloads/
```

---

## 🧩 Componentes transversales

- **Widget de chat (`chat-fab.js` + `chat-widget-standalone.html`)** — Botón flotante con modal y conexión a webhook de N8N. Versión *standalone* portable para integrar en otros sitios.
- **Menú de países (`js/components/countries-menu.js`)** — Selector geográfico compartido entre Panamá, EE. UU. y Venezuela.
- **Hero parallax (`css/hero-parallax.css` + `js/parallax.js`)** — Hero con fondo fijo, replicado en las tres sucursales con sub‑sitio.
- **Estilos `neon-price-sticker.css`** — Sticker neón para precios/ofertas en sucursales.

---

## 🛠️ Tecnologías

| Capa | Tecnología |
|------|-----------|
| Estructura | HTML5, PHP 8.x |
| Estilos | CSS3 (Grid, Flexbox, animaciones) |
| Interactividad | JavaScript ES6+ |
| Mapas | Leaflet.js |
| Tipografías | Google Fonts — Poppins (portal), Montserrat + Orbitron (sucursales) |
| Chat / AI | N8N (webhooks + IA) |
| Datos | JSON + SQLite (Panamá) |
| PWA | `manifest.json` por sucursal |
| Servidor | Apache (XAMPP local / GoDaddy en producción) |

---

## 🚀 Instalación local

```bash
# 1. Clonar dentro del htdocs de XAMPP
git clone <repo-url> C:/xampp/htdocs/prooq

# 2. Arrancar Apache desde el panel de XAMPP

# 3. Acceder a
http://localhost/prooq/
```

**Notas**
- Las sucursales de **España** y el `index.html` raíz no requieren PHP.
- **Panamá**, **EE. UU.** y **Venezuela** requieren PHP por sus páginas `servicios`, `gallery` y `downloads`.
- La base `panama/data/prooq.db` se usa vía SQLite (no requiere MySQL).
- Para que el chat funcione en local hay que ajustar el `webhookUrl` de N8N dentro de `js/chat-fab.js` o el snippet `chat-widget-standalone.html`.

---

## 🎨 SEO y accesibilidad

- Meta tags Open Graph + descripción y keywords en cada `index`.
- Roles ARIA (`role="banner"`, `role="main"`, `aria-label`) en el portal raíz.
- `alt` descriptivo en imágenes y `tabindex` en tarjetas interactivas.
- Soporte `playsinline` y `aria-hidden` en el video de fondo.

---

## 📞 Contacto

- 🌐 [prooq.com](https://prooq.com)
- 📧 info@prooq.com
- 📱 +507 6208‑2617
- 📍 Ciudad de Panamá, Panamá

**Redes sociales / canales** (9 oficiales — fuente: [social.html](social.html) + decisión V2):

| Red | Handle | URL |
|-----|--------|-----|
| YouTube | @prooqsa | <https://www.youtube.com/@prooqsa> |
| Instagram | @prooq | <https://www.instagram.com/prooq/> |
| Facebook | /prooq | <https://www.facebook.com/prooq/> |
| X (Twitter) | @prooqllc | <https://x.com/prooqllc> |
| LinkedIn | company/75542387 | <https://www.linkedin.com/company/75542387/> |
| TikTok | @prooqsa | <https://www.tiktok.com/@prooqsa> |
| GitHub | /prooq | <https://github.com/prooq> |
| Google Business | share | <https://share.google/FAt5Z7HtEK2NiKFsK> |
| WhatsApp | +507 6208‑2617 | <https://wa.me/50762082617> |

> ⚠️ **Inconsistencia actual**: las sucursales (Panamá, EE.UU., Venezuela) muestran solo **7** redes — les falta el enlace a **Google Business**. España no tiene sección de redes sociales (solo WhatsApp como contacto). A unificar en prooqV2.

---

© 2025 **PROOQ S.A.** — Todos los derechos reservados.
