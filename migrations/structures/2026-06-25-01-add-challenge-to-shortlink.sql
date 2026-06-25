-- Optional multi-line "challenge" text shown above the password form on the unlock
-- screen (rendered with nl2br / Latte `breakLines`). NULL = no extra text.
ALTER TABLE `shortlink`
    ADD `challenge` TEXT DEFAULT NULL AFTER `password`;
