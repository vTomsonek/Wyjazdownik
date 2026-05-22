-- 007_trip_places_google.sql
-- Dodaje pole google_place_id (Google Places API place_id) - reference do bogatszych danych
-- (zdjecia, recenzje, godziny otwarcia) wczytywanych on-demand w przegladarce.
-- Idempotentne.

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trip_places'
      AND COLUMN_NAME = 'google_place_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE trip_places ADD COLUMN google_place_id VARCHAR(100) NULL DEFAULT NULL AFTER osm_place_id, ADD INDEX idx_trip_places_google (google_place_id)',
    'SELECT "Column google_place_id already exists - skipping"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
