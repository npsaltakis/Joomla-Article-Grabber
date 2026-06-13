CREATE TABLE IF NOT EXISTS `#__cgrabber_sources` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `url` VARCHAR(255) NOT NULL DEFAULT '',
    `token_enc` TEXT NOT NULL,
    `default_catid` INT UNSIGNED NOT NULL DEFAULT 0,
    `published` TINYINT NOT NULL DEFAULT 1,
    `ordering` INT NOT NULL DEFAULT 0,
    `created` DATETIME NULL DEFAULT NULL,
    `modified` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_published` (`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
