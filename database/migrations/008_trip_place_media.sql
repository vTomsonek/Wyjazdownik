-- 008_trip_place_media.sql
-- Etap 2 funkcji "atrakcje": uploady zdjec/wideo + linki zewnetrzne per miejsce.
-- Idempotentne.

CREATE TABLE IF NOT EXISTS `trip_place_media` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `place_id`   BIGINT UNSIGNED NOT NULL,
    `type`       ENUM('image', 'video', 'link') NOT NULL,
    `file_path`  VARCHAR(500) NULL DEFAULT NULL,  -- dla image/video (relatywne, public/)
    `url`        VARCHAR(500) NULL DEFAULT NULL,  -- dla link (zewnetrzne URL, ew. embed YT)
    `caption`    VARCHAR(300) NULL DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_media_place` (`place_id`, `sort_order`),
    KEY `idx_media_type`  (`place_id`, `type`),
    CONSTRAINT `fk_media_place`
        FOREIGN KEY (`place_id`) REFERENCES `trip_places` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
