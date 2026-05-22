-- 010_trip_start_location.sql
-- Punkt startowy wyjazdu (np. miasto z ktorego ekipa wyjezdza) - uwzgledniany
-- w algorytmie propozycji tras. Opcjonalny.
-- Idempotentne.

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trips'
      AND COLUMN_NAME = 'start_lat'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE trips
        ADD COLUMN start_name VARCHAR(200) NULL DEFAULT NULL AFTER description,
        ADD COLUMN start_lat DECIMAL(10, 7) NULL DEFAULT NULL AFTER start_name,
        ADD COLUMN start_lng DECIMAL(10, 7) NULL DEFAULT NULL AFTER start_lat',
    'SELECT "Kolumny start_* juz istnieja - skipping"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
