<?php
/**
 * Custom admin controller for Advanced Popup Studio.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ps_advanced_popup/classes/SmartPopup.php';

class AdminSmartPopupController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'smart_popup';
        $this->className = 'SmartPopup';
        $this->identifier = 'id_popup';
        $this->bootstrap = true;
        $this->lang = true;

        parent::__construct();
    }

    public function initContent()
    {
        $this->processSmartPost();
        $this->processSmartAction();

        $view = Tools::getValue('aps_view', Tools::getValue('action'));
        if ($view === 'stats') {
            $this->content = $this->renderStats();
        } elseif ($view === 'add' || $view === 'edit') {
            $this->content = $this->renderEditor();
        } else {
            $this->content = $this->renderDashboard();
        }

        parent::initContent();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS(_MODULE_DIR_ . 'ps_advanced_popup/views/css/back.css');
        $this->addJS(_MODULE_DIR_ . 'ps_advanced_popup/views/js/vendor/chart-lite.js');
        $this->addJS(_MODULE_DIR_ . 'ps_advanced_popup/views/js/back.js');
    }

    private function processSmartPost()
    {
        if (!Tools::isSubmit('submitSmartPopup')) {
            return;
        }

        if (!SmartPopup::isSchemaReady()) {
            $this->errors[] = $this->l('The database schema is not ready for version 2.0. Please run the module upgrade or reset the module.');
            return;
        }

        if (!$this->isSmartTokenValid()) {
            $this->errors[] = $this->l('Invalid security token.');
            return;
        }

        $idPopup = (int) Tools::getValue('id_popup');
        $existing = $idPopup ? $this->getPopupRow($idPopup) : [];
        $imagePath = $this->processImageUpload(isset($existing['bg_image']) ? $existing['bg_image'] : '');
        $popupData = $this->collectPopupData($imagePath);

        if ($popupData['internal_name'] === '') {
            $this->errors[] = $this->l('Internal name is required.');
        }

        $this->validateLangInputs();

        if (!empty($this->errors)) {
            return;
        }

        if ($idPopup) {
            $popupData['date_upd'] = date('Y-m-d H:i:s');
            Db::getInstance()->update('smart_popup', $popupData, 'id_popup = ' . (int) $idPopup, 0, true);
        } else {
            $popupData['date_add'] = date('Y-m-d H:i:s');
            $popupData['date_upd'] = date('Y-m-d H:i:s');
            Db::getInstance()->insert('smart_popup', $popupData, true);
            $idPopup = (int) Db::getInstance()->Insert_ID();
        }

        $this->saveLangValues($idPopup);
        $this->saveTargetingValues($idPopup);
        $this->saveVariantValues($idPopup);

        Ps_advanced_popup::clearCache();

        $redirect = $this->context->link->getAdminLink('AdminSmartPopup');
        if (Tools::isSubmit('saveAndStay')) {
            $redirect .= '&aps_view=edit&id_popup=' . (int) $idPopup . '&conf=4';
        } else {
            $redirect .= '&conf=4';
        }

        Tools::redirectAdmin($redirect);
    }

    private function processSmartAction()
    {
        $smartAction = Tools::getValue('smart_action');
        if (!$smartAction) {
            return;
        }

        if (!$this->isSmartTokenValid()) {
            $this->errors[] = $this->l('Invalid security token.');
            return;
        }

        $idPopup = (int) Tools::getValue('id_popup');
        if (!$idPopup) {
            return;
        }

        if ($smartAction === 'toggle') {
            $active = (int) Tools::getValue('active');
            Db::getInstance()->update('smart_popup', [
                'active' => $active ? 1 : 0,
                'date_upd' => date('Y-m-d H:i:s'),
            ], 'id_popup = ' . (int) $idPopup);
            Ps_advanced_popup::clearCache();
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSmartPopup') . '&conf=4');
        }

        if ($smartAction === 'delete') {
            $popup = new SmartPopup($idPopup);
            if (Validate::isLoadedObject($popup)) {
                $popup->delete();
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSmartPopup') . '&conf=1');
        }

        if ($smartAction === 'duplicate') {
            $newId = $this->duplicatePopup($idPopup);
            $url = $this->context->link->getAdminLink('AdminSmartPopup');
            if ($newId) {
                $url .= '&aps_view=edit&id_popup=' . (int) $newId . '&conf=19';
            }
            Tools::redirectAdmin($url);
        }
    }

    private function isSmartTokenValid()
    {
        $token = (string) Tools::getValue('token');

        return $token !== '' && hash_equals((string) $this->token, $token);
    }

    private function renderDashboard()
    {
        $rows = SmartPopup::getDashboardRows((int) $this->context->language->id, 30);
        foreach ($rows as &$row) {
            $idPopup = (int) $row['id_popup'];
            $row['edit_url'] = $this->context->link->getAdminLink('AdminSmartPopup') . '&aps_view=edit&id_popup=' . $idPopup;
            $row['stats_url'] = $this->context->link->getAdminLink('AdminSmartPopup') . '&aps_view=stats&id_popup=' . $idPopup;
            $row['duplicate_url'] = $this->context->link->getAdminLink('AdminSmartPopup') . '&smart_action=duplicate&id_popup=' . $idPopup;
            $row['toggle_url'] = $this->context->link->getAdminLink('AdminSmartPopup') . '&smart_action=toggle&id_popup=' . $idPopup . '&active=' . ((int) $row['active'] ? 0 : 1);
            $row['delete_url'] = $this->context->link->getAdminLink('AdminSmartPopup') . '&smart_action=delete&id_popup=' . $idPopup;
            $row['preview_url'] = $row['edit_url'] . '#popup-preview';
        }

        $this->context->smarty->assign([
            'dashboard_title' => $this->l('Advanced Popup Studio'),
            'popups' => $rows,
            'totals' => SmartPopup::getGlobalStats(30),
            'templates' => $this->getPresetTemplates(),
            'add_url' => $this->context->link->getAdminLink('AdminSmartPopup') . '&aps_view=add',
            'token' => $this->token,
            'schema_ready' => SmartPopup::isSchemaReady(),
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'ps_advanced_popup/views/templates/admin/dashboard.tpl'
        );
    }

    private function renderEditor()
    {
        $idPopup = (int) Tools::getValue('id_popup');
        if (!SmartPopup::isSchemaReady()) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSmartPopup'));
        }

        $templateKey = Tools::getValue('template_key', 'newsletter_discount');
        $popup = $idPopup ? $this->getPopupRow($idPopup) : $this->getDefaultPopup($templateKey);

        if (!$popup) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSmartPopup'));
        }
        $popup['bg_image_url'] = $this->getPublicAssetUrl(isset($popup['bg_image']) ? $popup['bg_image'] : '');

        $this->context->smarty->assign([
            'editor_title' => $idPopup ? $this->l('Edit popup') : $this->l('Create popup'),
            'popup' => $popup,
            'lang_values' => $idPopup ? $this->getLangValues($idPopup) : $this->getDefaultLangValues($templateKey),
            'targeting' => $idPopup ? $this->getTargetingValues($idPopup) : $this->getDefaultTargetingValues($templateKey),
            'variants' => $idPopup ? $this->getVariantValues($idPopup) : $this->getDefaultVariants($templateKey),
            'templates' => $this->getPresetTemplates(),
            'languages' => Language::getLanguages(true),
            'default_language_id' => (int) Configuration::get('PS_LANG_DEFAULT'),
            'groups' => Group::getGroups((int) $this->context->language->id),
            'currencies' => Currency::getCurrencies(false, true),
            'page_type_options' => ['home', 'category', 'product', 'cart', 'checkout', 'search', 'cms', 'other'],
            'device_options' => ['desktop', 'tablet', 'mobile'],
            'layout_options' => ['centered', 'image_top', 'image_left', 'image_right', 'full_image', 'compact_coupon', 'newsletter'],
            'trigger_options' => ['load', 'exit', 'scroll', 'inactivity'],
            'mobile_options' => ['bottom_sheet', 'modal', 'hidden'],
            'form_action' => $this->context->link->getAdminLink('AdminSmartPopup'),
            'back_url' => $this->context->link->getAdminLink('AdminSmartPopup'),
            'token' => $this->token,
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'ps_advanced_popup/views/templates/admin/editor.tpl'
        );
    }

    public function renderStats()
    {
        $idPopup = (int) Tools::getValue('id_popup');
        if (!$idPopup) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSmartPopup'));
        }

        $popup = $this->getPopupRow($idPopup);
        $stats = SmartPopup::getStatsForPopup($idPopup, 30);

        $this->context->smarty->assign([
            'popup' => $popup,
            'stats' => $stats,
            'chartData' => json_encode($stats['trend']),
            'back_url' => $this->context->link->getAdminLink('AdminSmartPopup'),
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'ps_advanced_popup/views/templates/admin/stats.tpl'
        );
    }

    private function getPublicAssetUrl($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^(?:https?:)?//#', $path) || $path[0] === '/') {
            return $path;
        }

        return rtrim(__PS_BASE_URI__, '/') . '/' . ltrim($path, '/');
    }

    private function collectPopupData($imagePath)
    {
        $templateKey = $this->sanitizeChoice(Tools::getValue('template_key'), array_keys($this->getPresetTemplates()), 'announcement');

        return [
            'active' => (int) Tools::getValue('active'),
            'internal_name' => pSQL(trim(Tools::getValue('internal_name'))),
            'popup_type' => $this->sanitizeChoice(Tools::getValue('popup_type'), ['newsletter', 'coupon', 'cta', 'image', 'announcement'], 'cta'),
            'campaign_goal' => $this->sanitizeChoice(Tools::getValue('campaign_goal'), ['newsletter', 'coupon', 'cart_recovery', 'free_shipping', 'upsell', 'image_campaign', 'announcement'], 'announcement'),
            'template_key' => pSQL($templateKey),
            'layout' => $this->sanitizeChoice(Tools::getValue('layout'), ['centered', 'image_top', 'image_left', 'image_right', 'full_image', 'compact_coupon', 'newsletter'], 'centered'),
            'image_position' => $this->sanitizeChoice(Tools::getValue('image_position'), ['top', 'left', 'right', 'cover'], 'top'),
            'trigger_type' => $this->sanitizeChoice(Tools::getValue('trigger_type'), ['load', 'exit', 'scroll', 'inactivity'], 'load'),
            'trigger_value' => max(0, (int) Tools::getValue('trigger_value')),
            'frequency_days' => max(0, (int) Tools::getValue('frequency_days')),
            'width' => max(320, min(920, (int) Tools::getValue('width'))),
            'bg_color' => $this->sanitizeColor(Tools::getValue('bg_color'), '#ffffff'),
            'text_color' => $this->sanitizeColor(Tools::getValue('text_color'), '#1f2937'),
            'subtitle_color' => $this->sanitizeColor(Tools::getValue('subtitle_color'), '#4b5563'),
            'accent_color' => $this->sanitizeColor(Tools::getValue('accent_color'), '#25b9d7'),
            'button_color' => $this->sanitizeColor(Tools::getValue('button_color'), '#111827'),
            'button_text_color' => $this->sanitizeColor(Tools::getValue('button_text_color'), '#ffffff'),
            'bg_image' => pSQL($imagePath),
            'border_radius' => max(0, min(32, (int) Tools::getValue('border_radius'))),
            'overlay_opacity' => max(0, min(0.85, (float) Tools::getValue('overlay_opacity'))),
            'animation' => $this->sanitizeChoice(Tools::getValue('animation'), ['fadeIn', 'fadeInUp', 'zoomIn'], 'fadeIn'),
            'close_button_style' => $this->sanitizeChoice(Tools::getValue('close_button_style'), ['default', 'circle', 'square', 'text'], 'circle'),
            'close_on_overlay' => (int) Tools::getValue('close_on_overlay'),
            'mobile_behavior' => $this->sanitizeChoice(Tools::getValue('mobile_behavior'), ['bottom_sheet', 'modal', 'hidden'], 'bottom_sheet'),
            'priority' => max(0, (int) Tools::getValue('priority')),
            'ab_test_enabled' => (int) Tools::getValue('ab_test_enabled'),
            'winner_metric' => $this->sanitizeChoice(Tools::getValue('winner_metric'), ['conversion_rate', 'cta_click', 'newsletter_success', 'coupon_copy'], 'conversion_rate'),
            'date_start' => $this->normalizeDateTime(Tools::getValue('date_start')),
            'date_end' => $this->normalizeDateTime(Tools::getValue('date_end')),
        ];
    }

    /**
     * The controller writes with raw SQL, so ObjectModel validation never runs.
     * Enforce isCleanHtml / isUrl here to block stored XSS and broken URLs.
     */
    private function validateLangInputs()
    {
        $cleanHtmlFields = ['title', 'subtitle', 'content', 'cta_text', 'coupon_code', 'consent_text', 'success_message'];
        $variantCleanFields = ['title', 'subtitle', 'content', 'cta_text', 'coupon_code'];

        foreach (Language::getLanguages(false) as $lang) {
            $idLang = (int) $lang['id_lang'];

            foreach ($cleanHtmlFields as $fieldName) {
                $value = (string) Tools::getValue($fieldName . '_' . $idLang);
                if ($value !== '' && !Validate::isCleanHtml($value)) {
                    $this->errors[] = sprintf($this->l('Invalid content in field "%s" (%s).'), $fieldName, $lang['iso_code']);
                }
            }

            $ctaUrl = trim((string) Tools::getValue('cta_url_' . $idLang));
            if ($ctaUrl !== '' && (!Validate::isUrl($ctaUrl) || preg_match('/^\s*javascript:/i', $ctaUrl))) {
                $this->errors[] = sprintf($this->l('Invalid CTA URL (%s).'), $lang['iso_code']);
            }

            foreach (['a', 'b'] as $variantKey) {
                foreach ($variantCleanFields as $fieldName) {
                    $value = (string) Tools::getValue('variant_' . $variantKey . '_' . $fieldName . '_' . $idLang);
                    if ($value !== '' && !Validate::isCleanHtml($value)) {
                        $this->errors[] = sprintf($this->l('Invalid content in variant %s field "%s" (%s).'), strtoupper($variantKey), $fieldName, $lang['iso_code']);
                    }
                }

                $variantUrl = trim((string) Tools::getValue('variant_' . $variantKey . '_cta_url_' . $idLang));
                if ($variantUrl !== '' && (!Validate::isUrl($variantUrl) || preg_match('/^\s*javascript:/i', $variantUrl))) {
                    $this->errors[] = sprintf($this->l('Invalid variant %s CTA URL (%s).'), strtoupper($variantKey), $lang['iso_code']);
                }
            }
        }
    }

    private function saveLangValues($idPopup)
    {
        Db::getInstance()->delete('smart_popup_lang', 'id_popup = ' . (int) $idPopup);

        foreach (Language::getLanguages(false) as $lang) {
            $idLang = (int) $lang['id_lang'];
            Db::getInstance()->insert('smart_popup_lang', [
                'id_popup' => (int) $idPopup,
                'id_lang' => $idLang,
                'title' => pSQL(Tools::getValue('title_' . $idLang)),
                'subtitle' => pSQL(Tools::getValue('subtitle_' . $idLang)),
                'content' => pSQL(Tools::getValue('content_' . $idLang), true),
                'cta_text' => pSQL(Tools::getValue('cta_text_' . $idLang)),
                'cta_url' => pSQL(Tools::getValue('cta_url_' . $idLang)),
                'coupon_code' => pSQL(Tools::getValue('coupon_code_' . $idLang)),
                'consent_text' => pSQL(Tools::getValue('consent_text_' . $idLang)),
                'success_message' => pSQL(Tools::getValue('success_message_' . $idLang)),
            ], true);
        }
    }

    private function saveTargetingValues($idPopup)
    {
        Db::getInstance()->delete('smart_popup_targeting', 'id_popup = ' . (int) $idPopup);
        $rules = [];

        $this->addArrayRule($rules, 'page_type', 'in', Tools::getValue('target_page_types'));
        $this->addArrayRule($rules, 'device', 'in', Tools::getValue('target_devices'));
        $groups = array_filter(array_map('intval', (array) Tools::getValue('target_groups')));
        $this->addArrayRule($rules, 'customer_group', 'in', $groups);
        $this->addArrayRule($rules, 'language', 'in', Tools::getValue('target_languages'));
        $this->addArrayRule($rules, 'currency', 'in', Tools::getValue('target_currencies'));

        $loginState = Tools::getValue('target_login_state');
        if (in_array($loginState, ['logged', 'guest'])) {
            $rules[] = ['target_type' => 'login_state', 'operator' => 'eq', 'target_value' => $loginState];
        }

        $urlContains = trim(Tools::getValue('target_url_contains'));
        if ($urlContains !== '') {
            $rules[] = ['target_type' => 'url', 'operator' => 'contains', 'target_value' => $urlContains];
        }

        $minCartTotal = trim(Tools::getValue('target_min_cart_total'));
        if ($minCartTotal !== '') {
            $rules[] = ['target_type' => 'cart_total', 'operator' => 'gte', 'target_value' => (string) max(0, (float) $minCartTotal)];
        }

        $this->addCsvRule($rules, 'cart_product', Tools::getValue('target_cart_products'));
        $this->addCsvRule($rules, 'cart_category', Tools::getValue('target_cart_categories'));

        foreach ($rules as $rule) {
            Db::getInstance()->insert('smart_popup_targeting', [
                'id_popup' => (int) $idPopup,
                'target_type' => pSQL($rule['target_type']),
                'operator' => pSQL($rule['operator']),
                'target_value' => pSQL($rule['target_value']),
            ]);
        }
    }

    private function saveVariantValues($idPopup)
    {
        $existingVariants = [];
        $variantRows = Db::getInstance()->executeS(
            'SELECT id_variant, variant_key FROM `' . _DB_PREFIX_ . 'smart_popup_variant` WHERE id_popup = ' . (int) $idPopup
        );
        foreach ((array) $variantRows as $row) {
            $existingVariants[$row['variant_key']] = (int) $row['id_variant'];
        }

        $abEnabled = (int) Tools::getValue('ab_test_enabled');
        $trafficA = max(1, min(99, (int) Tools::getValue('variant_a_traffic', 50)));
        if (!$abEnabled) {
            $trafficA = 100;
        }
        $variants = [
            'a' => [
                'name' => Tools::getValue('variant_a_name', 'Variant A'),
                'active' => 1,
                'traffic_percentage' => $trafficA,
            ],
            'b' => [
                'name' => Tools::getValue('variant_b_name', 'Variant B'),
                'active' => $abEnabled ? 1 : 0,
                'traffic_percentage' => $abEnabled ? 100 - $trafficA : 0,
            ],
        ];

        foreach ($variants as $key => $variant) {
            if (isset($existingVariants[$key])) {
                // Keep id_variant stable so visitor cookies and historical stats stay valid.
                $idVariant = $existingVariants[$key];
                Db::getInstance()->update('smart_popup_variant', [
                    'name' => pSQL($variant['name']),
                    'active' => (int) $variant['active'],
                    'traffic_percentage' => (int) $variant['traffic_percentage'],
                    'date_upd' => date('Y-m-d H:i:s'),
                ], 'id_variant = ' . (int) $idVariant);
                Db::getInstance()->delete('smart_popup_variant_lang', 'id_variant = ' . (int) $idVariant);
            } else {
                Db::getInstance()->insert('smart_popup_variant', [
                    'id_popup' => (int) $idPopup,
                    'variant_key' => pSQL($key),
                    'name' => pSQL($variant['name']),
                    'active' => (int) $variant['active'],
                    'traffic_percentage' => (int) $variant['traffic_percentage'],
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s'),
                ]);
                $idVariant = (int) Db::getInstance()->Insert_ID();
            }

            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                Db::getInstance()->insert('smart_popup_variant_lang', [
                    'id_variant' => $idVariant,
                    'id_lang' => $idLang,
                    'title' => pSQL(Tools::getValue('variant_' . $key . '_title_' . $idLang)),
                    'subtitle' => pSQL(Tools::getValue('variant_' . $key . '_subtitle_' . $idLang)),
                    'content' => pSQL(Tools::getValue('variant_' . $key . '_content_' . $idLang), true),
                    'cta_text' => pSQL(Tools::getValue('variant_' . $key . '_cta_text_' . $idLang)),
                    'cta_url' => pSQL(Tools::getValue('variant_' . $key . '_cta_url_' . $idLang)),
                    'coupon_code' => pSQL(Tools::getValue('variant_' . $key . '_coupon_code_' . $idLang)),
                ], true);
            }
        }
    }

    private function getPopupRow($idPopup)
    {
        $sql = new DbQuery();
        $sql->select('p.*, pl.title');
        $sql->from('smart_popup', 'p');
        $sql->leftJoin('smart_popup_lang', 'pl', 'p.id_popup = pl.id_popup AND pl.id_lang = ' . (int) $this->context->language->id);
        $sql->where('p.id_popup = ' . (int) $idPopup);

        return Db::getInstance()->getRow($sql);
    }

    private function getLangValues($idPopup)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'smart_popup_lang` WHERE id_popup = ' . (int) $idPopup
        );
        $values = [];
        foreach ($rows as $row) {
            $values[(int) $row['id_lang']] = $row;
        }

        return $values;
    }

    private function getTargetingValues($idPopup)
    {
        $values = $this->getDefaultTargetingValues('announcement');
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'smart_popup_targeting` WHERE id_popup = ' . (int) $idPopup
        );

        foreach ($rows as $row) {
            $decoded = json_decode($row['target_value'], true);
            $value = is_array($decoded) ? $decoded : $row['target_value'];
            switch ($row['target_type']) {
                case 'page_type':
                    $values['page_types'] = $value;
                    break;
                case 'device':
                    $values['devices'] = $value;
                    break;
                case 'customer_group':
                    $values['groups'] = array_map('intval', (array) $value);
                    break;
                case 'language':
                    $values['languages'] = (array) $value;
                    break;
                case 'currency':
                    $values['currencies'] = (array) $value;
                    break;
                case 'login_state':
                    $values['login_state'] = $value;
                    break;
                case 'url':
                    $values['url_contains'] = $value;
                    break;
                case 'cart_total':
                    $values['min_cart_total'] = $value;
                    break;
                case 'cart_product':
                    $values['cart_products'] = implode(',', (array) $value);
                    break;
                case 'cart_category':
                    $values['cart_categories'] = implode(',', (array) $value);
                    break;
            }
        }

        return $values;
    }

    private function getVariantValues($idPopup)
    {
        $variants = $this->getDefaultVariants('announcement');
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'smart_popup_variant` WHERE id_popup = ' . (int) $idPopup . ' ORDER BY variant_key ASC'
        );

        foreach ($rows as $row) {
            $key = $row['variant_key'];
            $variants[$key]['id_variant'] = (int) $row['id_variant'];
            $variants[$key]['name'] = $row['name'];
            $variants[$key]['active'] = (int) $row['active'];
            $variants[$key]['traffic_percentage'] = (int) $row['traffic_percentage'];
            $variants[$key]['lang'] = [];

            $langRows = Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'smart_popup_variant_lang` WHERE id_variant = ' . (int) $row['id_variant']
            );
            foreach ($langRows as $langRow) {
                $variants[$key]['lang'][(int) $langRow['id_lang']] = $langRow;
            }
        }

        return $variants;
    }

    private function duplicatePopup($idPopup)
    {
        $popup = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'smart_popup` WHERE id_popup = ' . (int) $idPopup
        );
        if (!$popup) {
            return 0;
        }

        unset($popup['id_popup']);
        $popup['active'] = 0;
        $popup['internal_name'] = $this->l('Copy of ') . $popup['internal_name'];
        $popup['date_add'] = date('Y-m-d H:i:s');
        $popup['date_upd'] = date('Y-m-d H:i:s');
        Db::getInstance()->insert('smart_popup', $this->escapeRow($popup, ['content']), true);
        $newId = (int) Db::getInstance()->Insert_ID();

        foreach (Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'smart_popup_lang` WHERE id_popup = ' . (int) $idPopup) as $lang) {
            $lang['id_popup'] = $newId;
            Db::getInstance()->insert('smart_popup_lang', $this->escapeRow($lang, ['content']), true);
        }

        foreach (Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'smart_popup_targeting` WHERE id_popup = ' . (int) $idPopup) as $rule) {
            unset($rule['id_targeting']);
            $rule['id_popup'] = $newId;
            Db::getInstance()->insert('smart_popup_targeting', $this->escapeRow($rule), true);
        }

        foreach (Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'smart_popup_variant` WHERE id_popup = ' . (int) $idPopup) as $variant) {
            $oldVariantId = (int) $variant['id_variant'];
            unset($variant['id_variant']);
            $variant['id_popup'] = $newId;
            $variant['date_add'] = date('Y-m-d H:i:s');
            $variant['date_upd'] = date('Y-m-d H:i:s');
            Db::getInstance()->insert('smart_popup_variant', $this->escapeRow($variant), true);
            $newVariantId = (int) Db::getInstance()->Insert_ID();

            foreach (Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'smart_popup_variant_lang` WHERE id_variant = ' . $oldVariantId) as $variantLang) {
                $variantLang['id_variant'] = $newVariantId;
                Db::getInstance()->insert('smart_popup_variant_lang', $this->escapeRow($variantLang, ['content']), true);
            }
        }

        Ps_advanced_popup::clearCache();

        return $newId;
    }

    private function getPresetTemplates()
    {
        return [
            'newsletter_discount' => [
                'label' => $this->l('Newsletter discount'),
                'goal' => 'newsletter',
                'type' => 'newsletter',
                'layout' => 'newsletter',
                'trigger' => 'load',
                'trigger_value' => 4,
                'title' => $this->l('Get 10% off your first order'),
                'subtitle' => $this->l('Join the list for launches and private offers.'),
                'content' => $this->l('We send useful campaign updates, not noise.'),
                'cta' => $this->l('Subscribe'),
            ],
            'exit_coupon' => [
                'label' => $this->l('Exit intent coupon'),
                'goal' => 'coupon',
                'type' => 'coupon',
                'layout' => 'compact_coupon',
                'trigger' => 'exit',
                'trigger_value' => 0,
                'title' => $this->l('Before you go'),
                'subtitle' => $this->l('Use this code before checkout.'),
                'content' => $this->l('A small reason to finish the order today.'),
                'cta' => $this->l('Copy code'),
                'coupon' => 'SAVE10',
            ],
            'cart_reminder' => [
                'label' => $this->l('Cart reminder'),
                'goal' => 'cart_recovery',
                'type' => 'cta',
                'layout' => 'centered',
                'trigger' => 'inactivity',
                'trigger_value' => 30,
                'title' => $this->l('Your cart is waiting'),
                'subtitle' => $this->l('Complete checkout before your items sell out.'),
                'content' => $this->l('You can return to the cart and finish in a few seconds.'),
                'cta' => $this->l('View cart'),
            ],
            'free_shipping' => [
                'label' => $this->l('Free shipping'),
                'goal' => 'free_shipping',
                'type' => 'announcement',
                'layout' => 'image_top',
                'trigger' => 'load',
                'trigger_value' => 2,
                'title' => $this->l('Free shipping today'),
                'subtitle' => $this->l('A cleaner offer for visitors ready to buy.'),
                'content' => $this->l('Highlight a simple threshold or limited-time benefit.'),
                'cta' => $this->l('Shop now'),
            ],
            'product_upsell' => [
                'label' => $this->l('Product upsell'),
                'goal' => 'upsell',
                'type' => 'cta',
                'layout' => 'image_left',
                'trigger' => 'scroll',
                'trigger_value' => 45,
                'title' => $this->l('Pairs well with this product'),
                'subtitle' => $this->l('Show a relevant add-on without blocking the page too early.'),
                'content' => $this->l('Use product-page targeting for the best result.'),
                'cta' => $this->l('See recommendation'),
            ],
            'image_campaign' => [
                'label' => $this->l('Image campaign'),
                'goal' => 'image_campaign',
                'type' => 'image',
                'layout' => 'full_image',
                'trigger' => 'load',
                'trigger_value' => 3,
                'title' => $this->l('Campaign image'),
                'subtitle' => '',
                'content' => '',
                'cta' => $this->l('Open campaign'),
            ],
            'announcement' => [
                'label' => $this->l('Announcement'),
                'goal' => 'announcement',
                'type' => 'announcement',
                'layout' => 'centered',
                'trigger' => 'load',
                'trigger_value' => 2,
                'title' => $this->l('Important update'),
                'subtitle' => $this->l('Keep visitors informed with a restrained popup.'),
                'content' => $this->l('Use this for delivery notes, holiday hours or store-wide updates.'),
                'cta' => $this->l('Learn more'),
            ],
        ];
    }

    private function getDefaultPopup($templateKey)
    {
        $templates = $this->getPresetTemplates();
        if (!isset($templates[$templateKey])) {
            $templateKey = 'announcement';
        }
        $preset = $templates[$templateKey];

        return [
            'id_popup' => 0,
            'active' => 0,
            'internal_name' => $preset['label'],
            'popup_type' => $preset['type'],
            'campaign_goal' => $preset['goal'],
            'template_key' => $templateKey,
            'layout' => $preset['layout'],
            'image_position' => 'top',
            'trigger_type' => $preset['trigger'],
            'trigger_value' => $preset['trigger_value'],
            'frequency_days' => 7,
            'width' => 560,
            'bg_color' => '#ffffff',
            'text_color' => '#1f2937',
            'subtitle_color' => '#4b5563',
            'accent_color' => '#25b9d7',
            'button_color' => '#111827',
            'button_text_color' => '#ffffff',
            'bg_image' => '',
            'border_radius' => 8,
            'overlay_opacity' => 0.55,
            'animation' => 'fadeIn',
            'close_button_style' => 'circle',
            'close_on_overlay' => 1,
            'mobile_behavior' => 'bottom_sheet',
            'priority' => 0,
            'ab_test_enabled' => 0,
            'winner_metric' => 'conversion_rate',
            'date_start' => '',
            'date_end' => '',
        ];
    }

    private function getDefaultLangValues($templateKey)
    {
        $templates = $this->getPresetTemplates();
        $preset = isset($templates[$templateKey]) ? $templates[$templateKey] : $templates['announcement'];
        $values = [];

        foreach (Language::getLanguages(false) as $lang) {
            $idLang = (int) $lang['id_lang'];
            $values[$idLang] = [
                'title' => $preset['title'],
                'subtitle' => $preset['subtitle'],
                'content' => $preset['content'],
                'cta_text' => $preset['cta'],
                'cta_url' => '',
                'coupon_code' => isset($preset['coupon']) ? $preset['coupon'] : '',
                'consent_text' => $this->l('I agree to receive campaign emails.'),
                'success_message' => $this->l('Thank you. You are subscribed.'),
            ];
        }

        return $values;
    }

    private function getDefaultTargetingValues($templateKey)
    {
        $pageTypes = ['home', 'category', 'product', 'cart', 'checkout', 'search', 'cms', 'other'];
        if ($templateKey === 'cart_reminder') {
            $pageTypes = ['cart', 'checkout'];
        } elseif ($templateKey === 'product_upsell') {
            $pageTypes = ['product'];
        }

        return [
            'page_types' => $pageTypes,
            'devices' => ['desktop', 'tablet', 'mobile'],
            'groups' => [],
            'languages' => [],
            'currencies' => [],
            'login_state' => '',
            'url_contains' => '',
            'min_cart_total' => '',
            'cart_products' => '',
            'cart_categories' => '',
        ];
    }

    private function getDefaultVariants($templateKey)
    {
        $langValues = $this->getDefaultLangValues($templateKey);

        return [
            'a' => [
                'id_variant' => 0,
                'name' => 'Variant A',
                'active' => 1,
                'traffic_percentage' => 100,
                'lang' => $langValues,
            ],
            'b' => [
                'id_variant' => 0,
                'name' => 'Variant B',
                'active' => 0,
                'traffic_percentage' => 0,
                'lang' => $langValues,
            ],
        ];
    }

    private function processImageUpload($existingPath)
    {
        if (empty($_FILES['bg_image']['name']) || empty($_FILES['bg_image']['tmp_name'])) {
            return $existingPath;
        }

        $extension = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $this->errors[] = $this->l('Invalid image format.');
            return $existingPath;
        }

        if (!is_uploaded_file($_FILES['bg_image']['tmp_name'])
            || !@getimagesize($_FILES['bg_image']['tmp_name'])
        ) {
            $this->errors[] = $this->l('The uploaded file is not a valid image.');
            return $existingPath;
        }

        if ((int) $_FILES['bg_image']['size'] > 4 * 1024 * 1024) {
            $this->errors[] = $this->l('Image is too large (4 MB maximum).');
            return $existingPath;
        }

        $uploadDir = _PS_MODULE_DIR_ . 'ps_advanced_popup/views/img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'popup_' . date('YmdHis') . '_' . Tools::passwdGen(8) . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['bg_image']['tmp_name'], $filePath)) {
            $this->errors[] = $this->l('Image upload failed.');
            return $existingPath;
        }

        return 'modules/ps_advanced_popup/views/img/' . $fileName;
    }

    private function addArrayRule(&$rules, $type, $operator, $values)
    {
        $values = array_values(array_filter((array) $values, function ($value) {
            return $value !== '' && $value !== null && $value !== false && $value !== 0 && $value !== 'all';
        }));

        if (!empty($values)) {
            $rules[] = [
                'target_type' => $type,
                'operator' => $operator,
                'target_value' => json_encode($values),
            ];
        }
    }

    private function addCsvRule(&$rules, $type, $csvValue)
    {
        $ids = array_filter(array_map('intval', preg_split('/\s*,\s*/', (string) $csvValue)));
        if (!empty($ids)) {
            $rules[] = [
                'target_type' => $type,
                'operator' => 'in',
                'target_value' => json_encode(array_values($ids)),
            ];
        }
    }

    /**
     * Escape a raw DB row before re-inserting it with Db::insert (which does not escape).
     *
     * @param array $row
     * @param array $htmlFields fields allowed to keep HTML
     *
     * @return array
     */
    private function escapeRow(array $row, array $htmlFields = [])
    {
        foreach ($row as $key => $value) {
            if ($value === null || is_int($value) || is_float($value)) {
                continue;
            }
            $row[$key] = pSQL((string) $value, in_array($key, $htmlFields, true));
        }

        return $row;
    }

    private function sanitizeChoice($value, $allowed, $default)
    {
        return in_array($value, $allowed) ? $value : $default;
    }

    private function sanitizeColor($value, $default)
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $value) ? $value : $default;
    }

    private function normalizeDateTime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if (!$timestamp) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
