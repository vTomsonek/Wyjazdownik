-- ============================================================================
-- Wyjazdownik.pl - database schema
-- MySQL 5.7+ / MariaDB 10.3+, InnoDB, utf8mb4_unicode_ci
-- ============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `trip_responses_audit`;
DROP TABLE IF EXISTS `trip_place_votes`;
DROP TABLE IF EXISTS `trip_place_media`;
DROP TABLE IF EXISTS `trip_places`;
DROP TABLE IF EXISTS `participant_map_pins`;
DROP TABLE IF EXISTS `participant_responses`;
DROP TABLE IF EXISTS `participant_preferred_weeks`;
DROP TABLE IF EXISTS `participant_unavailable_dates`;
DROP TABLE IF EXISTS `participants`;
DROP TABLE IF EXISTS `trips`;
DROP TABLE IF EXISTS `admin_sessions`;
DROP TABLE IF EXISTS `admin_login_tokens`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `rate_limits`;

-- ----------------------------------------------------------------------------
-- admins
-- ----------------------------------------------------------------------------
CREATE TABLE `admins` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(190) NOT NULL,
    `name`       VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- admin_login_tokens (magic linki)
-- ----------------------------------------------------------------------------
CREATE TABLE `admin_login_tokens` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id`   BIGINT UNSIGNED NOT NULL,
    `token`      CHAR(64) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `used_at`    TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_login_tokens_token` (`token`),
    KEY `idx_login_tokens_admin` (`admin_id`),
    KEY `idx_login_tokens_expires` (`expires_at`),
    CONSTRAINT `fk_login_tokens_admin`
        FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- admin_sessions
-- ----------------------------------------------------------------------------
CREATE TABLE `admin_sessions` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id`      BIGINT UNSIGNED NOT NULL,
    `session_token` CHAR(64) NOT NULL,
    `expires_at`    TIMESTAMP NOT NULL,
    `ip_address`    VARCHAR(45) NULL DEFAULT NULL,
    `user_agent`    VARCHAR(255) NULL DEFAULT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_sessions_token` (`session_token`),
    KEY `idx_sessions_admin` (`admin_id`),
    KEY `idx_sessions_expires` (`expires_at`),
    CONSTRAINT `fk_sessions_admin`
        FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- trips
