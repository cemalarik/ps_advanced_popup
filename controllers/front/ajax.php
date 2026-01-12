<?php
/**
 * AJAX Controller for Smart Popup
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ps_advanced_popup/classes/SmartPopup.php';

class Ps_advanced_popupAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Verify AJAX request
        if (!$this->isXmlHttpRequest()) {
            $this->ajaxResponse(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $action = Tools::getValue('action');

        switch ($action) {
            case 'newsletter':
                $this->processNewsletter();
                break;

            case 'track':
                $this->processTracking();
                break;

            default:
                $this->ajaxResponse(['success' => false, 'message' => 'Invalid action']);
        }
    }

    /**
     * Process newsletter subscription
     */
    private function processNewsletter()
    {
        $email = Tools::getValue('email');
        $idPopup = (int) Tools::getValue('id_popup');

        // Validate email
        if (!Validate::isEmail($email)) {
            $this->ajaxResponse([
                'success' => false,
                'message' => $this->module->l('Please enter a valid email address', 'ajax'),
            ]);
            return;
        }

        // Check if ps_emailsubscription module exists
        if (Module::isInstalled('ps_emailsubscription') && Module::isEnabled('ps_emailsubscription')) {
            $result = $this->subscribeWithModule($email);
        } else {
            $result = $this->subscribeDirectly($email);
        }

        if ($result['success']) {
            SmartPopup::incrementStat($idPopup, 'conversion');
        }

        $this->ajaxResponse($result);
    }

    /**
     * Subscribe using ps_emailsubscription module
     */
    private function subscribeWithModule($email)
    {
        $idShop = (int) $this->context->shop->id;

        // Check if already subscribed
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'emailsubscription`
                WHERE `email` = "' . pSQL($email) . '"
                AND `id_shop` = ' . $idShop;

        if (Db::getInstance()->getRow($sql)) {
            return [
                'success' => false,
                'message' => $this->module->l('This email is already subscribed', 'ajax'),
            ];
        }

        // Insert subscription
        $result = Db::getInstance()->insert('emailsubscription', [
            'id_shop' => $idShop,
            'id_shop_group' => (int) $this->context->shop->id_shop_group,
            'email' => pSQL($email),
            'newsletter_date_add' => date('Y-m-d H:i:s'),
            'ip_registration_newsletter' => pSQL(Tools::getRemoteAddr()),
            'http_referer' => pSQL(Tools::getHttpReferer()),
            'active' => 1,
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => $this->module->l('Thank you for subscribing!', 'ajax'),
            ];
        }

        return [
            'success' => false,
            'message' => $this->module->l('An error occurred. Please try again.', 'ajax'),
        ];
    }

    /**
     * Direct subscription (fallback)
     */
    private function subscribeDirectly($email)
    {
        // Create simple newsletter table if not exists
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'smart_popup_subscribers` (
            `id_subscriber` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(255) NOT NULL,
            `date_add` DATETIME NOT NULL,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`id_subscriber`),
            UNIQUE KEY `email_shop` (`email`, `id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        Db::getInstance()->execute($sql);

        // Check if exists
        $exists = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'smart_popup_subscribers`
             WHERE `email` = "' . pSQL($email) . '"
             AND `id_shop` = ' . (int) $this->context->shop->id
        );

        if ($exists) {
            return [
                'success' => false,
                'message' => $this->module->l('This email is already subscribed', 'ajax'),
            ];
        }

        $result = Db::getInstance()->insert('smart_popup_subscribers', [
            'email' => pSQL($email),
            'date_add' => date('Y-m-d H:i:s'),
            'id_shop' => (int) $this->context->shop->id,
        ]);

        return [
            'success' => $result,
            'message' => $result
                ? $this->module->l('Thank you for subscribing!', 'ajax')
                : $this->module->l('An error occurred. Please try again.', 'ajax'),
        ];
    }

    /**
     * Process stat tracking
     */
    private function processTracking()
    {
        $idPopup = (int) Tools::getValue('id_popup');
        $statType = Tools::getValue('stat_type');

        if (!$idPopup || !in_array($statType, ['impression', 'conversion'])) {
            $this->ajaxResponse(['success' => false]);
            return;
        }

        SmartPopup::incrementStat($idPopup, $statType);

        $this->ajaxResponse(['success' => true]);
    }

    /**
     * Check if request is AJAX
     */
    private function isXmlHttpRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Send JSON response
     */
    private function ajaxResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
