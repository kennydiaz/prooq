CREATE TABLE IF NOT EXISTS ebop_requests (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  company_name    VARCHAR(255) NOT NULL,
  contact_name    VARCHAR(255) NOT NULL,
  email           VARCHAR(255) NOT NULL,
  phone           VARCHAR(50),
  message         TEXT,
  status          ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
  ip_address      VARCHAR(45),
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status_created (status, created_at),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
