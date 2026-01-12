<?php
/**
 * SmartPopup ObjectModel Class
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SmartPopup extends ObjectModel
{
    public $id_popup;
    public $active;
    public $popup_type;
    public $date_start;
    public $date_end;
    public $trigger_type;
    public $trigger_value;
    public $frequency_days;
    public $width;
    public $bg_color;
    public $bg_image;
    public $border_radius;
    public $overlay_opacity;
    public $animation;
    public $close_button_style;
    public $hide_on_mobile;
    public $priority;
    public $date_add;
    public $date_upd;

    // Lang fields
    public $title;
    public $content;
    public $cta_text;
    public $cta_url;

    public static $definition = [
        'table' => 'smart_popup',
        'primary' => 'id_popup',
        'multilang' => true,
        'fields' => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'popup_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 20],
            'date_start' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_end' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'trigger_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 20],
            'trigger_value' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'frequency_days' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'width' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'bg_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7],
            'bg_image' => ['type' => self::TYPE_STRING, 'size' => 255],
            'border_radius' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'overlay_opacity' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'animation' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'close_button_style' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'hide_on_mobile' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'priority' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            // Lang fields
            'title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'content' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'cta_text' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 100],
            'cta_url' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'size' => 255],
        ],
    ];

    /**
     * Get all active popups
     */
    public static function getActivePopups($idLang)
    {
        $now = date('Y-m-d H:i:s');

        $sql = new DbQuery();
        $sql->select('p.*, pl.title, pl.content, pl.cta_text, pl.cta_url');
        $sql->from('smart_popup', 'p');
        $sql->leftJoin('smart_popup_lang', 'pl', 'p.id_popup = pl.id_popup AND pl.id_lang = ' . (int) $idLang);
        $sql->where('p.active = 1');
        $sql->where('(p.date_start IS NULL OR p.date_start <= "' . pSQL($now) . '")');
        $sql->where('(p.date_end IS NULL OR p.date_end >= "' . pSQL($now) . '")');
        $sql->orderBy('p.priority DESC');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get all active popups with targeting in single query
     */
    public static function getActivePopupsWithTargeting($idLang)
    {
        $now = date('Y-m-d H:i:s');

        $sql = new DbQuery();
        $sql->select('p.*, pl.title, pl.content, pl.cta_text, pl.cta_url');
        $sql->from('smart_popup', 'p');
        $sql->leftJoin('smart_popup_lang', 'pl', 'p.id_popup = pl.id_popup AND pl.id_lang = ' . (int) $idLang);
        $sql->where('p.active = 1');
        $sql->where('(p.date_start IS NULL OR p.date_start <= "' . pSQL($now) . '")');
        $sql->where('(p.date_end IS NULL OR p.date_end >= "' . pSQL($now) . '")');
        $sql->orderBy('p.priority DESC');

        $popups = Db::getInstance()->executeS($sql);

        if (empty($popups)) {
            return [];
        }

        $popupIds = array_column($popups, 'id_popup');

        $targetingSql = new DbQuery();
        $targetingSql->select('*');
        $targetingSql->from('smart_popup_targeting');
        $targetingSql->where('id_popup IN (' . implode(',', array_map('intval', $popupIds)) . ')');

        $targetingRules = Db::getInstance()->executeS($targetingSql);

        $targetingByPopup = [];
        foreach ($targetingRules as $rule) {
            $targetingByPopup[$rule['id_popup']][] = $rule;
        }

        foreach ($popups as &$popup) {
            $popup['targeting'] = isset($targetingByPopup[$popup['id_popup']])
                ? $targetingByPopup[$popup['id_popup']]
                : [];
        }

        return $popups;
    }

    /**
     * Get popup targeting rules
     */
    public function getTargetingRules()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('smart_popup_targeting');
        $sql->where('id_popup = ' . (int) $this->id);

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Save targeting rules
     */
    public function saveTargetingRules($rules)
    {
        Db::getInstance()->delete('smart_popup_targeting', 'id_popup = ' . (int) $this->id);

        foreach ($rules as $rule) {
            Db::getInstance()->insert('smart_popup_targeting', [
                'id_popup' => (int) $this->id,
                'target_type' => pSQL($rule['target_type']),
                'target_ids' => pSQL($rule['target_ids']),
            ]);
        }

        return true;
    }

    /**
     * Increment stat counter
     */
    public static function incrementStat($idPopup, $statType)
    {
        $date = date('Y-m-d');

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'smart_popup_stats`
                (`id_popup`, `stat_type`, `stat_date`, `count`)
                VALUES (' . (int) $idPopup . ', "' . pSQL($statType) . '", "' . pSQL($date) . '", 1)
                ON DUPLICATE KEY UPDATE `count` = `count` + 1';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Get stats for popup
     */
    public function getStats($days = 30)
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));

        $sql = new DbQuery();
        $sql->select('stat_type, SUM(count) as total');
        $sql->from('smart_popup_stats');
        $sql->where('id_popup = ' . (int) $this->id);
        $sql->where('stat_date >= "' . pSQL($dateFrom) . '"');
        $sql->groupBy('stat_type');

        $results = Db::getInstance()->executeS($sql);

        $stats = ['impression' => 0, 'conversion' => 0];
        foreach ($results as $row) {
            $stats[$row['stat_type']] = (int) $row['total'];
        }

        return $stats;
    }

    /**
     * Override add to clear cache
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $result = parent::add($autoDate, $nullValues);
        if ($result) {
            Ps_advanced_popup::clearCache();
        }
        return $result;
    }

    /**
     * Override update to clear cache
     */
    public function update($nullValues = false)
    {
        $result = parent::update($nullValues);
        if ($result) {
            Ps_advanced_popup::clearCache();
        }
        return $result;
    }

    /**
     * Override delete to clear cache and related data
     */
    public function delete()
    {
        $result = parent::delete();
        if ($result) {
            Ps_advanced_popup::clearCache();
            Db::getInstance()->delete('smart_popup_targeting', 'id_popup = ' . (int) $this->id);
            Db::getInstance()->delete('smart_popup_stats', 'id_popup = ' . (int) $this->id);
        }
        return $result;
    }
}
