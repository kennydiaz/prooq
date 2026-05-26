-- Migration 006: CMS galería + auth admin
--
-- Modulo CMS para que el equipo PROOQ suba fotos via UI (próxima fase) o
-- via curl. Auth con sesión PHP, password hash bcrypt. Storage en
-- api/public/uploads/gallery/{uuid}.{ext} servido por api.prooq.com.

CREATE TABLE IF NOT EXISTS admin_users (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(64) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('admin','editor') NOT NULL DEFAULT 'admin',
  last_login_at   TIMESTAMP NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery_items (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(255),
  alt             VARCHAR(500),
  caption         TEXT,
  filename        VARCHAR(255) NOT NULL,
  mime_type       VARCHAR(100),
  file_size_bytes BIGINT,
  width_px        INT,
  height_px       INT,
  country         CHAR(2),
  display_order   INT NOT NULL DEFAULT 0,
  is_public       TINYINT(1) NOT NULL DEFAULT 1,
  uploaded_by     INT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_country_public (country, is_public),
  INDEX idx_display_order (display_order),
  CONSTRAINT fk_gallery_user FOREIGN KEY (uploaded_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
