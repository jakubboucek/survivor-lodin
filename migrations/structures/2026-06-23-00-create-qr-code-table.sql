-- QR code → redirect target mapping, managed in the admin and read by the redirector.
CREATE TABLE `qr_code` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`       VARCHAR(64)   NOT NULL,
    `target_url` VARCHAR(2048) NOT NULL,
    `label`      VARCHAR(255)  DEFAULT NULL,
    `is_active`  TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_qr_code_code` (`code`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
