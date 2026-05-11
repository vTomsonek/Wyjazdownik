-- 005_participant_sort_order.sql
-- Dodaje pole `sort_order` zeby admin mogl ustalic kolejnosc uczestnikow
-- w panelu i na stronie podsumowania (sekcja hero z avatarami).
-- Idempotentne - mozna uruchomic wielokrotnie.

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'sort_order'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE participants ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER hidden, ADD INDEX idx_participants_sort (trip_id, sort_order)',
    'SELECT "Column sort_order already exists - skipping"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
