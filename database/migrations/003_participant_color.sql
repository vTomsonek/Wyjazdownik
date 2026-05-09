-- Migracja 003: dodaje kolumne `color` do `participants` zeby admin mogl
-- recznie zmienic kolor uczestnika (nadpisuje deterministyczny z md5(token)).
--
-- Uruchomienie:
--   USE wyjazdownik;
--   SOURCE database/migrations/003_participant_color.sql;
--
-- Idempotentne - sprawdza czy kolumna juz istnieje przed dodaniem.

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'color'
);

SET @sql := IF(@col_exists = 0,
    'ALTER TABLE participants ADD COLUMN color VARCHAR(20) NULL DEFAULT NULL AFTER avatar_path',
    'SELECT "Kolumna color juz istnieje, pomijam." AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
