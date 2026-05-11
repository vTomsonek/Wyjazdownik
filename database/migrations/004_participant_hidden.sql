-- 004_participant_hidden.sql
-- Dodaje flage `hidden` do uczestnikow. Hidden = uczestnik nie pojawi sie w podsumowaniu,
-- ale dane sa zachowane (admin moze przywrocic). Uzyteczne np. zeby zobaczyc jak
-- wyglada plan bez konkretnej osoby.
-- Idempotentne - mozna uruchomic wielokrotnie.

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'hidden'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE participants ADD COLUMN hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER color',
    'SELECT "Column hidden already exists - skipping"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
