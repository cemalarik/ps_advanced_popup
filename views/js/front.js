(function () {
    'use strict';

    var config = window.smartPopupData || {};
    var popups = config.popups || [];
    var shownPopups = [];
    var activeOverlay = null;
    var lastFocusedElement = null;

    var Cookie = {
        set: function (name, value, days) {
            var expires = '';
            if (days > 0) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + encodeURIComponent(value || '') + expires + '; path=/; SameSite=Lax';
        },
        get: function (name) {
            var nameEQ = name + '=';
            var parts = document.cookie.split(';');
            for (var i = 0; i < parts.length; i++) {
                var part = parts[i];
                while (part.charAt(0) === ' ') {
                    part = part.substring(1);
                }
                if (part.indexOf(nameEQ) === 0) {
                    return decodeURIComponent(part.substring(nameEQ.length));
                }
            }
            return null;
        }
    };

    function post(action, data, callback) {
        if (!config.ajaxUrl) {
            return;
        }

        var payload = 'action=' + encodeURIComponent(action);
        Object.keys(data || {}).forEach(function (key) {
            payload += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(data[key] == null ? '' : data[key]);
        });

        // Fire-and-forget events must survive page navigation (e.g. CTA clicks).
        if (!callback && navigator.sendBeacon) {
            try {
                var blob = new Blob([payload], { type: 'application/x-www-form-urlencoded' });
                if (navigator.sendBeacon(config.ajaxUrl, blob)) {
                    return;
                }
            } catch (e) {
                // Fall through to XHR.
            }
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', config.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            if (callback) {
                var response = {};
                try {
                    response = JSON.parse(xhr.responseText || '{}');
                } catch (e) {
                    response = { success: false };
                }
                callback(response);
            }
        };
        xhr.onerror = function () {
            if (callback) {
                callback({ success: false, message: config.labels ? config.labels.connectionError : 'Connection error.' });
            }
        };
        xhr.send(payload);
    }

    function sendEvent(popupId, variantId, eventType, extra) {
        post('track_event', {
            id_popup: popupId,
            id_variant: variantId || 0,
            event_type: eventType,
            device: config.device || 'desktop',
            page_type: config.pageType || 'other',
            url: config.currentUrl || window.location.href,
            session_key: config.sessionKey || '',
            extra: extra ? JSON.stringify(extra) : ''
        });
    }

    function parseTargetValue(rule) {
        try {
            return JSON.parse(rule.target_value || '[]');
        } catch (e) {
            return rule.target_value || '';
        }
    }

    function arrayContainsAny(source, required) {
        source = source || [];
        required = required || [];
        for (var i = 0; i < required.length; i++) {
            if (source.indexOf(parseInt(required[i], 10)) !== -1 || source.indexOf(required[i]) !== -1) {
                return true;
            }
        }
        return false;
    }

    function rulePasses(rule) {
        var value = parseTargetValue(rule);
        var cart = config.cart || {};

        switch (rule.target_type) {
            case 'page_type':
                return value.indexOf(config.pageType) !== -1;
            case 'device':
                return value.indexOf(config.device) !== -1;
            case 'customer_group':
                return arrayContainsAny(config.customerGroups || [], value);
            case 'language':
                return value.indexOf(config.languageIso) !== -1;
            case 'currency':
                return value.indexOf(config.currencyIso) !== -1;
            case 'login_state':
                return value === 'logged' ? !!config.isLogged : !config.isLogged;
            case 'url':
                return String(config.currentUrl || window.location.href).indexOf(value) !== -1;
            case 'cart_total':
                return parseFloat(cart.total || 0) >= parseFloat(value || 0);
            case 'cart_product':
                return arrayContainsAny(cart.productIds || [], value);
            case 'cart_category':
                return arrayContainsAny(cart.categoryIds || [], value);
            default:
                return true;
        }
    }

    function checkTargeting(popup) {
        var rules = popup.targeting || [];
        for (var i = 0; i < rules.length; i++) {
            if (!rulePasses(rules[i])) {
                return false;
            }
        }
        return true;
    }

    function selectVariant(popup) {
        var variants = popup.variants || [];
        if (!variants.length) {
            return null;
        }

        var cookieName = 'smart_popup_variant_' + popup.id_popup;
        var assigned = Cookie.get(cookieName);
        for (var i = 0; i < variants.length; i++) {
            if (String(variants[i].id_variant) === String(assigned)) {
                return variants[i];
            }
        }

        if (parseInt(popup.ab_test_enabled, 10) !== 1 || variants.length === 1) {
            Cookie.set(cookieName, variants[0].id_variant, 30);
            return variants[0];
        }

        var roll = Math.floor(Math.random() * 100) + 1;
        var cursor = 0;
        var selected = variants[0];
        for (var j = 0; j < variants.length; j++) {
            cursor += parseInt(variants[j].traffic_percentage, 10) || 0;
            if (roll <= cursor) {
                selected = variants[j];
                break;
            }
        }

        Cookie.set(cookieName, selected.id_variant, 30);
        sendEvent(popup.id_popup, selected.id_variant, 'variant_assignment', { variant_key: selected.variant_key });

        return selected;
    }

    function getFocusable(container) {
        return Array.prototype.slice.call(container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter(function (element) {
            return element.offsetParent !== null || element.getClientRects().length > 0;
        });
    }

    function focusDialog(overlay, keepLastFocused) {
        var dialog = overlay.querySelector('.smart-popup-container');
        var focusable = getFocusable(dialog);
        if (!keepLastFocused) {
            lastFocusedElement = document.activeElement;
        }

        if (focusable.length) {
            focusable[0].focus();
        } else {
            dialog.focus();
        }
    }

    function trapFocus(event) {
        if (!activeOverlay || event.key !== 'Tab') {
            return;
        }

        var dialog = activeOverlay.querySelector('.smart-popup-container');
        var focusable = getFocusable(dialog);
        if (!focusable.length) {
            event.preventDefault();
            dialog.focus();
            return;
        }

        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function markVariant(overlay, variant) {
        var variantId = variant ? String(variant.id_variant) : '0';
        var blocks = overlay.querySelectorAll('.smart-popup-variant');
        for (var i = 0; i < blocks.length; i++) {
            var isSelected = blocks[i].getAttribute('data-variant-id') === variantId;
            blocks[i].hidden = !isSelected;
            if (isSelected) {
                var title = blocks[i].querySelector('.smart-popup-title');
                var dialog = overlay.querySelector('.smart-popup-container');
                if (title && dialog) {
                    dialog.setAttribute('aria-labelledby', title.id);
                }
            }
        }
        overlay.setAttribute('data-selected-variant', variantId);
    }

    function isSuppressed(popupId) {
        return Cookie.get('smart_popup_suppressed_' + popupId) !== null;
    }

    function suppress(popupId, days) {
        Cookie.set('smart_popup_suppressed_' + popupId, '1', days);
    }

    function showPopup(overlay, popup) {
        if (!overlay || shownPopups.indexOf(parseInt(popup.id_popup, 10)) !== -1) {
            return;
        }

        if (activeOverlay) {
            var activeId = activeOverlay.getAttribute('data-popup-id');
            var activePopup = null;
            for (var i = 0; i < popups.length; i++) {
                if (String(popups[i].id_popup) === String(activeId)) {
                    activePopup = popups[i];
                    break;
                }
            }
            if (activePopup && parseInt(activePopup.priority, 10) >= parseInt(popup.priority, 10)) {
                return;
            }
            closePopup(activeOverlay, false);
        }

        var variant = selectVariant(popup);
        markVariant(overlay, variant);
        shownPopups.push(parseInt(popup.id_popup, 10));
        overlay.style.display = 'flex';
        overlay.offsetHeight;
        overlay.classList.add('active');
        activeOverlay = overlay;
        document.body.style.overflow = 'hidden';
        focusDialog(overlay, false);
        window.setTimeout(function () {
            if (activeOverlay === overlay && !overlay.contains(document.activeElement)) {
                focusDialog(overlay, true);
            }
        }, 80);
        sendEvent(popup.id_popup, variant ? variant.id_variant : 0, 'impression');
    }

    function closePopup(overlay, trackClose) {
        if (!overlay) {
            return;
        }

        var popupId = overlay.getAttribute('data-popup-id');
        var variantId = overlay.getAttribute('data-selected-variant') || 0;
        var frequencyDays = parseInt(overlay.getAttribute('data-frequency-days'), 10);

        if (trackClose !== false) {
            sendEvent(popupId, variantId, 'close');
            suppress(popupId, isNaN(frequencyDays) ? 7 : frequencyDays);
        }

        overlay.classList.remove('active');
        setTimeout(function () {
            overlay.style.display = 'none';
        }, 240);

        activeOverlay = null;
        document.body.style.overflow = '';
        if (lastFocusedElement && lastFocusedElement.focus) {
            lastFocusedElement.focus();
        }
    }

    function setupTrigger(popup) {
        if (isSuppressed(popup.id_popup) || !checkTargeting(popup)) {
            return;
        }

        if (config.device === 'mobile' && popup.mobile_behavior === 'hidden') {
            return;
        }

        var overlay = document.querySelector('[data-popup-id="' + popup.id_popup + '"]');
        if (!overlay) {
            return;
        }

        switch (popup.trigger_type) {
            case 'exit':
                if (config.device === 'desktop') {
                    var exitTriggered = false;
                    document.addEventListener('mouseout', function (event) {
                        if (!exitTriggered && event.clientY < 10 && event.relatedTarget === null) {
                            exitTriggered = true;
                            showPopup(overlay, popup);
                        }
                    });
                }
                break;
            case 'scroll':
                var scrollTriggered = false;
                var scrollPercent = parseInt(popup.trigger_value, 10) || 50;
                window.addEventListener('scroll', function () {
                    if (scrollTriggered) {
                        return;
                    }
                    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    var docHeight = Math.max(document.documentElement.scrollHeight - window.innerHeight, 1);
                    if ((scrollTop / docHeight) * 100 >= scrollPercent) {
                        scrollTriggered = true;
                        showPopup(overlay, popup);
                    }
                }, { passive: true });
                break;
            case 'inactivity':
                var inactivityTriggered = false;
                var inactivityTime = (parseInt(popup.trigger_value, 10) || 30) * 1000;
                var timer;
                var reset = function () {
                    if (inactivityTriggered) {
                        return;
                    }
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        inactivityTriggered = true;
                        showPopup(overlay, popup);
                    }, inactivityTime);
                };
                ['mousemove', 'keydown', 'scroll', 'touchstart'].forEach(function (name) {
                    document.addEventListener(name, reset, { passive: true });
                });
                reset();
                break;
            case 'load':
            default:
                setTimeout(function () {
                    showPopup(overlay, popup);
                }, (parseInt(popup.trigger_value, 10) || 0) * 1000);
                break;
        }
    }

    function handleNewsletter(form) {
        var popupId = form.getAttribute('data-popup-id');
        var variantId = form.getAttribute('data-variant-id') || 0;
        var email = form.querySelector('input[name="email"]').value;
        var consent = form.querySelector('input[name="consent"]');
        var button = form.querySelector('button[type="submit"]');
        var message = form.parentNode.querySelector('.smart-popup-message');
        var original = button.textContent;

        button.disabled = true;
        button.textContent = '...';

        post('newsletter_subscribe', {
            id_popup: popupId,
            id_variant: variantId,
            email: email,
            consent: consent && consent.checked ? 1 : 0,
            device: config.device || 'desktop',
            page_type: config.pageType || 'other',
            url: config.currentUrl || window.location.href,
            session_key: config.sessionKey || ''
        }, function (response) {
            message.hidden = false;
            message.className = 'smart-popup-message ' + (response.success ? 'success' : 'error');
            message.textContent = response.message || '';
            if (response.success) {
                suppress(popupId, 365);
                form.style.display = 'none';
            } else {
                button.disabled = false;
                button.textContent = original;
            }
        });
    }

    function bindEvents() {
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && activeOverlay) {
                closePopup(activeOverlay, true);
            }
            trapFocus(event);
        });

        document.addEventListener('click', function (event) {
            if (event.target.classList && event.target.classList.contains('smart-popup-overlay')) {
                if (event.target.getAttribute('data-close-overlay') === '1') {
                    closePopup(event.target, true);
                }
            }

            var close = event.target.closest ? event.target.closest('.smart-popup-close') : null;
            if (close) {
                closePopup(close.closest('.smart-popup-overlay'), true);
            }

            var cta = event.target.closest ? event.target.closest('.smart-popup-cta') : null;
            if (cta) {
                var ctaOverlay = cta.closest('.smart-popup-overlay');
                var ctaFrequency = ctaOverlay ? parseInt(ctaOverlay.getAttribute('data-frequency-days'), 10) : 7;
                sendEvent(cta.getAttribute('data-popup-id'), cta.getAttribute('data-variant-id'), 'cta_click');
                suppress(cta.getAttribute('data-popup-id'), isNaN(ctaFrequency) ? 7 : ctaFrequency);
            }

            var coupon = event.target.closest ? event.target.closest('.smart-popup-copy-coupon') : null;
            if (coupon) {
                var overlay = coupon.closest('.smart-popup-overlay');
                var popupId = overlay.getAttribute('data-popup-id');
                var variantId = overlay.getAttribute('data-selected-variant') || 0;
                var code = coupon.getAttribute('data-coupon');
                var afterCopy = function (success) {
                    coupon.textContent = success && config.labels ? config.labels.copied : (config.labels ? config.labels.copyFailed : 'Copy failed');
                    post('coupon_copy', {
                        id_popup: popupId,
                        id_variant: variantId,
                        coupon_code: code,
                        device: config.device || 'desktop',
                        page_type: config.pageType || 'other',
                        url: config.currentUrl || window.location.href,
                        session_key: config.sessionKey || ''
                    });
                    suppress(popupId, 365);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(code).then(function () {
                        afterCopy(true);
                    }, function () {
                        afterCopy(false);
                    });
                } else {
                    var input = document.createElement('textarea');
                    input.value = code;
                    document.body.appendChild(input);
                    input.select();
                    afterCopy(document.execCommand('copy'));
                    document.body.removeChild(input);
                }
            }
        });

        var forms = document.querySelectorAll('.smart-popup-newsletter-form');
        for (var i = 0; i < forms.length; i++) {
            forms[i].addEventListener('submit', function (event) {
                event.preventDefault();
                handleNewsletter(event.currentTarget);
            });
        }
    }

    function init() {
        if (!popups.length) {
            return;
        }

        bindEvents();
        popups.forEach(setupTrigger);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
