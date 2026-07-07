<?php
/**
 * AJAX controller for Advanced Popup Studio.
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

        $action = Tools::getValue('action');
        switch ($action) {
            case 'newsletter':
            case 'newsletter_subscribe':
                $this->processNewsletter();
                break;

            case 'track':
            case 'track_event':
                $this->processTrackEvent();
                break;

            case 'coupon_copy':
                $this->processCouponCopy();
                break;

            case 'cta_click':
                $this->processCtaClick();
                break;

            case 'preview_render':
                $this->processPreviewRender();
                break;

            default:
                $this->ajaxResponse(['success' => false, 'message' => 'Invalid action']);
        }
    }

    private function processNewsletter()
    {
        $email = trim(Tools::getValue('email'));
        $idPopup = (int) Tools::getValue('id_popup');
        $idVariant = (int) Tools::getValue('id_variant');
        $context = $this->getEventContext();

        SmartPopup::trackEvent($idPopup, 'newsletter_submit', $idVariant, $context);

        if (!$idPopup || !Validate::isEmail($email)) {
            SmartPopup::trackEvent($idPopup, 'newsletter_error', $idVariant, $context, ['reason' => 'invalid_email']);
            $this->ajaxResponse([
                'success' => false,
                'message' => $this->module->l('Please enter a valid email address', 'ajax'),
            ]);
            return;
        }

        if (Module::isInstalled('ps_emailsubscription') && Module::isEnabled('ps_emailsubscription')) {
            $result = $this->subscribeWithModule($email);
        } else {
            $result = $this->subscribeDirectly($email, $idPopup, $idVariant);
        }

        if ($result['success']) {
            $this->recordSubscriber($email, $idPopup, $idVariant);
            SmartPopup::trackEvent($idPopup, 'newsletter_success', $idVariant, $context);

            $successMessage = $this->getPopupSuccessMessage($idPopup);
            if ($successMessage !== '') {
                $result['message'] = $successMessage;
            }
        } else {
            SmartPopup::trackEvent($idPopup, 'newsletter_error', $idVariant, $context, ['reason' => 'subscribe_failed']);
        }

        $this->ajaxResponse($result);
    }

    private function getPopupSuccessMessage($idPopup)
    {
        $message = Db::getInstance()->getValue(
            'SELECT `success_message` FROM `' . _DB_PREFIX_ . 'smart_popup_lang`
             WHERE `id_popup` = ' . (int) $idPopup . '
             AND `id_lang` = ' . (int) $this->context->language->id
        );

        return trim((string) $message);
    }

    private function subscribeWithModule($email)
    {
        $idShop = (int) $this->context->shop->id;

        // Existing customer: toggle the newsletter flag instead of using the guest table.
        $customer = Db::getInstance()->getRow(
            'SELECT `id_customer`, `newsletter` FROM `' . _DB_PREFIX_ . 'customer`
             WHERE `email` = "' . pSQL($email) . '"
             AND `id_shop` = ' . (int) $idShop . '
             AND `deleted` = 0'
        );

        if ($customer) {
            if ((int) $customer['newsletter']) {
                return [
                    'success' => false,
                    'message' => $this->module->l('This email is already subscribed', 'ajax'),
                ];
            }

            $updated = Db::getInstance()->update('customer', [
                'newsletter' => 1,
                'newsletter_date_add' => date('Y-m-d H:i:s'),
                'ip_registration_newsletter' => pSQL(Tools::getRemoteAddr()),
            ], 'id_customer = ' . (int) $customer['id_customer']);

            return [
                'success' => (bool) $updated,
                'message' => $updated
                    ? $this->module->l('Thank you for subscribing!', 'ajax')
                    : $this->module->l('An error occurred. Please try again.', 'ajax'),
            ];
        }

        $exists = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'emailsubscription`
             WHERE `email` = "' . pSQL($email) . '"
             AND `id_shop` = ' . (int) $idShop
        );

        if ($exists) {
            return [
                'success' => false,
                'message' => $this->module->l('This email is already subscribed', 'ajax'),
            ];
        }

        $result = Db::getInstance()->insert('emailsubscription', [
            'id_shop' => $idShop,
            'id_shop_group' => (int) $this->context->shop->id_shop_group,
            'email' => pSQL($email),
            'newsletter_date_add' => date('Y-m-d H:i:s'),
            'ip_registration_newsletter' => pSQL(Tools::getRemoteAddr()),
            'http_referer' => pSQL($this->getReferer()),
            'active' => 1,
        ]);

        return [
            'success' => (bool) $result,
            'message' => $result
                ? $this->module->l('Thank you for subscribing!', 'ajax')
                : $this->module->l('An error occurred. Please try again.', 'ajax'),
        ];
    }

    private function subscribeDirectly($email, $idPopup, $idVariant)
    {
        $exists = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'smart_popup_subscriber`
             WHERE `email` = "' . pSQL($email) . '"
             AND `id_popup` = ' . (int) $idPopup . '
             AND `id_shop` = ' . (int) $this->context->shop->id
        );

        if ($exists) {
            return [
                'success' => false,
                'message' => $this->module->l('This email is already subscribed', 'ajax'),
            ];
        }

        $result = $this->recordSubscriber($email, $idPopup, $idVariant);

        return [
            'success' => (bool) $result,
            'message' => $result
                ? $this->module->l('Thank you for subscribing!', 'ajax')
                : $this->module->l('An error occurred. Please try again.', 'ajax'),
        ];
    }

    private function recordSubscriber($email, $idPopup, $idVariant)
    {
        $exists = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'smart_popup_subscriber`
             WHERE `email` = "' . pSQL($email) . '"
             AND `id_popup` = ' . (int) $idPopup . '
             AND `id_shop` = ' . (int) $this->context->shop->id
        );

        if ($exists) {
            return true;
        }

        return Db::getInstance()->insert('smart_popup_subscriber', [
            'id_popup' => (int) $idPopup,
            'id_variant' => (int) $idVariant,
            'id_shop' => (int) $this->context->shop->id,
            'email' => pSQL($email),
            'consent' => (int) Tools::getValue('consent'),
            'source' => 'popup',
            'date_add' => date('Y-m-d H:i:s'),
        ]);
    }

    private function processTrackEvent()
    {
        $idPopup = (int) Tools::getValue('id_popup');
        $idVariant = (int) Tools::getValue('id_variant');
        $eventType = Tools::getValue('event_type', Tools::getValue('stat_type'));
        $extra = json_decode(Tools::getValue('extra'), true);

        $tracked = SmartPopup::trackEvent(
            $idPopup,
            $eventType,
            $idVariant,
            $this->getEventContext(),
            is_array($extra) ? $extra : []
        );

        $this->ajaxResponse(['success' => (bool) $tracked]);
    }

    private function processCouponCopy()
    {
        $idPopup = (int) Tools::getValue('id_popup');
        $idVariant = (int) Tools::getValue('id_variant');
        $couponCode = Tools::substr(trim((string) Tools::getValue('coupon_code')), 0, 80);
        $context = $this->getEventContext();

        SmartPopup::recordCouponEvent(
            $idPopup,
            $idVariant,
            $couponCode,
            'copy',
            isset($context['session_key']) ? $context['session_key'] : ''
        );
        $tracked = SmartPopup::trackEvent($idPopup, 'coupon_copy', $idVariant, $context, ['coupon_code' => $couponCode]);

        $this->ajaxResponse([
            'success' => (bool) $tracked,
            'message' => $this->module->l('Coupon code copied.', 'ajax'),
        ]);
    }

    private function processCtaClick()
    {
        $tracked = SmartPopup::trackEvent(
            (int) Tools::getValue('id_popup'),
            'cta_click',
            (int) Tools::getValue('id_variant'),
            $this->getEventContext()
        );

        $this->ajaxResponse(['success' => (bool) $tracked]);
    }

    private function processPreviewRender()
    {
        $this->ajaxResponse([
            'success' => true,
            'html' => '<div class="smart-popup-preview-fragment">' . Tools::htmlentitiesUTF8(Tools::getValue('title')) . '</div>',
        ]);
    }

    private function getEventContext()
    {
        $device = Tools::getValue('device', 'desktop');
        if (!in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
            $device = 'desktop';
        }

        $pageType = Tools::getValue('page_type', 'other');
        $allowedPageTypes = ['home', 'category', 'product', 'cart', 'checkout', 'search', 'brand', 'cms', 'other'];
        if (!in_array($pageType, $allowedPageTypes, true)) {
            $pageType = 'other';
        }

        $sessionKey = (string) Tools::getValue('session_key');
        if (!preg_match('/^[a-f0-9]{40}$/i', $sessionKey)) {
            $sessionKey = '';
        }

        return [
            'id_shop' => (int) $this->context->shop->id,
            'id_lang' => (int) $this->context->language->id,
            'device' => $device,
            'page_type' => $pageType,
            'url' => Tools::substr((string) Tools::getValue('url', $this->getReferer()), 0, 2048),
            'session_key' => $sessionKey,
        ];
    }

    private function getReferer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }

    private function ajaxResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
