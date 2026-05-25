CREATE TABLE IF NOT EXISTS downloads (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  title             VARCHAR(255) NOT NULL,
  description       TEXT,
  filename          VARCHAR(255) NOT NULL,
  file_size_bytes   BIGINT,
  mime_type         VARCHAR(100),
  country           CHAR(2),
  download_count    INT NOT NULL DEFAULT 0,
  is_public         TINYINT(1) NOT NULL DEFAULT 1,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_country_public (country, is_public),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
