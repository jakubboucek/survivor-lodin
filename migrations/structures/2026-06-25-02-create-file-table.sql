-- Downloadable files, managed in the admin and served inline through PHP from
-- outside the document root (web/data/files). Like `shortlink`, the slug may contain
-- slashes; the on-disk `storage_name` is a tidy nonce name, while `download_name`
-- and `mime_type` (editable) drive the Content-Disposition/Content-Type headers.
CREATE TABLE `file` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `slug`          VARCHAR(191)  NOT NULL,
    `storage_name`  VARCHAR(255)  NOT NULL,
    `download_name` VARCHAR(255)  NOT NULL,
    `mime_type`     VARCHAR(127)  NOT NULL,
    `size`          INT UNSIGNED  DEFAULT NULL,
    `title`         VARCHAR(255)  DEFAULT NULL,
    `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_file_slug` (`slug`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_520_ci;
