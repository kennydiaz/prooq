# SEO de prooq.com — guía de puesta en marcha

Estado del **SEO técnico**: ✅ ya implementado en el código (titles/descriptions por página,
canonical, hreflang regional es-PA/en-US/es-ES/es-VE + x-default, Open Graph, Twitter Cards,
JSON-LD `Organization` + `LocalBusiness`, `Article` + `BreadcrumbList` en el blog, favicons,
`sitemap-index.xml` por app y `robots.txt`).

El núcleo vive en componentes compartidos en `packages/ui`: [`Seo.astro`](packages/ui/src/Seo.astro)
(metadatos, invocado desde el `BaseLayout` de cada app) y [`BlogList.astro`](packages/ui/src/BlogList.astro)
+ [`BlogArticle.astro`](packages/ui/src/BlogArticle.astro) (blog). El blog existe en las 4 sucursales
país, cada una con su contenido localizado:
`/pty/blog` (es-PA), `/usa/blog` (en-US), `/esp/blog` (es-ES) y `/ven/blog` (es-VE).

Lo que sigue **no es código** — son acciones que haces una vez en plataformas de Google.
Hazlas en este orden.

---

## 1. Google Search Console (imprescindible, ~15 min)

Es donde Google te dice por qué palabras apareces, en qué posición y qué errores ve.

1. Entra a <https://search.google.com/search-console> con la cuenta Google del negocio.
2. **Agregar propiedad → tipo "Dominio"** → escribe `prooq.com`.
3. Google te da un **registro TXT** para el DNS. Agrégalo en Cloudflare (o donde gestiones el DNS
   de prooq.com) y espera la verificación (minutos a unas horas).
   - *Alternativa más rápida si no puedes tocar DNS:* usa el tipo "Prefijo de URL"
     (`https://prooq.com`) y verifica con la etiqueta HTML (se puede inyectar en el `<head>` vía
     `Seo.astro` si eliges esta vía).
4. Una vez verificado: menú **Sitemaps** → envía **los 5 sitemaps** (uno por app):
   - `sitemap-index.xml`
   - `pty/sitemap-index.xml`
   - `usa/sitemap-index.xml`
   - `esp/sitemap-index.xml`
   - `ven/sitemap-index.xml`
   > El `robots.txt` ([apps/portal/public/robots.txt](apps/portal/public/robots.txt)) ya los lista
   > todos, pero conviene enviarlos a mano para acelerar el descubrimiento.
5. Usa **Inspección de URLs** para pedir indexación de: el portal `/`, la home de cada país
   (`/pty/`, `/usa/`, `/esp/`, `/ven/`) y el primer artículo del blog de cada país
   (`/pty/blog/…`, `/usa/blog/…`, `/esp/blog/…`, `/ven/blog/…`).

> Tip: los datos tardan unos días en poblarse. No te asustes si al inicio sale vacío.

---

## 2. Google Business Profile / Perfil de Empresa (alto impacto local, ~20 min)

Para aparecer en Google Maps y en búsquedas tipo "automatización de procesos Panamá" o
"cámaras de seguridad para negocios Panamá".

1. Entra a <https://business.google.com> con la cuenta del negocio.
2. Crea/reclama el perfil con el **nombre real** (PROOQ S.A.), categoría "Empresa de tecnología
   de la información" o "Servicio de software".
3. Dirección: Ciudad de Panamá. Si no atiendes público en sitio, configúralo como **negocio de
   servicio** (ocultas la dirección y defines el área de cobertura).
4. Teléfono `+507 6208-2617`, sitio `https://prooq.com`, horario de atención.
5. **Verificación**: Google pide confirmar por teléfono, correo o video. Hazlo.
6. Sube logo, foto de portada y 3-5 imágenes. Completa la descripción con tus keywords
   ("automatización con n8n", "seguridad electrónica / CCTV", "desarrollo web", "agentes IA").
7. Pide **reseñas** a tus clientes actuales. Pesan mucho para el ranking local.

> El `LocalBusiness` que ya emite `Seo.astro` en la home de Panamá y este perfil se refuerzan
> mutuamente. La URL del perfil ya está en `SOCIAL_LINKS` (icono `google`).

---

## 3. Bing Webmaster Tools (opcional, 5 min)

Importa directo desde Search Console: <https://www.bing.com/webmasters>. Gratis, suma algo de
tráfico (y alimenta a ChatGPT/Copilot, que usan el índice de Bing).

---

## 4. Mantenimiento de contenido (lo que mueve la aguja a mediano plazo)

- **Publica en el blog con regularidad.** Cada artículo nuevo = otra puerta de entrada desde
  Google. Crea un `.md` en `apps/pty/src/content/blog/` (mira
  [`automatizacion-n8n-pymes-panama.md`](apps/pty/src/content/blog/automatizacion-n8n-pymes-panama.md)
  como plantilla).
- Apunta a búsquedas reales de tus clientes panameños:
  - "automatización con n8n para PYMEs" ✅ (ya publicado)
  - "cómo elegir cámaras de seguridad / CCTV para un negocio"
  - "qué es un agente de IA y cómo usarlo en mi empresa"
  - "cuánto cuesta un sitio web profesional en Panamá"
  - "control de acceso para oficinas / locales comerciales"
- Enlaza entre artículos y hacia las páginas de servicio (`/pty/servicios`) — el enlazado interno
  ayuda a Google a entender tu sitio.
- Revisa Search Console cada 2-3 semanas: si una página aparece en posición 8-15 para una
  palabra, mejórala (más contenido, mejor título) para subir a la primera página.

---

## Cómo agregar un nuevo artículo al blog

1. Crea `apps/<pais>/src/content/blog/mi-slug.md` (pty/usa/esp/ven). El nombre del archivo
   define la URL: `prooq.com/<pais>/blog/mi-slug`. Cada país tiene su propio blog localizado;
   publica el artículo donde tenga sentido (o adáptalo en cada uno).
2. Frontmatter mínimo:
   ```yaml
   ---
   title: "Título con la keyword"
   description: "Resumen de 1-2 frases (sale en Google y al compartir)."
   pubDate: 2026-06-01
   tags: ["seguridad electrónica", "Panamá"]
   heroImage: "/images/..."   # opcional, ruta dentro de apps/pty/public
   draft: false               # ponlo en true mientras lo escribes
   ---
   ```
3. Escribe el cuerpo en Markdown. Se renderiza en `/<pais>/blog/<slug>` con sus JSON-LD `Article` +
   `BreadcrumbList` y aparece en el listado `/<pais>/blog` automáticamente.
4. `git push` → se despliega solo. El `sitemap-index.xml` de ese país se regenera con la nueva URL.

---

## Notas técnicas

- **hreflang**: cada home de país se declara como variante regional (es-PA, en-US, es-ES, es-VE)
  con `x-default` al portal. Si en el futuro las sucursales dejan de ser equivalentes, revisar el
  mapa `ALTERNATES` en `Seo.astro`.
- **OG image**: hoy se reusa la foto del país (`/images/<pais>.jpg`). Una imagen OG diseñada por
  página es una mejora futura — se pasa con la prop `image` del `BaseLayout`/`Seo`.
- **Favicons**: el set vive en [apps/portal/public/favicons/](apps/portal/public/favicons/) y se
  referencia con ruta absoluta `/favicons/…` (válida para todas las apps en producción, ya que
  comparten el dominio `prooq.com`). Se regeneran desde `favicon.svg` con `sharp`.
- **Analítica**: prooq.com usa su propio `VisitTracker` (vía la API) en lugar de Google Analytics.
