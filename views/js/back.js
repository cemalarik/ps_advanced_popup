/**
 * Smart Popup Admin JS
 */
$(document).ready(function () {
    // Trigger type change handler
    $('select[name="trigger_type"]').on('change', function () {
        updateTriggerHint($(this).val());
    }).trigger('change');

    function updateTriggerHint(type) {
        var hints = {
            'load': 'Saniye cinsinden gecikme süresi (örn: 5 = 5 saniye sonra)',
            'exit': 'Kullanılmaz (Exit intent otomatik algılanır)',
            'scroll': 'Yüzde cinsinden scroll miktarı (örn: 50 = %50)',
            'inactivity': 'Saniye cinsinden hareketsizlik süresi'
        };

        var $hint = $('input[name="trigger_value"]').closest('.form-group').find('.help-block');
        if ($hint.length) {
            $hint.text(hints[type] || '');
        }
    }

    // Popup type change handler
    $('select[name="popup_type"]').on('change', function () {
        var type = $(this).val();

        var $contentGroup = $('textarea[name^="content"]').closest('.form-group');
        var $ctaGroup = $('input[name^="cta_text"]').closest('.form-group');
        var $ctaUrlGroup = $('input[name^="cta_url"]').closest('.form-group');

        if (type === 'image') {
            $contentGroup.hide();
            $ctaGroup.hide();
        } else {
            $contentGroup.show();
            $ctaGroup.show();
        }
    }).trigger('change');

    // Target pages change handler
    $('select[name="target_pages[]"]').on('change', function () {
        var selected = $(this).val() || [];

        // If 'all' is selected, deselect others
        if (selected.includes('all') && selected.length > 1) {
            $(this).val(['all']);
        }
    });

    // Initialize chosen for multiple selects
    if ($.fn.chosen) {
        $('select.chosen').chosen({
            width: '100%',
            placeholder_text_multiple: 'Seçiniz...'
        });
    }

    // Form validation
    $('form#smart_popup_form').on('submit', function (e) {
        var title = $('input[name^="title_"]').first().val();
        if (!title || title.trim() === '') {
            alert('Lütfen bir başlık girin.');
            e.preventDefault();
            return false;
        }

        var overlayOpacity = parseFloat($('input[name="overlay_opacity"]').val());
        if (isNaN(overlayOpacity) || overlayOpacity < 0 || overlayOpacity > 1) {
            alert('Overlay opaklığı 0 ile 1 arasında olmalıdır.');
            e.preventDefault();
            return false;
        }

        return true;
    });

    // Color picker preview
    $('input[name="bg_color"]').on('change input', function () {
        $(this).css('background-color', $(this).val());
    }).trigger('change');

    // Image preview
    $('input[name="bg_image"]').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var $preview = $('.image-preview');
                if (!$preview.length) {
                    $preview = $('<div class="image-preview"><img src=""></div>');
                    $('input[name="bg_image"]').closest('.form-group').append($preview);
                }
                $preview.find('img').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
});
