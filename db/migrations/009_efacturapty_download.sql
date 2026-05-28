-- Migration 009: alta del PAC eFacturaPTY como portal de folios en downloads.
--
-- Cuarto proveedor de facturacion electronica (PAC) integrado, junto a
-- DigiFact, The Factory HKA y Factura Facil. Se modela igual que los otros
-- portales: external_url + icon_url, sin filename. Idempotente via slug unico.

INSERT INTO downloads (title, description, filename, file_size_bytes, mime_type, external_url, icon_url, country, slug, is_public)
VALUES (
  'Folios eFacturaPTY',
  'Portal de gestion de folios eFacturaPTY.',
  NULL, NULL, NULL,
  'https://www.efacturapty.com/',
  '/pty/images/downloads/efacturapty.svg',
  'PA',
  'portal-efacturapty',
  1
)
ON DUPLICATE KEY UPDATE
  title        = VALUES(title),
  description  = VALUES(description),
  external_url = VALUES(external_url),
  icon_url     = VALUES(icon_url),
  is_public    = VALUES(is_public);
