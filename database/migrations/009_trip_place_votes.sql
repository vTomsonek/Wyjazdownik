-- 009_trip_place_votes.sql
-- Etap 3 funkcji "atrakcje": oceny gwiazdkowe 1-5 per uczestnik per miejsce.
-- Unique key zapewnia ze kazdy moze odddac tylko 1 ocene per miejsce.
-- Idempotentne.

CREATE TABLE IF NOT EXISTS `trip_place_votes` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `place_id`       BIGINT UNSIGNED NOT NULL,
    `participant_id` BIGINT UNSIGNED NOT NULL,
    `score`          TINYINT UNSIGNED NOT NULL,  -- 1-5
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_vote_per_user` (`place_id`, `participant_id`),
    KEY `idx_vote_place`       (`place_id`),
    KEY `idx_vote_participant` (`participant_id`),
    CONSTRAINT `fk_votes_place`
        FOREIGN KEY (`place_id`) REFERENCES `trip_places` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_votes_participant`
        FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
