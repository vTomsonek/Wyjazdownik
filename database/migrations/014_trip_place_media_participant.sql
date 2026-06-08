-- Etap: media na miejscach moga byc dodawane przez wszystkich uczestnikow wyjazdu
-- (nie tylko autora miejsca). Zeby moc kontrolowac uprawnienia do usuwania
-- ('moge usunac wlasne wgranie + autor miejsca moge usunac wszystko z mojego miejsca')
-- dodajemy participant_id, ktory wskazuje kto wgral dane media.
--
-- Stare media nie maja tej informacji -> NULL = uznajemy ze nalezy do autora
-- miejsca (legacy fallback).

ALTER TABLE `trip_place_media`
    ADD COLUMN `participant_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `place_id`;

ALTER TABLE `trip_place_media`
    ADD KEY `idx_trip_place_media_participant` (`participant_id`);

ALTER TABLE `trip_place_media`
    ADD CONSTRAINT `fk_trip_place_media_participant`
        FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;
