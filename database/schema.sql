CREATE TABLE IF NOT EXISTS videogames (
    game_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_name VARCHAR(150) NOT NULL,
    game_description TEXT NOT NULL,
    released_date DATE NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    rawg_id INT UNSIGNED NULL UNIQUE,
    CONSTRAINT videogames_rating_range CHECK (rating BETWEEN 0 AND 10)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
