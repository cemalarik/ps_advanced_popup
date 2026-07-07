<?php
/**
 * Validation helpers for Advanced Popup Studio.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SmartPopupValidator
{
    public static function validatePopupData($data)
    {
        $errors = [];

        $validPopupTypes = ['newsletter', 'coupon', 'cta', 'image', 'announcement'];
        $validTriggers = ['load', 'exit', 'scroll', 'inactivity'];
        $validLayouts = ['centered', 'image_top', 'image_left', 'image_right', 'full_image', 'compact_coupon', 'newsletter'];
        $validMobile = ['bottom_sheet', 'modal', 'hidden'];

        if (isset($data['popup_type']) && !in_array($data['popup_type'], $validPopupTypes)) {
            $errors[] = 'Invalid popup type';
        }
        if (isset($data['trigger_type']) && !in_array($data['trigger_type'], $validTriggers)) {
            $errors[] = 'Invalid trigger type';
        }
        if (isset($data['layout']) && !in_array($data['layout'], $validLayouts)) {
            $errors[] = 'Invalid layout';
        }
        if (isset($data['mobile_behavior']) && !in_array($data['mobile_behavior'], $validMobile)) {
            $errors[] = 'Invalid mobile behavior';
        }

        foreach (['width', 'border_radius', 'trigger_value', 'frequency_days', 'priority'] as $field) {
            if (isset($data[$field]) && !Validate::isUnsignedInt($data[$field])) {
                $errors[] = 'Invalid value for ' . $field;
            }
        }

        foreach (['bg_color', 'text_color', 'accent_color', 'button_color', 'button_text_color'] as $field) {
            if (isset($data[$field]) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data[$field])) {
                $errors[] = 'Invalid color for ' . $field;
            }
        }

        if (isset($data['overlay_opacity'])) {
            $opacity = (float) $data['overlay_opacity'];
            if ($opacity < 0 || $opacity > 0.85) {
                $errors[] = 'Overlay opacity must be between 0 and 0.85';
            }
        }

        return $errors;
    }

    public static function sanitizePopupData(&$data)
    {
        foreach (['width', 'border_radius', 'trigger_value', 'frequency_days', 'priority'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = abs((int) $data[$field]);
            }
        }

        if (isset($data['overlay_opacity'])) {
            $data['overlay_opacity'] = max(0, min(0.85, (float) $data['overlay_opacity']));
        }

        foreach (['internal_name', 'campaign_goal', 'template_key', 'layout', 'mobile_behavior'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = Tools::htmlentitiesUTF8(trim($data[$field]));
            }
        }

        return $data;
    }

    public static function validateEmail($email)
    {
        return !empty($email) && Validate::isEmail($email);
    }

    public static function sanitizeEmail($email)
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
}
