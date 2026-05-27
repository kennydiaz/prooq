-- Migration 007: tracking de visitas (analytics) para el Dashboard del admin.
--
-- Registra cada visita a las paginas publicas con IP (server-side) y pais de
-- origen (resuelto por geolocalizacion de IP). Alimenta los KPIs y graficas
-- del modulo Dashboard. Primer modulo de analytics; se ampliara por partes.

CREATE TABLE IF NOT EXISTS page_visits (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  path        VARCHAR(255) NOT NULL,
  site        VARCHAR(16)  NULL,            -- pty / usa / esp / ven / portal
  country     CHAR(2)      NULL,            -- ISO-2; NULL si desconocido o IP local
  ip          VARCHAR(45)  NULL,            -- IPv4 / IPv6
  user_agent  VARCHAR(500) NULL,
  referrer    VARCHAR(500) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pv_created (created_at),
  INDEX idx_pv_country (country),
  INDEX idx_pv_site (site),
  INDEX idx_pv_ip (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
