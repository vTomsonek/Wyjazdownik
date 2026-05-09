-- ============================================================================
-- Migration: budget_range z przedzialow tekstowych na liczbe (mediana przedzialu).
-- Plus usuniecie 'any' z transport_modes (zostaje multi z konkretnych srodkow).
-- Plus room_sharing: 'tent_ok' przeniesione do accommodation pattern.
--
-- Bezpieczne do uruchomienia wielokrotnie - operacje sa idempotentne.
-- Uruchomienie: w phpMyAdmin / mysql CLI:
--   USE wyjazdownik;
--   SOURCE database/migrations/002_budget_to_numeric.sql;
-- ============================================================================

-- 1) budget_range: tekst -> liczba (mediana przedzialu)
UPDATE participant_responses SET value_text = '750'  WHERE question_key = 'budget_range' AND value_text = '0-1500';
UPDATE participant_responses SET value_text = '2250' WHERE question_key = 'budget_range' AND value_text = '1500-3000';
UPDATE participant_responses SET value_text = '4000' WHERE question_key = 'budget_range' AND value_text = '3000-5000';
UPDATE participant_responses SET value_text = '8000' WHERE question_key = 'budget_range' AND value_text = '5000+';

-- 2) transport_modes: usun 'any' z kazdej tablicy JSON (mysql 5.7+)
UPDATE participant_responses
SET    value_json = JSON_REMOVE(value_json, JSON_UNQUOTE(JSON_SEARCH(value_json, 'one', 'any')))
WHERE  question_key = 'transport_modes'
  AND  JSON_SEARCH(value_json, 'one', 'any') IS NOT NULL;

-- 3) room_sharing: jesli ktos mial 'tent_ok' -> przemapuj na 'share_with_friends'
UPDATE participant_responses SET value_text = 'share_with_friends'
WHERE question_key = 'room_sharing' AND value_text = 'tent_ok';
