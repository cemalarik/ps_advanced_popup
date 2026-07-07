CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup` (
    `id_popup` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `internal_name` VARCHAR(255) NOT NULL DEFAULT '',
    `popup_type` ENUM('newsletter', 'coupon', 'cta', 'image', 'announcement') NOT NULL DEFAULT 'cta',
    `campaign_goal` VARCHAR(50) NOT NULL DEFAULT 'announcement',
    `template_key` VARCHAR(80) NOT NULL DEFAULT 'announcement',
    `layout` VARCHAR(50) NOT NULL DEFAULT 'centered',
    `image_position` VARCHAR(20) NOT NULL DEFAULT 'top',
    `trigger_type` ENUM('load', 'exit', 'scroll', 'inactivity') NOT NULL DEFAULT 'load',
    `trigger_value` INT(11) UNSIGNED NOT NULL DEFAULT 3,
    `frequency_days` INT(11) UNSIGNED NOT NULL DEFAULT 7,
    `width` INT(11) UNSIGNED NOT NULL DEFAULT 560,
    `bg_color` VARCHAR(7) NOT NULL DEFAULT '#ffffff',
    `text_color` VARCHAR(7) NOT NULL DEFAULT '#1f2937',
    `accent_color` VARCHAR(7) NOT NULL DEFAULT '#25b9d7',
    `button_color` VARCHAR(7) NOT NULL DEFAULT '#111827',
    `button_text_color` VARCHAR(7) NOT NULL DEFAULT '#ffffff',
    `bg_image` VARCHAR(255) DEFAULT NULL,
    `border_radius` INT(11) UNSIGNED NOT NULL DEFAULT 8,
    `overlay_opacity` FLOAT NOT NULL DEFAULT 0.55,
    `animation` VARCHAR(50) NOT NULL DEFAULT 'fadeIn',
    `close_button_style` VARCHAR(50) NOT NULL DEFAULT 'circle',
    `close_on_overlay` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `mobile_behavior` VARCHAR(30) NOT NULL DEFAULT 'bottom_sheet',
    `priority` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `ab_test_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `winner_metric` VARCHAR(50) NOT NULL DEFAULT 'conversion_rate',
    `date_start` DATETIME DEFAULT NULL,
    `date_end` DATETIME DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_popup`),
    KEY `active_dates` (`active`, `date_start`, `date_end`),
    KEY `priority` (`priority`),
    KEY `template_key` (`template_key`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_lang` (
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `cta_text` VARCHAR(120) DEFAULT NULL,
    `cta_url` VARCHAR(255) DEFAULT NULL,
    `coupon_code` VARCHAR(80) DEFAULT NULL,
    `consent_text` VARCHAR(255) DEFAULT NULL,
    `success_message` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id_popup`, `id_lang`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_variant` (
    `id_variant` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `variant_key` VARCHAR(20) NOT NULL DEFAULT 'a',
    `name` VARCHAR(120) NOT NULL DEFAULT '',
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `traffic_percentage` INT(3) UNSIGNED NOT NULL DEFAULT 100,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_variant`),
    UNIQUE KEY `popup_variant_key` (`id_popup`, `variant_key`),
    KEY `id_popup` (`id_popup`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_variant_lang` (
    `id_variant` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `cta_text` VARCHAR(120) DEFAULT NULL,
    `cta_url` VARCHAR(255) DEFAULT NULL,
    `coupon_code` VARCHAR(80) DEFAULT NULL,
    PRIMARY KEY (`id_variant`, `id_lang`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_targeting` (
    `id_targeting` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `target_type` VARCHAR(50) NOT NULL,
    `operator` VARCHAR(20) NOT NULL DEFAULT 'in',
    `target_value` TEXT NOT NULL,
    PRIMARY KEY (`id_targeting`),
    KEY `id_popup` (`id_popup`),
    KEY `target_type` (`target_type`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_event` (
    `id_event` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `id_variant` INT(11) UNSIGNED DEFAULT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `event_date` DATETIME NOT NULL,
    `device` VARCHAR(20) NOT NULL DEFAULT 'desktop',
    `page_type` VARCHAR(50) NOT NULL DEFAULT 'other',
    `url_hash` CHAR(40) DEFAULT NULL,
    `session_key` CHAR(40) DEFAULT NULL,
    `extra` TEXT,
    PRIMARY KEY (`id_event`),
    KEY `popup_event_date` (`id_popup`, `event_type`, `event_date`),
    KEY `variant_event` (`id_variant`, `event_type`),
    KEY `device_page` (`device`, `page_type`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_stats_daily` (
    `id_stat` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `id_variant` INT(11) UNSIGNED DEFAULT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `stat_date` DATE NOT NULL,
    `device` VARCHAR(20) NOT NULL DEFAULT 'all',
    `page_type` VARCHAR(50) NOT NULL DEFAULT 'all',
    `count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_stat`),
    UNIQUE KEY `daily_unique` (`id_popup`, `id_variant`, `event_type`, `stat_date`, `device`, `page_type`),
    KEY `popup_date` (`id_popup`, `stat_date`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_subscriber` (
    `id_subscriber` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `id_variant` INT(11) UNSIGNED DEFAULT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `consent` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `source` VARCHAR(80) NOT NULL DEFAULT 'popup',
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_subscriber`),
    UNIQUE KEY `popup_email_shop` (`id_popup`, `email`, `id_shop`),
    KEY `id_popup` (`id_popup`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_smart_popup_coupon_event` (
    `id_coupon_event` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_popup` INT(11) UNSIGNED NOT NULL,
    `id_variant` INT(11) UNSIGNED DEFAULT NULL,
    `coupon_code` VARCHAR(80) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `event_type` VARCHAR(50) NOT NULL DEFAULT 'copy',
    `session_key` CHAR(40) DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_coupon_event`),
    KEY `popup_coupon` (`id_popup`, `coupon_code`),
    KEY `session_key` (`session_key`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;
