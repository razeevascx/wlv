CREATE TABLE IF NOT EXISTS rawg_cache (
    cache_key CHAR(64) NOT NULL PRIMARY KEY,
    payload JSON NOT NULL,
    expires_at DATETIME NOT NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
