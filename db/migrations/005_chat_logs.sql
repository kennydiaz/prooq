CREATE TABLE IF NOT EXISTS chat_logs (
  id              BIGINT AUTO_INCREMENT PRIMARY KEY,
  conversation_id VARCHAR(64) NOT NULL,
  role            ENUM('user','assistant') NOT NULL,
  message         TEXT NOT NULL,
  source_site     CHAR(2),
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conversation (conversation_id),
  INDEX idx_source_created (source_site, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
