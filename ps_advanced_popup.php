<?php
/**
 * Advanced Popup Studio for PrestaShop.
 *
 * @author    Advanced Popup Studio
 * @copyright 2026
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/SmartPopup.php';

class Ps_advanced_popup extends Module
{
    public function __construct()
    {
        $this->name = 'ps_advanced_popup';
        $this->tab = 'front_office_features';
        $this->version = '2.0.1';
        $this->author = 'Advanced Popup Studio';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Advanced Popup Studio');
        $this->description = $this->l('Create premium, targeted popups with templates, A/B variants and anonymous analytics.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Advanced Popup Studio? All popup data will be removed.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayFooter')
            && $this->registerHook('actionNewsletterRegistrationAfter')
            && $this->installDb()
            && $this->installTab();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDb()
            && $this->uninstallTab();
    }

    public function installDb()
    {
        $sqlFile = dirname(__FILE__) . '/sql/install.sql';
        if (!file_exists($sqlFile)) {
            return false;
        }

        $sql = file_get_contents($sqlFile);
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);

        foreach (preg_split('/;\s*[\r\n]+/', $sql) as $query) {
            $query = trim($query);
            if ($query !== '' && !Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function uninstallDb()
    {
        $sqlFile = dirname(__FILE__) . '/sql/uninstall.sql';
        if (!file_exists($sqlFile)) {
            return true;
        }

        $sql = str_replace('PREFIX_', _DB_PREFIX_, file_get_contents($sqlFile));
        foreach (preg_split('/;\s*[\r\n]+/', $sql) as $query) {
            $query = trim($query);
            if ($query !== '') {
                Db::getInstance()->execute($query);
            }
        }

        return true;
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminSmartPopup';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Popup Studio';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        $tab->module = $this->name;

        return $tab->add();
    }

    private function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminSmartPopup');
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }

        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminSmartPopup'));
    }

    public function hookDisplayHeader($params)
    {
        $popups = $this->getActivePopupsForCurrentPage();
        if (empty($popups)) {
            return '';
        }

        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');

        Media::addJsDef([
            'smartPopupData' => [
                'popups' => $popups,
                'ajaxUrl' => $this->context->link->getModuleLink($this->name, 'ajax'),
                'pageType' => $this->getCurrentPageType(),
                'currentUrl' => $this->getCurrentUrl(),
                'customerId' => (int) $this->context->customer->id,
                'customerGroups' => $this->getCustomerGroups(),
                'isLogged' => (bool) $this->context->customer->isLogged(),
                'device' => $this->getDeviceType(),
                'languageIso' => $this->context->language->iso_code,
                'currencyIso' => $this->context->currency ? $this->context->currency->iso_code : '',
                'cart' => $this->getCartSnapshot(),
                'sessionKey' => $this->getVisitorSessionKey(),
                'labels' => [
                    'copied' => $this->l('Copied'),
                    'copyFailed' => $this->l('Copy failed. Please copy the code manually.'),
                    'connectionError' => $this->l('Connection error. Please try again.'),
                ],
            ],
        ]);

        return '';
    }

    public function hookDisplayFooter($params)
    {
        $popups = $this->getActivePopupsForCurrentPage();
        if (empty($popups)) {
            return '';
        }

        $this->context->smarty->assign([
            'popups' => $popups,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/popup.tpl');
    }

    public function hookActionNewsletterRegistrationAfter($params)
    {
        return '';
    }

    private function getActivePopupsForCurrentPage()
    {
        $cacheKey = $this->getCacheKey();
        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }

        $popups = SmartPopup::getActivePopupsWithTargeting(
            (int) $this->context->language->id,
            (int) $this->context->shop->id
        );
        $this->preparePopupAssetUrls($popups);

        Cache::store($cacheKey, $popups);

        return $popups;
    }

    private function preparePopupAssetUrls(&$popups)
    {
        foreach ($popups as &$popup) {
            $popup['bg_image_url'] = $this->getPublicAssetUrl(isset($popup['bg_image']) ? $popup['bg_image'] : '');
        }
        unset($popup);
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

    private function getCacheKey()
    {
        return 'smart_popup_runtime_' . (int) $this->context->shop->id . '_' . (int) $this->context->language->id;
    }

    public static function clearCache()
    {
        $shops = Shop::getShops(true, null, true);
        $languages = Language::getLanguages(true);

        foreach ($shops as $idShop) {
            foreach ($languages as $lang) {
                Cache::clean('smart_popup_runtime_' . (int) $idShop . '_' . (int) $lang['id_lang']);
                Cache::clean('smart_popup_active_' . (int) $idShop . '_' . (int) $lang['id_lang']);
            }
        }
    }

    private function getCurrentPageType()
    {
        $controller = Tools::getValue('controller');
        $pageTypes = [
            'index' => 'home',
            'category' => 'category',
            'product' => 'product',
            'cart' => 'cart',
            'order' => 'checkout',
            'order-opc' => 'checkout',
            'search' => 'search',
            'manufacturer' => 'brand',
            'cms' => 'cms',
        ];

        return isset($pageTypes[$controller]) ? $pageTypes[$controller] : 'other';
    }

    private function getCustomerGroups()
    {
        if ($this->context->customer->isLogged()) {
            return array_map('intval', $this->context->customer->getGroups());
        }

        return [(int) Configuration::get('PS_UNIDENTIFIED_GROUP')];
    }

    private function getDeviceType()
    {
        $context = Context::getContext();
        if (method_exists($context, 'getMobileDevice') && $context->getMobileDevice()) {
            return 'mobile';
        }

        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        if (preg_match('/mobile|android|iphone/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function getCartSnapshot()
    {
        $snapshot = [
            'total' => 0,
            'productIds' => [],
            'categoryIds' => [],
        ];

        if (!$this->context->cart || !(int) $this->context->cart->id) {
            return $snapshot;
        }

        $snapshot['total'] = (float) $this->context->cart->getOrderTotal(false, Cart::BOTH_WITHOUT_SHIPPING);
        foreach ($this->context->cart->getProducts() as $product) {
            $idProduct = (int) $product['id_product'];
            $snapshot['productIds'][] = $idProduct;
            if (!empty($product['id_category_default'])) {
                $snapshot['categoryIds'][] = (int) $product['id_category_default'];
            }
        }

        $snapshot['productIds'] = array_values(array_unique($snapshot['productIds']));
        $snapshot['categoryIds'] = array_values(array_unique($snapshot['categoryIds']));

        return $snapshot;
    }

    private function getCurrentUrl()
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        return $scheme . $host . $uri;
    }

    private function getVisitorSessionKey()
    {
        if (empty($this->context->cookie->smart_popup_session_key)) {
            $this->context->cookie->smart_popup_session_key = sha1(uniqid('smart_popup_', true));
        }

        return $this->context->cookie->smart_popup_session_key;
    }
}
