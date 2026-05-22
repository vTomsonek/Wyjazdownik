-- 011_trip_place_votes_decimal.sql
-- Zmienia typ score z TINYINT (1-5) na DECIMAL(2,1) zeby wspierac polowki:
-- 0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0
-- Istniejace wartosci (1-5) zachowuja sie jako 1.0, 2.0 itd.
-- Idempotentne - sprawdza aktualny typ.

SET @current_type = (
    SELECT DATA_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trip_place_votes'
      AND COLUMN_NAME = 'score'
);

SET @sql = IF(@current_type = 'tinyint',
    'ALTER TABLE trip_place_votes MODIFY COLUMN score DECIMAL(2,1) NOT NULL',
    'SELECT "Kolumna score juz ma typ DECIMAL - skipping"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
