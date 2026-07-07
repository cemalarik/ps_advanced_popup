<?php
/**
 * Upgrade script 2.0.x -> 2.0.2.
 *
 * Adds the dedicated subtitle color setting.
 *
 * @param Ps_advanced_popup $module
 *
 * @return bool
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_2($module)
{
    $table = _DB_PREFIX_ . 'smart_popup';
    $rows = Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . bqSQL($table) . '` LIKE "subtitle_color"'
    );

    if (!empty($rows)) {
        return true;
    }

    return Db::getInstance()->execute(
        'ALTER TABLE `' . $table . "` ADD COLUMN `subtitle_color` VARCHAR(7) NOT NULL DEFAULT '#4b5563' AFTER `text_color`"
    );
}
