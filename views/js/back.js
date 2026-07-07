(function () {
    'use strict';

    var selectedPreviewImage = '';
    var activePreviewVariant = 'a';
    var wasAbTestEnabled = false;

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

    function classNameForLayout(layout) {
        return 'aps-preview-layout-' + (layout || 'centered').replace(/[^a-z0-9_-]/gi, '');
    }

    function cssUrl(url) {
        return url ? 'url("' + String(url).replace(/"/g, '\\"') + '")' : '';
    }

    function getPreviewImage(preview) {
        return selectedPreviewImage || preview.getAttribute('data-existing-image') || '';
    }

    function fieldValue(name) {
        var input = field(name);
        return input ? input.value : '';
    }

    function pickPreviewValue(value, fallback) {
        return value !== '' ? value : fallback;
    }

    function isAbTestEnabled() {
        var input = field('ab_test_enabled');
        return !!(input && input.checked);
    }

    function getPreviewCopy(langId) {
        var base = {
            title: fieldValue('title_' + langId),
            subtitle: fieldValue('subtitle_' + langId),
            content: fieldValue('content_' + langId),
            cta: fieldValue('cta_text_' + langId),
            coupon: fieldValue('coupon_code_' + langId)
        };

        if (!isAbTestEnabled()) {
            return base;
        }

        var variantKey = activePreviewVariant === 'b' ? 'b' : 'a';
        return {
            title: pickPreviewValue(fieldValue('variant_' + variantKey + '_title_' + langId), base.title),
            subtitle: pickPreviewValue(fieldValue('variant_' + variantKey + '_subtitle_' + langId), base.subtitle),
            content: pickPreviewValue(fieldValue('variant_' + variantKey + '_content_' + langId), base.content),
            cta: pickPreviewValue(fieldValue('variant_' + variantKey + '_cta_text_' + langId), base.cta),
            coupon: pickPreviewValue(fieldValue('variant_' + variantKey + '_coupon_code_' + langId), base.coupon)
        };
    }

    function syncVariantPreviewControls() {
        var enabled = isAbTestEnabled();
        var controls = $('[data-ab-preview-controls]');
        var trafficA = field('variant_a_traffic');
        var trafficB = $('[data-variant-b-traffic]');

        if (!enabled) {
            activePreviewVariant = 'a';
        }

        if (controls) {
            controls.hidden = !enabled;
        }

        if (trafficA && trafficB) {
            var value = parseInt(trafficA.value, 10);
            if (isNaN(value)) {
                value = 50;
            }
            if (enabled) {
                if (!wasAbTestEnabled && value > 99) {
                    value = 50;
                } else if (value > 99) {
                    value = 99;
                } else if (value < 1) {
                    value = 1;
                }
                trafficA.value = value;
            }
            trafficB.value = enabled ? String(100 - value) : '0';
        }

        $all('[data-preview-variant]').forEach(function (button) {
            var key = button.getAttribute('data-preview-variant') === 'b' ? 'b' : 'a';
            var name = fieldValue('variant_' + key + '_name') || (key === 'b' ? 'Variant B' : 'Variant A');
            var traffic = key === 'b' && trafficB ? trafficB.value : (trafficA ? trafficA.value : '');

            button.textContent = enabled && traffic !== '' ? name + ' (' + traffic + '%)' : name;
            button.classList.toggle('is-active', enabled && key === activePreviewVariant);
        });

        wasAbTestEnabled = enabled;
    }

    function updatePreview() {
        var preview = $('.aps-preview-popup');
        if (!preview) {
            return;
        }

        var langId = activeLanguageId();
        var previewCopy = getPreviewCopy(langId);
        var layoutField = field('layout');
        var layout = layoutField ? layoutField.value : 'centered';
        var imageUrl = getPreviewImage(preview);
        var media = $('.aps-preview-media', preview);
        var body = $('.aps-preview-body', preview);
        var shell = $('.aps-preview-shell');

        syncVariantPreviewControls();

        $('.aps-preview-title').textContent = previewCopy.title;
        $('.aps-preview-subtitle').textContent = previewCopy.subtitle;
        $('.aps-preview-content').textContent = previewCopy.content;
        $('.aps-preview-cta').textContent = previewCopy.cta;
        $('.aps-preview-coupon').textContent = previewCopy.coupon;
        $('.aps-preview-coupon').style.display = previewCopy.coupon ? 'inline-flex' : 'none';

        var width = field('width') ? parseInt(field('width').value, 10) : 560;
        var radius = field('border_radius') ? parseInt(field('border_radius').value, 10) : 8;
        var isMobilePreview = shell && shell.getAttribute('data-device') === 'mobile';
        var mobileBehavior = field('mobile_behavior') ? field('mobile_behavior').value : 'bottom_sheet';

        if (isNaN(width)) {
            width = 560;
        }
        if (isNaN(radius)) {
            radius = 8;
        }

        preview.style.backgroundColor = field('bg_color') ? field('bg_color').value : '#ffffff';
        preview.style.color = field('text_color') ? field('text_color').value : '#1f2937';
        preview.style.borderRadius = isMobilePreview && mobileBehavior === 'bottom_sheet'
            ? radius + 'px ' + radius + 'px 0 0'
            : radius + 'px';
        preview.style.maxWidth = (isMobilePreview ? Math.min(width, 320) : width) + 'px';
        $('.aps-preview-cta').style.backgroundColor = field('button_color') ? field('button_color').value : '#111827';
        $('.aps-preview-cta').style.color = field('button_text_color') ? field('button_text_color').value : '#ffffff';
        $('.aps-preview-coupon').style.borderColor = field('accent_color') ? field('accent_color').value : '#25b9d7';

        $all('select[name="layout"] option').forEach(function (option) {
            preview.classList.remove(classNameForLayout(option.value));
        });
        preview.classList.add(classNameForLayout(layout));
        preview.classList.toggle('has-image', !!imageUrl);
        preview.classList.toggle('has-full-image', layout === 'full_image' && !!imageUrl);

        if (media) {
            media.style.backgroundImage = imageUrl && layout !== 'full_image' ? cssUrl(imageUrl) : '';
        }
        preview.style.backgroundImage = imageUrl && layout === 'full_image' ? cssUrl(imageUrl) : '';

        if (body) {
            body.style.backgroundColor = layout === 'full_image' && imageUrl
                ? 'rgba(255, 255, 255, 0.94)'
                : '';
        }
        if (shell) {
            shell.setAttribute('data-mobile-behavior', mobileBehavior);
        }
    }

    function bindImagePreview() {
        var input = field('bg_image');
        if (!input) {
            return;
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files.length ? input.files[0] : null;
            if (!file) {
                selectedPreviewImage = '';
                updatePreview();
                return;
            }

            if (!/^image\//.test(file.type)) {
                selectedPreviewImage = '';
                updatePreview();
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                selectedPreviewImage = event.target && event.target.result ? event.target.result : '';
                updatePreview();
            };
            reader.readAsDataURL(file);
        });
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
        $all('[data-preview], input[type="color"], input[name="width"], input[name="border_radius"], input[name="overlay_opacity"], input[name="ab_test_enabled"], input[name^="variant_"], textarea[name^="variant_"], select[name="layout"], select[name="mobile_behavior"]').forEach(function (input) {
            input.addEventListener('input', updatePreview);
            input.addEventListener('change', updatePreview);
        });

        $all('[data-preview-variant]').forEach(function (button) {
            button.addEventListener('click', function () {
                activePreviewVariant = button.getAttribute('data-preview-variant') === 'b' ? 'b' : 'a';
                updatePreview();
            });
        });

        $all('[data-preview-device]').forEach(function (button) {
            button.addEventListener('click', function () {
                $all('[data-preview-device]').forEach(function (item) {
                    item.classList.toggle('is-active', item === button);
                });
                var shell = $('.aps-preview-shell');
                if (shell) {
                    shell.setAttribute('data-device', button.getAttribute('data-preview-device'));
                    updatePreview();
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
            var abEnabled = isAbTestEnabled();

            if (!name || !name.value.trim()) {
                event.preventDefault();
                alert('Internal name is required.');
                setStep(1);
                return;
            }

            if (abEnabled && traffic) {
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
        bindImagePreview();
        bindValidation();
        bindConfirmations();

        if (window.ApsChartLite && window.apsStatsChartData && $('#apsStatsChart')) {
            window.ApsChartLite.draw($('#apsStatsChart'), window.apsStatsChartData);
        }
    });
})();
