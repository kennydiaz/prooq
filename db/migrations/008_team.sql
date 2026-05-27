-- Migration 008: modulo de equipo (team members) gestionado desde el admin.
--
-- Cada sucursal muestra un equipo distinto, pero un mismo miembro puede
-- aparecer en varias sucursales -> relacion muchos-a-muchos. El miembro existe
-- una sola vez (team_members) y se asigna a 1..N paises (team_member_countries)
-- con orden por pais.

CREATE TABLE IF NOT EXISTS team_members (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  role        VARCHAR(120) NULL,
  photo       VARCHAR(255) NULL,            -- filename en uploads/team/
  bio         TEXT NULL,
  email       VARCHAR(160) NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_member_countries (
  member_id     INT NOT NULL,
  country       CHAR(2) NOT NULL,           -- ISO-2: PA / US / ES / VE
  display_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (member_id, country),
  CONSTRAINT fk_tmc_member FOREIGN KEY (member_id)
    REFERENCES team_members(id) ON DELETE CASCADE,
  INDEX idx_tmc_country (country, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
