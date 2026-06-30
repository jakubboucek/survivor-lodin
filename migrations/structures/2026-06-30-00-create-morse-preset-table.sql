-- Named presets for the Morse code generator (Admin\Morse). The generator is a
-- purely client-side tool; the server only persists named presets as an opaque
-- JSON blob in `data` (it never inspects the contents). The "Default" preset is
-- defined in JavaScript and is NOT stored here. Names are not unique (presets are
-- referenced by id), and ownership/locking is intentionally not handled (2 admins).
CREATE TABLE `morse_preset` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255)  NOT NULL,
    `data`       TEXT          NOT NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
