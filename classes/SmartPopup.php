<?php
/**
 * SmartPopup ObjectModel and reporting helpers.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SmartPopup extends ObjectModel
{
    public $id_popup;
    public $active;
    public $internal_name;
    public $popup_type;
    public $campaign_goal;
    public $template_key;
    public $layout;
    public $image_position;
    public $trigger_type;
    public $trigger_value;
    public $frequency_days;
    public $width;
    public $bg_color;
    public $text_color;
    public $accent_color;
    public $button_color;
    public $button_text_color;
    public $bg_image;
    public $border_radius;
    public $overlay_opacity;
    public $animation;
    public $close_button_style;
    public $close_on_overlay;
    public $mobile_behavior;
    public $priority;
    public $ab_test_enabled;
    public $winner_metric;
    public $date_start;
    public $date_end;
    public $date_add;
    public $date_upd;

    public $title;
    public $subtitle;
    public $content;
    public $cta_text;
    public $cta_url;
    public $coupon_code;
    public $consent_text;
    public $success_message;

    public static $definition = [
        'table' => 'smart_popup',
        'primary' => 'id_popup',
        'multilang' => true,
        'fields' => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'internal_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'popup_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'campaign_goal' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'template_key' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 80],
            'layout' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'image_position' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 20],
            'trigger_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 20],
            'trigger_value' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'frequency_days' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'width' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'bg_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7],
            'text_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7],
            'accent_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7],
            'button_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7],
            'button_text_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7],
            'bg_image' => ['type' => self::TYPE_STRING, 'size' => 255],
            'border_radius' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'overlay_opacity' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'animation' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'close_button_style' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'close_on_overlay' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'mobile_behavior' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 30],
            'priority' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'ab_test_enabled' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'winner_metric' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'date_start' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'allow_null' => true],
            'date_end' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'allow_null' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'subtitle' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'content' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'cta_text' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 120],
            'cta_url' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'size' => 255],
            'coupon_code' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 80],
            'consent_text' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'success_message' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
        ],
    ];

    public static function getActivePopupsWithTargeting($idLang, $idShop = null)
    {
        if (!self::isSchemaReady()) {
            return [];
        }

        $now = date('Y-m-d H:i:s');

        $sql = new DbQuery();
        $sql->select('p.*, pl.title, pl.subtitle, pl.content, pl.cta_text, pl.cta_url, pl.coupon_code, pl.consent_text, pl.success_message');
        $sql->from('smart_popup', 'p');
        $sql->leftJoin('smart_popup_lang', 'pl', 'p.id_popup = pl.id_popup AND pl.id_lang = ' . (int) $idLang);
        $sql->where('p.active = 1');
        $sql->where('(p.date_start IS NULL OR p.date_start = "0000-00-00 00:00:00" OR p.date_start <= "' . pSQL($now) . '")');
        $sql->where('(p.date_end IS NULL OR p.date_end = "0000-00-00 00:00:00" OR p.date_end >= "' . pSQL($now) . '")');
        $sql->orderBy('p.priority DESC, p.id_popup DESC');

        $popups = Db::getInstance()->executeS($sql);
        if (empty($popups)) {
            return [];
        }

        $popupIds = array_map('intval', array_column($popups, 'id_popup'));
        $targeting = self::loadTargetingForPopups($popupIds);
        $variants = self::loadVariantsForPopups($popupIds, $idLang);

        foreach ($popups as &$popup) {
            $idPopup = (int) $popup['id_popup'];
            $popup['targeting'] = isset($targeting[$idPopup]) ? $targeting[$idPopup] : [];
            $popup['variants'] = isset($variants[$idPopup]) ? $variants[$idPopup] : [];
            if (empty($popup['variants'])) {
                $popup['variants'][] = self::buildDefaultVariant($popup);
            }
        }

        return $popups;
    }

    public static function loadTargetingForPopups($popupIds)
    {
        if (empty($popupIds)) {
            return [];
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('smart_popup_targeting');
        $sql->where('id_popup IN (' . implode(',', array_map('intval', $popupIds)) . ')');
        $sql->orderBy('id_popup ASC, id_targeting ASC');

        $rows = Db::getInstance()->executeS($sql);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['id_popup']][] = $row;
        }

        return $grouped;
    }

    public static function loadVariantsForPopups($popupIds, $idLang)
    {
        if (empty($popupIds)) {
            return [];
        }

        $sql = new DbQuery();
        $sql->select('v.*, vl.title, vl.subtitle, vl.content, vl.cta_text, vl.cta_url, vl.coupon_code');
        $sql->from('smart_popup_variant', 'v');
        $sql->leftJoin('smart_popup_variant_lang', 'vl', 'v.id_variant = vl.id_variant AND vl.id_lang = ' . (int) $idLang);
        $sql->where('v.id_popup IN (' . implode(',', array_map('intval', $popupIds)) . ')');
        $sql->where('v.active = 1');
        $sql->orderBy('v.id_popup ASC, v.variant_key ASC');

        $rows = Db::getInstance()->executeS($sql);
        $grouped = [];
        foreach ($rows as $row) {
            $row['id_variant'] = (int) $row['id_variant'];
            $row['traffic_percentage'] = (int) $row['traffic_percentage'];
            $grouped[(int) $row['id_popup']][] = $row;
        }

        return $grouped;
    }

    public static function buildDefaultVariant($popup)
    {
        return [
            'id_variant' => 0,
            'id_popup' => (int) $popup['id_popup'],
            'variant_key' => 'default',
            'name' => 'Default',
            'active' => 1,
            'traffic_percentage' => 100,
            'title' => isset($popup['title']) ? $popup['title'] : '',
            'subtitle' => isset($popup['subtitle']) ? $popup['subtitle'] : '',
            'content' => isset($popup['content']) ? $popup['content'] : '',
            'cta_text' => isset($popup['cta_text']) ? $popup['cta_text'] : '',
            'cta_url' => isset($popup['cta_url']) ? $popup['cta_url'] : '',
            'coupon_code' => isset($popup['coupon_code']) ? $popup['coupon_code'] : '',
        ];
    }

    public function getTargetingRules()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('smart_popup_targeting');
        $sql->where('id_popup = ' . (int) $this->id);
        $sql->orderBy('id_targeting ASC');

        return Db::getInstance()->executeS($sql);
    }

    public function saveTargetingRules($rules)
    {
        Db::getInstance()->delete('smart_popup_targeting', 'id_popup = ' . (int) $this->id);

        foreach ($rules as $rule) {
            Db::getInstance()->insert('smart_popup_targeting', [
                'id_popup' => (int) $this->id,
                'target_type' => pSQL($rule['target_type']),
                'operator' => pSQL(isset($rule['operator']) ? $rule['operator'] : 'in'),
                'target_value' => pSQL(isset($rule['target_value']) ? $rule['target_value'] : ''),
            ]);
        }

        return true;
    }

    public static function trackEvent($idPopup, $eventType, $idVariant = 0, $context = [], $extra = [])
    {
        if (!self::isSchemaReady()) {
            return false;
        }

        $allowedEvents = [
            'impression',
            'close',
            'cta_click',
            'newsletter_submit',
            'newsletter_success',
            'newsletter_error',
            'coupon_copy',
            'variant_assignment',
        ];

        if (!$idPopup || !in_array($eventType, $allowedEvents)) {
            return false;
        }

        $psContext = Context::getContext();
        $idShop = isset($context['id_shop']) ? (int) $context['id_shop'] : (int) $psContext->shop->id;
        $idLang = isset($context['id_lang']) ? (int) $context['id_lang'] : (int) $psContext->language->id;
        $device = isset($context['device']) ? $context['device'] : 'desktop';
        $pageType = isset($context['page_type']) ? $context['page_type'] : 'other';
        $url = isset($context['url']) ? $context['url'] : Tools::getHttpReferer();
        $sessionKey = !empty($context['session_key']) ? $context['session_key'] : self::buildFallbackSessionKey();
        $sessionKey = Tools::substr((string) $sessionKey, 0, 40);
        $device = Tools::substr((string) $device, 0, 20);
        $pageType = Tools::substr((string) $pageType, 0, 50);
        $eventDate = date('Y-m-d H:i:s');
        $statDate = date('Y-m-d');
        $idVariant = (int) $idVariant;

        $inserted = Db::getInstance()->insert('smart_popup_event', [
            'id_popup' => (int) $idPopup,
            'id_variant' => $idVariant,
            'id_shop' => $idShop,
            'id_lang' => $idLang,
            'event_type' => pSQL($eventType),
            'event_date' => pSQL($eventDate),
            'device' => pSQL($device),
            'page_type' => pSQL($pageType),
            'url_hash' => pSQL($url ? sha1($url) : ''),
            'session_key' => pSQL($sessionKey),
            'extra' => pSQL(json_encode($extra)),
        ], true);

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'smart_popup_stats_daily`
                (`id_popup`, `id_variant`, `event_type`, `stat_date`, `device`, `page_type`, `count`)
                VALUES (
                    ' . (int) $idPopup . ',
                    ' . (int) $idVariant . ',
                    "' . pSQL($eventType) . '",
                    "' . pSQL($statDate) . '",
                    "' . pSQL($device) . '",
                    "' . pSQL($pageType) . '",
                    1
                )
                ON DUPLICATE KEY UPDATE `count` = `count` + 1';

        Db::getInstance()->execute($sql);

        // Probabilistic retention cleanup: keep raw events for 90 days (daily aggregates are kept).
        if (mt_rand(1, 100) === 1) {
            Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . 'smart_popup_event`
                 WHERE `event_date` < "' . pSQL(date('Y-m-d H:i:s', strtotime('-90 days'))) . '"
                 LIMIT 5000'
            );
        }

        return (bool) $inserted;
    }

    public static function recordCouponEvent($idPopup, $idVariant, $couponCode, $eventType, $sessionKey, $email = null)
    {
        if (!$idPopup || !$couponCode) {
            return false;
        }

        return Db::getInstance()->insert('smart_popup_coupon_event', [
            'id_popup' => (int) $idPopup,
            'id_variant' => (int) $idVariant,
            'coupon_code' => pSQL($couponCode),
            'email' => pSQL($email),
            'event_type' => pSQL($eventType),
            'session_key' => pSQL($sessionKey),
            'date_add' => date('Y-m-d H:i:s'),
        ], true);
    }

    public static function getDashboardRows($idLang, $days = 30)
    {
        if (!self::isSchemaReady()) {
            return [];
        }

        $sql = new DbQuery();
        $sql->select('p.*, pl.title');
        $sql->from('smart_popup', 'p');
        $sql->leftJoin('smart_popup_lang', 'pl', 'p.id_popup = pl.id_popup AND pl.id_lang = ' . (int) $idLang);
        $sql->orderBy('p.date_upd DESC, p.id_popup DESC');

        $rows = Db::getInstance()->executeS($sql);
        foreach ($rows as &$row) {
            $row['stats'] = self::getEventTotals((int) $row['id_popup'], $days);
            $row['targeting_summary'] = self::buildTargetingSummary((int) $row['id_popup']);
        }

        return $rows;
    }

    public static function getGlobalStats($days = 30)
    {
        if (!self::isSchemaReady()) {
            return self::normalizeEventTotals([]);
        }

        $dateFrom = date('Y-m-d', strtotime('-' . (int) $days . ' days'));
        $sql = new DbQuery();
        $sql->select('event_type, SUM(count) AS total');
        $sql->from('smart_popup_stats_daily');
        $sql->where('stat_date >= "' . pSQL($dateFrom) . '"');
        $sql->groupBy('event_type');

        return self::normalizeEventTotals(Db::getInstance()->executeS($sql));
    }

    public static function getEventTotals($idPopup, $days = 30)
    {
        if (!self::isSchemaReady()) {
            return self::normalizeEventTotals([]);
        }

        $dateFrom = date('Y-m-d', strtotime('-' . (int) $days . ' days'));
        $sql = new DbQuery();
        $sql->select('event_type, SUM(count) AS total');
        $sql->from('smart_popup_stats_daily');
        $sql->where('id_popup = ' . (int) $idPopup);
        $sql->where('stat_date >= "' . pSQL($dateFrom) . '"');
        $sql->groupBy('event_type');

        return self::normalizeEventTotals(Db::getInstance()->executeS($sql));
    }

    public static function normalizeEventTotals($rows)
    {
        $stats = [
            'impression' => 0,
            'close' => 0,
            'cta_click' => 0,
            'newsletter_success' => 0,
            'newsletter_error' => 0,
            'coupon_copy' => 0,
            'conversions' => 0,
            'conversion_rate' => 0,
            'close_rate' => 0,
        ];

        foreach ($rows as $row) {
            $stats[$row['event_type']] = (int) $row['total'];
        }

        $stats['conversions'] = (int) $stats['cta_click'] + (int) $stats['newsletter_success'] + (int) $stats['coupon_copy'];

        if ($stats['impression'] > 0) {
            $stats['conversion_rate'] = round(($stats['conversions'] / $stats['impression']) * 100, 2);
            $stats['close_rate'] = round(($stats['close'] / $stats['impression']) * 100, 2);
        }

        return $stats;
    }

    public static function getStatsForPopup($idPopup, $days = 30)
    {
        if (!self::isSchemaReady()) {
            return [
                'overview' => self::normalizeEventTotals([]),
                'trend' => ['labels' => [], 'impression' => [], 'conversions' => [], 'close' => []],
                'devices' => [],
                'pages' => [],
                'variants' => [],
            ];
        }

        $dateFrom = date('Y-m-d', strtotime('-' . (int) $days . ' days'));
        $trend = [
            'labels' => [],
            'impression' => [],
            'conversions' => [],
            'close' => [],
        ];

        for ($i = (int) $days; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $trend['labels'][] = date('d M', strtotime($date));
            $trend['impression'][$date] = 0;
            $trend['conversions'][$date] = 0;
            $trend['close'][$date] = 0;
        }

        $sql = new DbQuery();
        $sql->select('stat_date, event_type, SUM(count) AS total');
        $sql->from('smart_popup_stats_daily');
        $sql->where('id_popup = ' . (int) $idPopup);
        $sql->where('stat_date >= "' . pSQL($dateFrom) . '"');
        $sql->groupBy('stat_date, event_type');

        foreach (Db::getInstance()->executeS($sql) as $row) {
            $date = $row['stat_date'];
            if (!isset($trend['impression'][$date])) {
                continue;
            }

            if ($row['event_type'] === 'impression') {
                $trend['impression'][$date] = (int) $row['total'];
            } elseif ($row['event_type'] === 'close') {
                $trend['close'][$date] = (int) $row['total'];
            } elseif (in_array($row['event_type'], ['cta_click', 'newsletter_success', 'coupon_copy'])) {
                $trend['conversions'][$date] += (int) $row['total'];
            }
        }

        $trend['impression'] = array_values($trend['impression']);
        $trend['conversions'] = array_values($trend['conversions']);
        $trend['close'] = array_values($trend['close']);

        return [
            'overview' => self::getEventTotals((int) $idPopup, $days),
            'trend' => $trend,
            'devices' => self::getBreakdown((int) $idPopup, 'device', $days),
            'pages' => self::getBreakdown((int) $idPopup, 'page_type', $days),
            'variants' => self::getVariantBreakdown((int) $idPopup, $days),
        ];
    }

    private static function getBreakdown($idPopup, $field, $days)
    {
        $allowed = ['device', 'page_type'];
        if (!in_array($field, $allowed)) {
            return [];
        }

        $dateFrom = date('Y-m-d', strtotime('-' . (int) $days . ' days'));
        $sql = new DbQuery();
        $sql->select($field . ', event_type, SUM(count) AS total');
        $sql->from('smart_popup_stats_daily');
        $sql->where('id_popup = ' . (int) $idPopup);
        $sql->where('stat_date >= "' . pSQL($dateFrom) . '"');
        $sql->groupBy($field . ', event_type');

        $breakdown = [];
        foreach (Db::getInstance()->executeS($sql) as $row) {
            $key = $row[$field] ? $row[$field] : 'unknown';
            if (!isset($breakdown[$key])) {
                $breakdown[$key] = self::normalizeEventTotals([]);
            }
            $breakdown[$key][$row['event_type']] = (int) $row['total'];
        }

        foreach ($breakdown as &$stats) {
            $stats = self::normalizeEventTotals(self::rowsFromStats($stats));
        }

        return $breakdown;
    }

    private static function getVariantBreakdown($idPopup, $days)
    {
        $dateFrom = date('Y-m-d', strtotime('-' . (int) $days . ' days'));
        $sql = new DbQuery();
        $sql->select('s.id_variant, v.name, v.variant_key, s.event_type, SUM(s.count) AS total');
        $sql->from('smart_popup_stats_daily', 's');
        $sql->leftJoin('smart_popup_variant', 'v', 's.id_variant = v.id_variant');
        $sql->where('s.id_popup = ' . (int) $idPopup);
        $sql->where('s.stat_date >= "' . pSQL($dateFrom) . '"');
        $sql->groupBy('s.id_variant, s.event_type');

        $breakdown = [];
        foreach (Db::getInstance()->executeS($sql) as $row) {
            $key = (int) $row['id_variant'];
            if (!isset($breakdown[$key])) {
                $breakdown[$key] = [
                    'name' => $row['name'] ? $row['name'] : 'Default',
                    'variant_key' => $row['variant_key'] ? $row['variant_key'] : 'default',
                    'stats' => self::normalizeEventTotals([]),
                ];
            }
            $breakdown[$key]['stats'][$row['event_type']] = (int) $row['total'];
        }

        foreach ($breakdown as &$variant) {
            $variant['stats'] = self::normalizeEventTotals(self::rowsFromStats($variant['stats']));
        }

        return $breakdown;
    }

    private static function rowsFromStats($stats)
    {
        $rows = [];
        foreach ($stats as $eventType => $total) {
            if (is_numeric($total)) {
                $rows[] = ['event_type' => $eventType, 'total' => $total];
            }
        }

        return $rows;
    }

    public static function buildTargetingSummary($idPopup)
    {
        if (!self::isSchemaReady()) {
            return 'schema not ready';
        }

        $sql = new DbQuery();
        $sql->select('target_type, COUNT(*) AS total');
        $sql->from('smart_popup_targeting');
        $sql->where('id_popup = ' . (int) $idPopup);
        $sql->groupBy('target_type');

        $parts = [];
        foreach (Db::getInstance()->executeS($sql) as $row) {
            $parts[] = $row['target_type'] . ':' . (int) $row['total'];
        }

        return empty($parts) ? 'all visitors' : implode(', ', $parts);
    }

    public function add($autoDate = true, $nullValues = false)
    {
        $result = parent::add($autoDate, $nullValues);
        if ($result && class_exists('Ps_advanced_popup')) {
            Ps_advanced_popup::clearCache();
        }

        return $result;
    }

    public function update($nullValues = false)
    {
        $result = parent::update($nullValues);
        if ($result && class_exists('Ps_advanced_popup')) {
            Ps_advanced_popup::clearCache();
        }

        return $result;
    }

    public function delete()
    {
        $idPopup = (int) $this->id;
        $result = parent::delete();

        if ($result) {
            if (class_exists('Ps_advanced_popup')) {
                Ps_advanced_popup::clearCache();
            }
            Db::getInstance()->delete('smart_popup_targeting', 'id_popup = ' . $idPopup);
            Db::getInstance()->delete('smart_popup_variant_lang', 'id_variant IN (SELECT id_variant FROM `' . _DB_PREFIX_ . 'smart_popup_variant` WHERE id_popup = ' . $idPopup . ')');
            Db::getInstance()->delete('smart_popup_variant', 'id_popup = ' . $idPopup);
            Db::getInstance()->delete('smart_popup_event', 'id_popup = ' . $idPopup);
            Db::getInstance()->delete('smart_popup_stats_daily', 'id_popup = ' . $idPopup);
            Db::getInstance()->delete('smart_popup_subscriber', 'id_popup = ' . $idPopup);
            Db::getInstance()->delete('smart_popup_coupon_event', 'id_popup = ' . $idPopup);
        }

        return $result;
    }

    private static function buildFallbackSessionKey()
    {
        $seed = Tools::getRemoteAddr() . '|' . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

        return sha1($seed);
    }

    public static function isSchemaReady()
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $variantTable = _DB_PREFIX_ . 'smart_popup_variant';
        $langTable = _DB_PREFIX_ . 'smart_popup_lang';

        $variantRows = Db::getInstance()->executeS('SHOW TABLES LIKE "' . pSQL($variantTable) . '"');
        $langRows = Db::getInstance()->executeS('SHOW TABLES LIKE "' . pSQL($langTable) . '"');
        if (empty($variantRows) || empty($langRows)) {
            $ready = false;
            return $ready;
        }

        $subtitleRows = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . bqSQL($langTable) . '` LIKE "subtitle"');
        $ready = !empty($subtitleRows);

        return $ready;
    }
}
