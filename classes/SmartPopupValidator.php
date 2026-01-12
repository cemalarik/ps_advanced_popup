<?php
/**
 * SmartPopup Validator Class
 * Security and validation helpers
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SmartPopupValidator
{
    /**
     * Validate popup data before save
     */
    public static function validatePopupData($data)
    {
        $errors = [];

        // Title validation
        if (isset($data['title'])) {
            foreach ($data['title'] as $idLang => $title) {
                if (!empty($title) && !Validate::isGenericName($title)) {
                    $errors[] = 'Invalid title for language ' . $idLang;
                }
            }
        }

        // Content validation (HTML)
        if (isset($data['content'])) {
            foreach ($data['content'] as $idLang => $content) {
                if (!empty($content) && !Validate::isCleanHtml($content)) {
                    $errors[] = 'Invalid HTML content for language ' . $idLang;
                }
            }
        }

        // URL validation
        if (isset($data['cta_url'])) {
            foreach ($data['cta_url'] as $idLang => $url) {
                if (!empty($url) && !Validate::isUrl($url)) {
                    $errors[] = 'Invalid URL for language ' . $idLang;
                }
            }
        }

        // Numeric validations
        $numericFields = ['width', 'border_radius', 'trigger_value', 'frequency_days', 'priority'];
        foreach ($numericFields as $field) {
            if (isset($data[$field]) && !Validate::isUnsignedInt($data[$field])) {
                $errors[] = 'Invalid value for ' . $field;
            }
        }

        // Float validation
        if (isset($data['overlay_opacity'])) {
            $opacity = (float) $data['overlay_opacity'];
            if ($opacity < 0 || $opacity > 1) {
                $errors[] = 'Overlay opacity must be between 0 and 1';
            }
        }

        // Color validation
        if (isset($data['bg_color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['bg_color'])) {
            $errors[] = 'Invalid background color format';
        }

        // Enum validations
        $validPopupTypes = ['image', 'html', 'newsletter'];
        if (isset($data['popup_type']) && !in_array($data['popup_type'], $validPopupTypes)) {
            $errors[] = 'Invalid popup type';
        }

        $validTriggerTypes = ['load', 'exit', 'scroll', 'inactivity'];
        if (isset($data['trigger_type']) && !in_array($data['trigger_type'], $validTriggerTypes)) {
            $errors[] = 'Invalid trigger type';
        }

        return $errors;
    }

    /**
     * Sanitize popup data
     */
    public static function sanitizePopupData(&$data)
    {
        // Sanitize strings
        if (isset($data['title'])) {
            foreach ($data['title'] as &$title) {
                $title = Tools::htmlentitiesUTF8($title);
            }
        }

        // Sanitize HTML content
        if (isset($data['content'])) {
            foreach ($data['content'] as &$content) {
                $content = Tools::purifyHTML($content);
            }
        }

        // Sanitize URLs
        if (isset($data['cta_url'])) {
            foreach ($data['cta_url'] as &$url) {
                $url = filter_var($url, FILTER_SANITIZE_URL);
            }
        }

        // Sanitize numeric values
        $numericFields = ['width', 'border_radius', 'trigger_value', 'frequency_days', 'priority'];
        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = abs((int) $data[$field]);
            }
        }

        // Sanitize float
        if (isset($data['overlay_opacity'])) {
            $data['overlay_opacity'] = max(0, min(1, (float) $data['overlay_opacity']));
        }

        return $data;
    }

    /**
     * Validate email for newsletter
     */
    public static function validateEmail($email)
    {
        if (empty($email)) {
            return false;
        }

        return Validate::isEmail($email);
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail($email)
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
}
