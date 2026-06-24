-- Simple key/value store for admin-tweakable application options.
-- First option: `game_active` ('0'/'1') – when on, the homepage redirects to the
-- results board instead of showing the intro.

CREATE TABLE `setting` (
    `name`       VARCHAR(64)  NOT NULL,
    `value`      VARCHAR(255) NOT NULL,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;

INSERT INTO `setting` (`name`, `value`) VALUES ('game_active', '0');
