-- Google Places API (New) zwraca dluzsze Place IDs niz stary endpoint
-- (czesto > 100 znakow). Rozszerzamy kolumne aby dlugie ID nie crashowaly
-- backendu z bledem "Data too long for column".

ALTER TABLE `trip_places`
    MODIFY COLUMN `google_place_id` VARCHAR(255) NULL DEFAULT NULL;

-- Niektore adresy z Google Places (zwlaszcza z dlugimi nazwami ulic
-- i administracyjnymi) potrafia przekroczyc 500. Damy zapas.
ALTER TABLE `trip_places`
    MODIFY COLUMN `address` VARCHAR(800) NULL DEFAULT NULL;
