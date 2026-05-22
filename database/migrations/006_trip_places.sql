-- 006_trip_places.sql
-- Nowa kolaboratywna mapa atrakcji - zastapuje stara `participant_map_pins`.
-- Kazdy uczestnik moze dodac konkretne miejsce (POI) z lokalizacja i opisem.
-- Inne tabele (trip_place_media, trip_place_votes) zostana dodane w kolejnych etapach.
-- Idempotentne - mozna uruchomic wielokrotnie.

CREATE TABLE IF NOT EXISTS `trip_places` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `trip_id`        BIGINT UNSIGNED NOT NULL,
    `participant_id` BIGINT UNSIGNED NOT NULL,  -- kto dodal
    `name`           VARCHAR(200) NOT NULL,
    `description`    TEXT NULL DEFAULT NULL,
    `lat`            DECIMAL(10, 7) NOT NULL,
    `lng`            DECIMAL(10, 7) NOT NULL,
    `address`        VARCHAR(500) NULL DEFAULT NULL,
    `country_code`   VARCHAR(2) NULL DEFAULT NULL,    -- ISO 3166-1 dla klastrowania
    `osm_place_id`   VARCHAR(50) NULL DEFAULT NULL,   -- ID z OpenStreetMap (deduplication)
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_trip_places_trip`        (`trip_id`),
    KEY `idx_trip_places_participant` (`participant_id`),
    KEY `idx_trip_places_country`     (`trip_id`, `country_code`),
    CONSTRAINT `fk_trip_places_trip`
        FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_trip_places_participant`
        FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
