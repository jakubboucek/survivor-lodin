-- Rename qr_code → shortlink: the table is primarily a URL shortener now, QR codes
-- are just a feature on top. Widen `code` to allow multi-segment slugs (slashes in
-- the path) and add an optional plaintext `password` gate (a game mechanic, not
-- security-grade – the admin shares the password with players).
RENAME TABLE `qr_code` TO `shortlink`;

ALTER TABLE `shortlink`
    MODIFY `code` VARCHAR(191) NOT NULL,
    ADD `password` VARCHAR(255) DEFAULT NULL AFTER `target_url`;
