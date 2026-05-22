-- 012_trip_places_visit_minutes.sql
-- Dodaje pole `visit_minutes` per miejsce - ile czasu zajmie zwiedzenie.
-- Algorytm tras uwzglednia to przy szacowaniu liczby dni:
-- punkt widokowy (30 min) vs Plitvice (cały dzień, 480 min).
-- Default 60 (1h) - sensowne dla wiekszosci miejsc.

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trip_places'
      AND COLUMN_NAME = 'visit_minutes'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE trip_places ADD COLUMN visit_minutes INT UNSIGNED NOT NULL DEFAULT 60 AFTER description',
    'SELECT "Kolumna visit_minutes juz istnieje - skipping"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
