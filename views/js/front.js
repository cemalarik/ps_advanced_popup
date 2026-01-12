/**
 * Smart Popup Front-End JavaScript
 * Handles triggers, cookie management, and interactions
 */

(function () {
    'use strict';

    // Configuration
    var config = window.smartPopupData || {};
    var popups = config.popups || [];
    var shownPopups = [];

    // Cookie helpers
    var Cookie = {
        set: function (name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + (value || '') + expires + '; path=/';
        },

        get: function (name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        },

        remove: function (name) {
            document.cookie = name + '=; Max-Age=-99999999; path=/';
        }
    };

    // Popup Manager
    var PopupManager = {

        init: function () {
            if (!popups.length) return;

            this.bindGlobalEvents();
            this.processPopups();
        },

        bindGlobalEvents: function () {
            var self = this;

            // ESC key to close
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    self.closeAllPopups();
                }
            });

            // Click on overlay to close
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('smart-popup-overlay')) {
                    self.closePopup(e.target);
                }
            });

            // Close button click
            document.querySelectorAll('.smart-popup-close').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var overlay = this.closest('.smart-popup-overlay');
                    self.closePopup(overlay);
                });
            });

            // Newsletter form submit
            document.querySelectorAll('.smart-popup-newsletter-form').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    self.handleNewsletterSubmit(this);
                });
            });
        },

        processPopups: function () {
            var self = this;

            popups.forEach(function (popup) {
                // Check if already shown (cookie)
                if (self.isPopupSuppressed(popup.id_popup)) {
                    return;
                }

                // Check targeting rules
                if (!self.checkTargeting(popup)) {
                    return;
                }

                // Check device visibility
                if (popup.hide_on_mobile == 1 && config.device === 'mobile') {
                    return;
                }

                // Setup trigger
                self.setupTrigger(popup);
            });
        },

        isPopupSuppressed: function (popupId) {
            return Cookie.get('smart_popup_' + popupId) !== null;
        },

        suppressPopup: function (popupId, days) {
            Cookie.set('smart_popup_' + popupId, '1', days);
        },

        checkTargeting: function (popup) {
            var targeting = popup.targeting || [];

            // No targeting rules = show everywhere
            if (!targeting.length) {
                return true;
            }

            var checks = {
                page: false,
                category: false,
                customer_group: false,
                device: false
            };

            var hasRule = {
                page: false,
                category: false,
                customer_group: false,
                device: false
            };

            targeting.forEach(function (rule) {
                var ids;
                try {
                    ids = JSON.parse(rule.target_ids || '[]');
                } catch (e) {
                    ids = [];
                }
                hasRule[rule.target_type] = true;

                switch (rule.target_type) {
                    case 'page':
                        if (ids.includes('all') || ids.includes(config.currentPage)) {
                            checks.page = true;
                        }
                        break;

                    case 'category':
                        var currentCategory = window.prestashop && window.prestashop.category
                            ? window.prestashop.category.id : null;
                        if (currentCategory && ids.includes(currentCategory)) {
                            checks.category = true;
                        }
                        break;

                    case 'customer_group':
                        var customerGroups = config.customerGroups || [];
                        if (ids.some(function (id) { return customerGroups.includes(parseInt(id)); })) {
                            checks.customer_group = true;
                        }
                        break;

                    case 'device':
                        if (ids.includes(config.device)) {
                            checks.device = true;
                        }
                        break;
                }
            });

            // All defined rules must pass
            for (var type in hasRule) {
                if (hasRule[type] && !checks[type]) {
                    return false;
                }
            }

            return true;
        },

        setupTrigger: function (popup) {
            var self = this;
            var overlay = document.querySelector('[data-popup-id="' + popup.id_popup + '"]');

            if (!overlay) return;

            switch (popup.trigger_type) {
                case 'load':
                    var delay = (parseInt(popup.trigger_value) || 0) * 1000;
                    setTimeout(function () {
                        self.showPopup(overlay, popup);
                    }, delay);
                    break;

                case 'exit':
                    if (config.device === 'desktop') {
                        var exitTriggered = false;
                        document.addEventListener('mouseout', function (e) {
                            if (exitTriggered) return;
                            if (e.clientY < 10 && e.relatedTarget === null) {
                                exitTriggered = true;
                                self.showPopup(overlay, popup);
                            }
                        });
                    }
                    break;

                case 'scroll':
                    var scrollPercent = parseInt(popup.trigger_value) || 50;
                    var scrollTriggered = false;

                    window.addEventListener('scroll', function () {
                        if (scrollTriggered) return;

                        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
                        var currentPercent = (scrollTop / docHeight) * 100;

                        if (currentPercent >= scrollPercent) {
                            scrollTriggered = true;
                            self.showPopup(overlay, popup);
                        }
                    });
                    break;

                case 'inactivity':
                    var inactivityTime = (parseInt(popup.trigger_value) || 30) * 1000;
                    var inactivityTimer;
                    var inactivityTriggered = false;

                    var resetTimer = function () {
                        if (inactivityTriggered) return;
                        clearTimeout(inactivityTimer);
                        inactivityTimer = setTimeout(function () {
                            inactivityTriggered = true;
                            self.showPopup(overlay, popup);
                        }, inactivityTime);
                    };

                    ['mousemove', 'keydown', 'scroll', 'touchstart'].forEach(function (event) {
                        document.addEventListener(event, resetTimer, { passive: true });
                    });

                    resetTimer();
                    break;
            }
        },

        showPopup: function (overlay, popup) {
            // Prevent showing multiple popups
            if (shownPopups.includes(popup.id_popup)) {
                return;
            }

            // Check priority - only show highest priority popup
            var activeOverlay = document.querySelector('.smart-popup-overlay.active');
            if (activeOverlay) {
                var activeId = activeOverlay.getAttribute('data-popup-id');
                var activePopup = popups.find(function (p) { return p.id_popup == activeId; });
                if (activePopup && parseInt(activePopup.priority) >= parseInt(popup.priority)) {
                    return;
                }
                this.closePopup(activeOverlay);
            }

            shownPopups.push(popup.id_popup);

            // Show overlay
            overlay.style.display = 'flex';

            // Trigger reflow for animation
            overlay.offsetHeight;

            overlay.classList.add('active');

            // Track impression
            this.trackStat(popup.id_popup, 'impression');

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        },

        closePopup: function (overlay) {
            if (!overlay) return;

            var popupId = overlay.getAttribute('data-popup-id');
            var frequencyDays = parseInt(overlay.getAttribute('data-frequency-days')) || 1;

            overlay.classList.remove('active');

            setTimeout(function () {
                overlay.style.display = 'none';
            }, 300);

            // Set suppression cookie
            this.suppressPopup(popupId, frequencyDays);

            // Restore body scroll
            document.body.style.overflow = '';
        },

        closeAllPopups: function () {
            var self = this;
            document.querySelectorAll('.smart-popup-overlay.active').forEach(function (overlay) {
                self.closePopup(overlay);
            });
        },

        handleNewsletterSubmit: function (form) {
            var self = this;
            var emailInput = form.querySelector('input[name="email"]');
            var email = emailInput.value;
            var popupId = form.getAttribute('data-popup-id');
            var messageEl = form.parentNode.querySelector('.newsletter-message');
            var submitBtn = form.querySelector('button[type="submit"]');
            var originalText = submitBtn.textContent;

            // Disable button
            submitBtn.disabled = true;
            submitBtn.textContent = '...';

            // AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open('POST', config.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function () {
                var response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    response = { success: false, message: 'An error occurred' };
                }

                messageEl.style.display = 'block';
                messageEl.className = 'newsletter-message ' + (response.success ? 'success' : 'error');
                messageEl.textContent = response.message;

                if (response.success) {
                    // Track conversion
                    self.trackStat(popupId, 'conversion');

                    // Set permanent suppression cookie
                    self.suppressPopup(popupId, 365);

                    // Hide form
                    form.style.display = 'none';

                    // Close popup after delay
                    setTimeout(function () {
                        var overlay = document.querySelector('[data-popup-id="' + popupId + '"]');
                        self.closePopup(overlay);
                    }, 2000);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            };

            xhr.onerror = function () {
                messageEl.style.display = 'block';
                messageEl.className = 'newsletter-message error';
                messageEl.textContent = 'Connection error. Please try again.';
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            };

            xhr.send('action=newsletter&email=' + encodeURIComponent(email) + '&id_popup=' + popupId);
        },

        trackStat: function (popupId, statType) {
            if (!config.ajaxUrl) return;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', config.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=track&id_popup=' + popupId + '&stat_type=' + statType);
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            PopupManager.init();
        });
    } else {
        PopupManager.init();
    }

})();
