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
        } else {
            SmartPopup::trackEvent($idPopup, 'newsletter_error', $idVariant, $context, ['reason' => 'subscribe_failed']);
        }

        $this->ajaxResponse($result);
    }

    private function subscribeWithModule($email)
    {
        $idShop = (int) $this->context->shop->id;
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
        $couponCode = Tools::getValue('coupon_code');
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
        return [
            'id_shop' => (int) $this->context->shop->id,
            'id_lang' => (int) $this->context->language->id,
            'device' => Tools::getValue('device', 'desktop'),
            'page_type' => Tools::getValue('page_type', 'other'),
            'url' => Tools::getValue('url', $this->getReferer()),
            'session_key' => Tools::getValue('session_key'),
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
