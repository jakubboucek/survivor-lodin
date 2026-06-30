-- IVR endpoints for the ODORIK exchange remote control (https://www.odorik.cz/w/ivr:vzdalene_rizeni_pres_web).
-- Each endpoint is a dynamic, publicly callable URL (/ivr/<code>): the exchange passes the
-- caller's DTMF input as a GET param, we compare it to `expected_dtmf` and return one of two
-- plain-text command bodies (`response_correct` / `response_incorrect`).
CREATE TABLE `ivr_endpoint` (
    `id`                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `code`               VARCHAR(191)  NOT NULL,
    `label`              VARCHAR(255)  NOT NULL,
    `expected_dtmf`      VARCHAR(255)  NOT NULL DEFAULT '',
    `response_correct`   MEDIUMTEXT    NOT NULL,
    `response_incorrect` MEDIUMTEXT    NOT NULL,
    `is_active`          TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ivr_endpoint_code` (`code`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;

-- Append-only call log. Intentionally MyISAM (per spec): fast inserts, no transactional needs,
-- not referenced by FKs. `endpoint_id` is NULL for hits on an unknown/inactive code. All incoming
-- GET params are stored verbatim as a JSON object in `params`.
CREATE TABLE `ivr_log` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `endpoint_id` INT UNSIGNED  DEFAULT NULL,
    `code`        VARCHAR(191)  DEFAULT NULL,
    `dtmf`        VARCHAR(255)  DEFAULT NULL,
    `matched`     TINYINT(1)    DEFAULT NULL,
    `params`      JSON          NOT NULL,
    `response`    MEDIUMTEXT    DEFAULT NULL,
    `ip`          VARCHAR(45)   DEFAULT NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ivr_log_endpoint` (`endpoint_id`, `id`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
