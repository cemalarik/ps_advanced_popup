<?php
/**
 * Upgrade script 1.x -> 2.0.0.
 *
 * Creates the v2 tables, adds the new columns to the v1 tables and migrates
 * existing popup, targeting and stats data without data loss.
 *
 * @param Ps_advanced_popup $module
 *
 * @return bool
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_0($module)
{
    $db = Db::getInstance();

    // 1. Create every v2 table that does not exist yet (install.sql uses IF NOT EXISTS).
    if (!$module->installDb()) {
        return false;
    }

    // 2. New columns on the main table.
    $popupTable = _DB_PREFIX_ . 'smart_popup';
    $newPopupColumns = [
        'internal_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'campaign_goal' => "VARCHAR(50) NOT NULL DEFAULT 'announcement'",
        'template_key' => "VARCHAR(80) NOT NULL DEFAULT 'announcement'",
        'layout' => "VARCHAR(50) NOT NULL DEFAULT 'centered'",
        'image_position' => "VARCHAR(20) NOT NULL DEFAULT 'top'",
        'text_color' => "VARCHAR(7) NOT NULL DEFAULT '#1f2937'",
        'accent_color' => "VARCHAR(7) NOT NULL DEFAULT '#25b9d7'",
        'button_color' => "VARCHAR(7) NOT NULL DEFAULT '#111827'",
        'button_text_color' => "VARCHAR(7) NOT NULL DEFAULT '#ffffff'",
        'close_on_overlay' => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 1',
        'mobile_behavior' => "VARCHAR(30) NOT NULL DEFAULT 'bottom_sheet'",
        'ab_test_enabled' => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0',
        'winner_metric' => "VARCHAR(50) NOT NULL DEFAULT 'conversion_rate'",
    ];
    foreach ($newPopupColumns as $column => $definition) {
        if (!aps_upgrade_column_exists($popupTable, $column)) {
            if (!$db->execute('ALTER TABLE `' . $popupTable . '` ADD COLUMN `' . bqSQL($column) . '` ' . $definition)) {
                return false;
            }
        }
    }

    // 3. popup_type: v1 enum ('image', 'html', 'newsletter') -> v2 enum. Map 'html' to 'announcement'.
    $db->execute('ALTER TABLE `' . $popupTable . '` MODIFY `popup_type` VARCHAR(50) NOT NULL DEFAULT \'cta\'');
    $db->execute('UPDATE `' . $popupTable . '` SET `popup_type` = \'announcement\' WHERE `popup_type` = \'html\'');
    $db->execute('UPDATE `' . $popupTable . '` SET `popup_type` = \'announcement\' WHERE `popup_type` NOT IN (\'newsletter\', \'coupon\', \'cta\', \'image\', \'announcement\')');
    if (!$db->execute('ALTER TABLE `' . $popupTable . "` MODIFY `popup_type` ENUM('newsletter', 'coupon', 'cta', 'image', 'announcement') NOT NULL DEFAULT 'cta'")) {
        return false;
    }

    // 4. hide_on_mobile -> mobile_behavior.
    if (aps_upgrade_column_exists($popupTable, 'hide_on_mobile')) {
        $db->execute('UPDATE `' . $popupTable . '` SET `mobile_behavior` = IF(`hide_on_mobile` = 1, \'hidden\', \'modal\')');
        $db->execute('ALTER TABLE `' . $popupTable . '` DROP COLUMN `hide_on_mobile`');
    }

    // 5. Fill internal_name from the default-language title when empty.
    $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
    $db->execute(
        'UPDATE `' . $popupTable . '` p
         LEFT JOIN `' . _DB_PREFIX_ . 'smart_popup_lang` pl
            ON pl.id_popup = p.id_popup AND pl.id_lang = ' . $idLangDefault . '
         SET p.internal_name = COALESCE(NULLIF(pl.title, \'\'), CONCAT(\'Popup #\', p.id_popup))
         WHERE p.internal_name = \'\''
    );

    // 6. New columns on the lang table.
    $langTable = _DB_PREFIX_ . 'smart_popup_lang';
    $newLangColumns = [
        'subtitle' => 'VARCHAR(255) DEFAULT NULL',
        'coupon_code' => 'VARCHAR(80) DEFAULT NULL',
        'consent_text' => 'VARCHAR(255) DEFAULT NULL',
        'success_message' => 'VARCHAR(255) DEFAULT NULL',
    ];
    foreach ($newLangColumns as $column => $definition) {
        if (!aps_upgrade_column_exists($langTable, $column)) {
            if (!$db->execute('ALTER TABLE `' . $langTable . '` ADD COLUMN `' . bqSQL($column) . '` ' . $definition)) {
                return false;
            }
        }
    }
    $db->execute('ALTER TABLE `' . $langTable . '` MODIFY `cta_text` VARCHAR(120) DEFAULT NULL');

    // 7. Targeting: target_ids (JSON) -> target_value + operator; 'page' -> 'page_type'.
    $targetingTable = _DB_PREFIX_ . 'smart_popup_targeting';
    if (aps_upgrade_column_exists($targetingTable, 'target_ids')) {
        $db->execute('ALTER TABLE `' . $targetingTable . '` MODIFY `target_type` VARCHAR(50) NOT NULL');
        if (!aps_upgrade_column_exists($targetingTable, 'operator')) {
            $db->execute('ALTER TABLE `' . $targetingTable . "` ADD COLUMN `operator` VARCHAR(20) NOT NULL DEFAULT 'in'");
        }
        if (!aps_upgrade_column_exists($targetingTable, 'target_value')) {
            $db->execute('ALTER TABLE `' . $targetingTable . '` ADD COLUMN `target_value` TEXT NOT NULL');
        }
        $db->execute('UPDATE `' . $targetingTable . '` SET `target_value` = `target_ids`, `operator` = \'in\'');
        $db->execute('UPDATE `' . $targetingTable . '` SET `target_type` = \'page_type\' WHERE `target_type` = \'page\'');
        // v1 category/product rules have no v2 equivalent semantics; drop them to avoid wrong targeting.
        $db->execute('DELETE FROM `' . $targetingTable . '` WHERE `target_type` IN (\'category\', \'product\')');
        $db->execute('ALTER TABLE `' . $targetingTable . '` DROP COLUMN `target_ids`');
    }

    // 8. Migrate v1 daily stats into the v2 stats table, then drop the old table.
    $oldStats = _DB_PREFIX_ . 'smart_popup_stats';
    if (aps_upgrade_table_exists($oldStats)) {
        $db->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'smart_popup_stats_daily`
                (`id_popup`, `id_variant`, `event_type`, `stat_date`, `device`, `page_type`, `count`)
             SELECT `id_popup`, 0,
                    IF(`stat_type` = \'impression\', \'impression\', \'cta_click\'),
                    `stat_date`, \'all\', \'all\', `count`
             FROM `' . $oldStats . '`
             ON DUPLICATE KEY UPDATE `count` = `count` + VALUES(`count`)'
        );
        $db->execute('DROP TABLE `' . $oldStats . '`');
    }

    // 9. Hooks and tab (idempotent).
    foreach (['displayHeader', 'displayFooter', 'actionNewsletterRegistrationAfter'] as $hookName) {
        if (!$module->isRegisteredInHook($hookName)) {
            $module->registerHook($hookName);
        }
    }

    return true;
}

function aps_upgrade_column_exists($table, $column)
{
    $rows = Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . bqSQL($table) . '` LIKE "' . pSQL($column) . '"'
    );

    return !empty($rows);
}

function aps_upgrade_table_exists($table)
{
    $rows = Db::getInstance()->executeS('SHOW TABLES LIKE "' . pSQL($table) . '"');

    return !empty($rows);
}
