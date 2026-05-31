-- Migration 011: blog gestionado desde el admin (CMS en BD).
--
-- Hasta ahora el blog vivia como archivos .md estaticos dentro de cada app
-- (apps/{pty,usa,esp,ven}/src/content/blog). Se migra a la BD para poder crear,
-- editar y borrar articulos desde el panel sin tocar el repo. Cada app lee sus
-- posts (por country) desde la API en build time.
--
-- Un post pertenece a UNA sucursal (country). El par (country, slug) es unico:
-- el slug define la URL /<pais>/blog/<slug>. El cuerpo se guarda en Markdown y
-- se renderiza a HTML en el build de cada app.

CREATE TABLE IF NOT EXISTS blog_posts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  country      CHAR(2) NOT NULL,                 -- ISO-2: PA / US / ES / VE
  slug         VARCHAR(160) NOT NULL,            -- define la URL del articulo
  title        VARCHAR(255) NOT NULL,
  description  VARCHAR(500) NOT NULL,            -- meta description / resumen
  body         MEDIUMTEXT NOT NULL,             -- contenido en Markdown
  tags         JSON NULL,                        -- array de strings, p.ej. ["n8n","PYMEs"]
  hero_image   VARCHAR(255) NULL,               -- ruta/URL de imagen destacada
  author       VARCHAR(120) NOT NULL DEFAULT 'Kenny Diaz',
  pub_date     DATE NOT NULL,
  updated_date DATE NULL,
  draft        TINYINT(1) NOT NULL DEFAULT 0,    -- 1 = borrador (no se publica)
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_blog_country_slug (country, slug),
  INDEX idx_blog_pub (country, draft, pub_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
