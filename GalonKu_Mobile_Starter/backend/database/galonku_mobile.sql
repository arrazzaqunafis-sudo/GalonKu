USE db_galonku;

CREATE TABLE IF NOT EXISTS api_tokens (
    token_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_tokens_user (user_id),
    INDEX idx_api_tokens_expiry (expires_at)
) ENGINE=InnoDB;
