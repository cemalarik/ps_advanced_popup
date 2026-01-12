<?php
/**
 * Advanced Smart Popup Module for PrestaShop
 *
 * @author    Your Name
 * @copyright 2024
 * @license   MIT
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
        $this->version = '1.0.0';
        $this->author = 'Your Name';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Advanced Smart Popup');
        $this->description = $this->l('Create targeted popups with smart triggers for your PrestaShop store.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    /**
     * Module installation
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayFooter')
            && $this->registerHook('actionNewsletterRegistrationAfter')
            && $this->installDb()
            && $this->installTab();
    }

    /**
     * Module uninstallation
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDb()
            && $this->uninstallTab();
    }

    /**
     * Install database tables
     */
    private function installDb()
    {
        $sqlFile = dirname(__FILE__) . '/sql/install.sql';
        if (!file_exists($sqlFile)) {
            return false;
        }

        $sql = file_get_contents($sqlFile);
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);

        $queries = preg_split('/;\s*[\r\n]+/', $sql);
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if (!Db::getInstance()->execute($query)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Uninstall database tables
     */
    private function uninstallDb()
    {
        $sqlFile = dirname(__FILE__) . '/sql/uninstall.sql';
        if (!file_exists($sqlFile)) {
            return true;
        }

        $sql = file_get_contents($sqlFile);
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);

        $queries = preg_split('/;\s*[\r\n]+/', $sql);
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                Db::getInstance()->execute($query);
            }
        }

        return true;
    }

    /**
     * Install admin tab
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminSmartPopup';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Smart Popup';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * Uninstall admin tab
     */
    private function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminSmartPopup');
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }
        return true;
    }

    /**
     * Redirect to admin controller
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminSmartPopup'));
    }

    /**
     * Hook: displayHeader - Load CSS and JS
     */
    public function hookDisplayHeader($params)
    {
        $popups = $this->getActivePopupsForCurrentPage();

        if (empty($popups)) {
            return '';
        }

        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        $this->context->controller->addCSS($this->_path . 'views/css/animate.min.css');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');

        // JavaScript değişkenlerini header'da tanımla
        Media::addJsDef([
            'smartPopupData' => [
                'popups' => $popups,
                'ajaxUrl' => $this->context->link->getModuleLink($this->name, 'ajax'),
                'currentPage' => $this->getCurrentPageType(),
                'customerId' => (int) $this->context->customer->id,
                'customerGroups' => $this->getCustomerGroups(),
                'device' => $this->getDeviceType(),
            ],
        ]);

        return '';
    }

    /**
     * Hook: displayFooter - Render popup HTML
     */
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

    /**
     * Hook: actionNewsletterRegistrationAfter
     */
    public function hookActionNewsletterRegistrationAfter($params)
    {
        // Can be used for additional newsletter integration
        return '';
    }

    /**
     * Get active popups for current page
     */
    private function getActivePopupsForCurrentPage()
    {
        $cacheKey = $this->getCacheKey();

        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }

        $idLang = (int) $this->context->language->id;
        $popups = SmartPopup::getActivePopupsWithTargeting($idLang);

        Cache::store($cacheKey, $popups);

        return $popups;
    }

    /**
     * Get cache key
     */
    private function getCacheKey()
    {
        return 'smart_popup_active_' . (int) $this->context->shop->id . '_' . (int) $this->context->language->id;
    }

    /**
     * Clear popup cache
     */
    public static function clearCache()
    {
        $shops = Shop::getShops(true, null, true);
        $languages = Language::getLanguages(true);

        foreach ($shops as $idShop) {
            foreach ($languages as $lang) {
                $cacheKey = 'smart_popup_active_' . (int) $idShop . '_' . (int) $lang['id_lang'];
                Cache::clean($cacheKey);
            }
        }
    }

    /**
     * Get current page type
     */
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
        ];

        return isset($pageTypes[$controller]) ? $pageTypes[$controller] : 'other';
    }

    /**
     * Get customer groups
     */
    private function getCustomerGroups()
    {
        if ($this->context->customer->isLogged()) {
            return $this->context->customer->getGroups();
        }
        return [(int) Configuration::get('PS_UNIDENTIFIED_GROUP')];
    }

    /**
     * Get device type
     */
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
}
