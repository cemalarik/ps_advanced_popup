CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup` (
    `id_popup` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `popup_type` ENUM('image', 'html', 'newsletter') NOT NULL DEFAULT 'html',
    `date_start` DATETIME DEFAULT NULL,
    `date_end` DATETIME DEFAULT NULL,
    `trigger_type` ENUM('load', 'exit', 'scroll', 'inactivity') NOT NULL DEFAULT 'load',
    `trigger_value` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `frequency_days` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `width` INT(11) UNSIGNED NOT NULL DEFAULT 600,
    `bg_color` VARCHAR(7) NOT NULL DEFAULT '#ffffff',
    `bg_image` VARCHAR(255) DEFAULT NULL,
    `border_radius` INT(11) UNSIGNED NOT NULL DEFAULT 8,
    `overlay_opacity` FLOAT NOT NULL DEFAULT 0.5,
    `animation` VARCHAR(50) NOT NULL DEFAULT 'fadeIn',
    `close_button_style` VARCHAR(50) NOT NULL DEFAULT 'default',
    `hide_on_mobile` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `priority` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_popup`),
    KEY `active` (`active`),
    KEY `priority` (`priority`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_lang` (
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `content` TEXT,
    `cta_text` VARCHAR(100) DEFAULT NULL,
    `cta_url` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id_popup`, `id_lang`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_targeting` (
    `id_targeting` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `target_type` ENUM('page', 'category', 'product', 'customer_group', 'device') NOT NULL,
    `target_ids` TEXT NOT NULL,
    PRIMARY KEY (`id_targeting`),
    KEY `id_popup` (`id_popup`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_stats` (
    `id_stat` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `stat_type` ENUM('impression', 'conversion') NOT NULL,
    `stat_date` DATE NOT NULL,
    `count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_stat`),
    UNIQUE KEY `popup_stat_date` (`id_popup`, `stat_type`, `stat_date`),
    KEY `id_popup` (`id_popup`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;
