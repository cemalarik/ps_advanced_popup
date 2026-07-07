(function () {
    'use strict';

    function $(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function $all(selector, scope) {
        return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
    }

    function setStep(step) {
        $all('.aps-step').forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-step') === String(step));
        });
        $all('.aps-wizard-panel').forEach(function (panel) {
            panel.classList.toggle('is-active', panel.getAttribute('data-panel') === String(step));
        });
    }

    function currentStep() {
        var active = $('.aps-step.is-active');
        return active ? parseInt(active.getAttribute('data-step'), 10) : 1;
    }

    function activeLanguageId() {
        var active = $('.aps-lang-tab.is-active');
        return active ? active.getAttribute('data-lang') : null;
    }

    function field(name) {
        return $('[name="' + name + '"]');
    }

    function updatePreview() {
        var preview = $('.aps-preview-popup');
        if (!preview) {
            return;
        }

        var langId = activeLanguageId();
        var title = field('title_' + langId);
        var subtitle = field('subtitle_' + langId);
        var content = field('content_' + langId);
        var cta = field('cta_text_' + langId);
        var coupon = field('coupon_code_' + langId);

        $('.aps-preview-title').textContent = title ? title.value : '';
        $('.aps-preview-subtitle').textContent = subtitle ? subtitle.value : '';
        $('.aps-preview-content').textContent = content ? content.value : '';
        $('.aps-preview-cta').textContent = cta ? cta.value : '';
        $('.aps-preview-coupon').textContent = coupon ? coupon.value : '';
        $('.aps-preview-coupon').style.display = coupon && coupon.value ? 'inline-flex' : 'none';

        preview.style.backgroundColor = field('bg_color') ? field('bg_color').value : '#ffffff';
        preview.style.color = field('text_color') ? field('text_color').value : '#1f2937';
        preview.style.borderRadius = (field('border_radius') ? field('border_radius').value : 8) + 'px';
        preview.style.maxWidth = (field('width') ? field('width').value : 560) + 'px';
        $('.aps-preview-cta').style.backgroundColor = field('button_color') ? field('button_color').value : '#111827';
        $('.aps-preview-cta').style.color = field('button_text_color') ? field('button_text_color').value : '#ffffff';
        $('.aps-preview-coupon').style.borderColor = field('accent_color') ? field('accent_color').value : '#25b9d7';
    }

    function bindWizard() {
        $all('.aps-step').forEach(function (button) {
            button.addEventListener('click', function () {
                setStep(button.getAttribute('data-step'));
            });
        });

        var next = $('.aps-next-step');
        var prev = $('.aps-prev-step');
        if (next) {
            next.addEventListener('click', function () {
                setStep(Math.min(5, currentStep() + 1));
            });
        }
        if (prev) {
            prev.addEventListener('click', function () {
                setStep(Math.max(1, currentStep() - 1));
            });
        }
    }

    function bindLanguages() {
        $all('.aps-lang-tab').forEach(function (button) {
            button.addEventListener('click', function () {
                var lang = button.getAttribute('data-lang');
                $all('.aps-lang-tab').forEach(function (tab) {
                    tab.classList.toggle('is-active', tab === button);
                });
                $all('.aps-language-panel').forEach(function (panel) {
                    panel.classList.toggle('is-active', panel.getAttribute('data-lang-panel') === lang);
                });
                updatePreview();
            });
        });
    }

    function bindTemplates() {
        $all('.aps-template-grid-select input[type="radio"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                $all('.aps-template-card').forEach(function (card) {
                    card.classList.remove('is-selected');
                });
                radio.closest('.aps-template-card').classList.add('is-selected');

                if (field('campaign_goal')) {
                    field('campaign_goal').value = radio.getAttribute('data-goal');
                }
                if (field('popup_type')) {
                    field('popup_type').value = radio.getAttribute('data-type');
                }
                if (field('layout')) {
                    field('layout').value = radio.getAttribute('data-layout');
                }
                updatePreview();
            });
        });
    }

    function bindPreview() {
        $all('[data-preview], input[type="color"], input[name="width"], input[name="border_radius"]').forEach(function (input) {
            input.addEventListener('input', updatePreview);
            input.addEventListener('change', updatePreview);
        });

        $all('[data-preview-device]').forEach(function (button) {
            button.addEventListener('click', function () {
                $all('[data-preview-device]').forEach(function (item) {
                    item.classList.toggle('is-active', item === button);
                });
                var shell = $('.aps-preview-shell');
                if (shell) {
                    shell.setAttribute('data-device', button.getAttribute('data-preview-device'));
                }
            });
        });

        updatePreview();
    }

    function bindValidation() {
        var form = $('#aps-editor-form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            var name = field('internal_name');
            var traffic = field('variant_a_traffic');

            if (!name || !name.value.trim()) {
                event.preventDefault();
                alert('Internal name is required.');
                setStep(1);
                return;
            }

            if (traffic) {
                var value = parseInt(traffic.value, 10);
                if (isNaN(value) || value < 1 || value > 99) {
                    event.preventDefault();
                    alert('Variant A traffic must be between 1 and 99.');
                    setStep(5);
                }
            }
        });
    }

    function bindConfirmations() {
        $all('.aps-confirm-delete').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (!window.confirm('Delete this popup?')) {
                    event.preventDefault();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindWizard();
        bindLanguages();
        bindTemplates();
        bindPreview();
        bindValidation();
        bindConfirmations();

        if (window.ApsChartLite && window.apsStatsChartData && $('#apsStatsChart')) {
            window.ApsChartLite.draw($('#apsStatsChart'), window.apsStatsChartData);
        }
    });
})();
