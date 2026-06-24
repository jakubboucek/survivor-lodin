-- Teams (two fixed factions), their members, and per-game results.
--
-- Results use a denormalised "wide" layout: one `game` row holds both teams'
-- points, since the two teams are fixed and scores are always entered together.
-- The internal team codes (bear/hornet) are immutable and double as the result
-- column names; only the display name in `team` is editable.

CREATE TABLE `team` (
    `code`       VARCHAR(16)      NOT NULL,            -- 'bear' | 'hornet' (fixed internal key)
    `name`       VARCHAR(64)      NOT NULL,            -- editable display name (Hrdinové / Padouši)
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`code`),
    CONSTRAINT `chk_team_code` CHECK (`code` IN ('bear', 'hornet'))
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;

INSERT INTO `team` (`code`, `name`, `sort_order`) VALUES
    ('bear', 'Hrdinové', 0),
    ('hornet', 'Padouši', 1);

CREATE TABLE `team_member` (
    `id`         INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `team_code`  VARCHAR(16)       NOT NULL,
    `name`       VARCHAR(128)      NOT NULL,
    `photo`      VARCHAR(255)      DEFAULT NULL,        -- round avatar filename under /img/, NULL = name only
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_team_member_order` (`team_code`, `sort_order`),
    CONSTRAINT `fk_team_member_team` FOREIGN KEY (`team_code`) REFERENCES `team` (`code`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;

CREATE TABLE `game` (
    `id`            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(128)      NOT NULL,         -- discipline name
    `bear_points`   TINYINT UNSIGNED  DEFAULT NULL,     -- NULL = not yet played
    `hornet_points` TINYINT UNSIGNED  DEFAULT NULL,
    `played_at`     DATETIME          DEFAULT NULL,     -- when the game was played; results ordering key
    `published_at`  DATETIME          DEFAULT NULL,     -- result release time; NULL = published immediately
    `created_at`    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_game_played` (`played_at`),
    -- scores are entered for both teams at once: both NULL (unplayed) or both set
    CONSTRAINT `chk_game_scored_both` CHECK ((`bear_points` IS NULL) = (`hornet_points` IS NULL))
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