-- ----------------------------------------------------------------------------
CREATE TABLE `trips` (
    `id`                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id`                  BIGINT UNSIGNED NOT NULL,
    `name`                      VARCHAR(150) NOT NULL,
    `slug`                      VARCHAR(160) NOT NULL,
    `description`               TEXT NULL,
    `start_name`                VARCHAR(200) NULL DEFAULT NULL,
    `start_lat`                 DECIMAL(10, 7) NULL DEFAULT NULL,
    `start_lng`                 DECIMAL(10, 7) NULL DEFAULT NULL,
    `banner_image`              VARCHAR(255) NULL DEFAULT NULL,
    `date_from`                 DATE NOT NULL,
    `date_to`                   DATE NOT NULL,
    `calendar_mode`             ENUM('block_unavailable','select_preferred_weeks')
                                NOT NULL DEFAULT 'block_unavailable',
    `show_individual_responses` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active`                 TINYINT(1) NOT NULL DEFAULT 1,
    `summary_public_token`      CHAR(64) NOT NULL,
    `created_at`                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_trips_slug` (`slug`),
    UNIQUE KEY `uniq_trips_summary_token` (`summary_public_token`),
    KEY `idx_trips_admin` (`admin_id`),
    KEY `idx_trips_active` (`is_active`),
    CONSTRAINT `fk_trips_admin`
        FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- participants
-- ----------------------------------------------------------------------------
CREATE TABLE `participants` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `trip_id`          BIGINT UNSIGNED NOT NULL,
    `nickname`         VARCHAR(60) NOT NULL,
    `avatar_path`      VARCHAR(255) NULL DEFAULT NULL,
    `color`            VARCHAR(20) NULL DEFAULT NULL,
    `hidden`           TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order`       INT NOT NULL DEFAULT 0,
    `access_token`     CHAR(64) NOT NULL,
    `completed_at`     TIMESTAMP NULL DEFAULT NULL,
    `last_activity_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_participants_token` (`access_token`),
    KEY `idx_participants_trip` (`trip_id`),
    KEY `idx_participants_completed` (`completed_at`),
    KEY `idx_participants_sort` (`trip_id`, `sort_order`),
    CONSTRAINT `fk_participants_trip`
        FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- participant_unavailable_dates
-- ----------------------------------------------------------------------------
CREATE TABLE `participant_unavailable_dates` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `participant_id`    BIGINT UNSIGNED NOT NULL,
    `unavailable_date`  DATE NOT NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_unavail_participant_date` (`participant_id`, `unavailable_date`),
    KEY `idx_unavail_date` (`unavailable_date`),
    CONSTRAINT `fk_unavail_participant`
        FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- participant_preferred_weeks
-- ----------------------------------------------------------------------------
CREATE TABLE `participant_preferred_weeks` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `participant_id`  BIGINT UNSIGNED NOT NULL,
    `week_start_date` DATE NOT NULL,
    `preference`      ENUM('yes','maybe','no') NOT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_pref_participant_week` (`participant_id`, `week_start_date`),
    KEY `idx_pref_week` (`week_start_date`),
    CONSTRAINT `fk_pref_participant`
        FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- participant_responses (EAV)
-- ----------------------------------------------------------------------------
CREATE TABLE `participant_responses` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `participant_id` BIGINT UNSIGNED NOT NULL,
    `question_key`   VARCHAR(64) NOT NULL,
    `value_text`     TEXT NULL DEFAULT NULL,
    `value_json`     JSON NULL DEFAULT NULL,
    `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                     ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_responses_participant_key` (`participant_id`, `question_key`),
    KEY `idx_responses_key` (`question_key`),
    CONSTRAINT `fk_responses_participant`
        FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- participant_map_pins
-- ----------------------------------------------------------------------------
CREATE TABLE `participant_map_pins` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `participant_id` BIGINT UNSIGNED NOT NULL,
    `pin_type`       ENUM('marker','polyline','polygon') NOT NULL,
    `label`          VARCHAR(150) NULL DEFAULT NULL,
    `description`    TEXT NULL DEFAULT NULL,
    `geojson`        JSON NOT NULL,
    `color`          VARCHAR(20) NULL DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pins_participant` (`participant_id`),
    KEY `idx_pins_type` (`pin_type`),
    CONSTRAINT `fk_pins_participant`
        FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- trip_places - nowa kolaboratywna mapa atrakcji (zastapuje participant_map_pins)
-- Kazdy uczestnik moze dodac konkretne miejsce (POI) z lokalizacja i opisem.
-- ----------------------------------------------------------------------------
CREATE TABLE `trip_places` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `trip_id`        BIGINT UNSIGNED NOT NULL,
    `participant_id` BIGINT UNSIGNED NOT NULL,
    `name`           VARCHAR(200) NOT NULL,
    `description`    TEXT NULL DEFAULT NULL,
    `visit_minutes`  INT UNSIGNED NOT NULL DEFAULT 60,
    `lat`            DECIMAL(10, 7) NOT NULL,
    `lng`            DECIMAL(10, 7) NOT NULL,
    `address`         VARCHAR(500) NULL DEFAULT NULL,
    `country_code`    VARCHAR(2) NULL DEFAULT NULL,
    `osm_place_id`    VARCHAR(50) NULL DEFAULT NULL,
    `google_place_id` VARCHAR(100) NULL DEFAULT NULL,
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

-- ----------------------------------------------------------------------------
-- trip_place_votes - oceny gwiazdkowe 1-5 (1 ocena per uczestnik per miejsce)
-- ----------------------------------------------------------------------------
CREATE TABLE `trip_place_votes` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `place_id`       BIGINT UNSIGNED NOT NULL,
    `participant_id` BIGINT UNSIGNED NOT NULL,
    `score`          DECIMAL(2,1) NOT NULL,  -- 0.5, 1.0, 1.5, ..., 5.0
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

-- ----------------------------------------------------------------------------
-- trip_place_media - zdjecia, wideo, linki dla atrakcji
-- ----------------------------------------------------------------------------
CREATE TABLE `trip_place_media` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `place_id`   BIGINT UNSIGNED NOT NULL,
    `type`       ENUM('image', 'video', 'link') NOT NULL,
    `file_path`  VARCHAR(500) NULL DEFAULT NULL,
    `url`        VARCHAR(500) NULL DEFAULT NULL,
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

-- ----------------------------------------------------------------------------
-- trip_responses_audit
-- ----------------------------------------------------------------------------
CREATE TABLE `trip_responses_audit` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `trip_id`              BIGINT UNSIGNED NOT NULL,
    `participant_id`       BIGINT UNSIGNED NOT NULL,
    `changed_by_admin_id`  BIGINT UNSIGNED NOT NULL,
    `field_changed`        VARCHAR(64) NOT NULL,
    `old_value`            TEXT NULL DEFAULT NULL,
    `new_value`            TEXT NULL DEFAULT NULL,
    `changed_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_trip` (`trip_id`),
    KEY `idx_audit_participant` (`participant_id`),
    KEY `idx_audit_admin` (`changed_by_admin_id`),
    KEY `idx_audit_changed_at` (`changed_at`),
    CONSTRAINT `fk_audit_trip`
        FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_audit_participant`
        FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_audit_admin`
        FOREIGN KEY (`changed_by_admin_id`) REFERENCES `admins` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- rate_limits  (login throttle, submit throttle, etc.)
-- ----------------------------------------------------------------------------
CREATE TABLE `rate_limits` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bucket_key`    VARCHAR(150) NOT NULL,
    `attempted_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rate_bucket_time` (`bucket_key`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
