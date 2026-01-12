{*
 * Smart Popup Front Template
 *}

{foreach from=$popups item=popup}
<div class="smart-popup-overlay"
     data-popup-id="{$popup.id_popup|intval}"
     data-trigger-type="{$popup.trigger_type|escape:'html':'UTF-8'}"
     data-trigger-value="{$popup.trigger_value|intval}"
     data-frequency-days="{$popup.frequency_days|intval}"
     data-animation="{$popup.animation|escape:'html':'UTF-8'}"
     data-hide-mobile="{$popup.hide_on_mobile|intval}"
     data-priority="{$popup.priority|intval}"
     style="display: none; --overlay-opacity: {$popup.overlay_opacity|floatval};">

    <div class="smart-popup-container animated {$popup.animation|escape:'html':'UTF-8'}"
         style="width: {$popup.width|intval}px;
                background-color: {$popup.bg_color|escape:'html':'UTF-8'};
                border-radius: {$popup.border_radius|intval}px;
                {if $popup.bg_image}background-image: url('{$popup.bg_image|escape:'html':'UTF-8'}');{/if}">

        {* Close Button *}
        <button type="button" class="smart-popup-close close-style-{$popup.close_button_style|escape:'html':'UTF-8'}"
                aria-label="{l s='Close' mod='ps_advanced_popup'}">
            {if $popup.close_button_style == 'text'}
                {l s='Close' mod='ps_advanced_popup'}
            {else}
                <span aria-hidden="true">&times;</span>
            {/if}
        </button>

        {* Popup Content *}
        <div class="smart-popup-content">
            {if $popup.popup_type == 'newsletter'}
                {* Newsletter Form *}
                <div class="smart-popup-newsletter">
                    {if $popup.title}
                        <h3 class="popup-title">{$popup.title|escape:'html':'UTF-8'}</h3>
                    {/if}

                    {if $popup.content}
                        <div class="popup-description">{$popup.content nofilter}</div>
                    {/if}

                    <form class="smart-popup-newsletter-form" data-popup-id="{$popup.id_popup|intval}">
                        <div class="form-group">
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   placeholder="{l s='Your email address' mod='ps_advanced_popup'}"
                                   required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            {if $popup.cta_text}{$popup.cta_text|escape:'html':'UTF-8'}{else}{l s='Subscribe' mod='ps_advanced_popup'}{/if}
                        </button>
                    </form>

                    <div class="newsletter-message" style="display: none;"></div>
                </div>

            {elseif $popup.popup_type == 'image'}
                {* Image Only *}
                {if $popup.cta_url}
                    <a href="{$popup.cta_url|escape:'html':'UTF-8'}" class="popup-image-link">
                        <img src="{$popup.bg_image|escape:'html':'UTF-8'}" alt="{$popup.title|escape:'html':'UTF-8'}" class="popup-image">
                    </a>
                {else}
                    <img src="{$popup.bg_image|escape:'html':'UTF-8'}" alt="{$popup.title|escape:'html':'UTF-8'}" class="popup-image">
                {/if}

            {else}
                {* HTML Content *}
                {if $popup.title}
                    <h3 class="popup-title">{$popup.title|escape:'html':'UTF-8'}</h3>
                {/if}

                <div class="popup-html-content">{$popup.content nofilter}</div>

                {if $popup.cta_text && $popup.cta_url}
                    <div class="popup-cta">
                        <a href="{$popup.cta_url|escape:'html':'UTF-8'}" class="btn btn-primary popup-cta-btn">
                            {$popup.cta_text|escape:'html':'UTF-8'}
                        </a>
                    </div>
                {/if}
            {/if}
        </div>
    </div>
</div>
{/foreach}
