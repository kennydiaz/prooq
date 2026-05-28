-- Migration 010: iconos del catalogo de descargas reconvertidos a WebP (256px)
-- para un catalogo liviano y uniforme. Reapunta icon_url de .jpg/.png a .webp;
-- los SVG (vectoriales) y los ya-webp quedan igual. Idempotente.

UPDATE downloads
SET icon_url = CONCAT(SUBSTRING_INDEX(icon_url, '.', 1), '.webp')
WHERE icon_url LIKE '/pty/images/downloads/%'
  AND icon_url NOT LIKE '%.svg'
  AND icon_url NOT LIKE '%.webp';
