{*
 * Advanced Popup Studio front template.
 *}

{foreach from=$popups item=popup}
<div class="smart-popup-overlay"
     data-popup-id="{$popup.id_popup|intval}"
     data-trigger-type="{$popup.trigger_type|escape:'html':'UTF-8'}"
     data-trigger-value="{$popup.trigger_value|intval}"
     data-frequency-days="{$popup.frequency_days|intval}"
     data-priority="{$popup.priority|intval}"
     data-close-overlay="{$popup.close_on_overlay|intval}"
     data-mobile-behavior="{$popup.mobile_behavior|escape:'html':'UTF-8'}"
     data-ab-enabled="{$popup.ab_test_enabled|intval}"
     style="display:none; --popup-overlay-opacity: {$popup.overlay_opacity|floatval};">

    <div class="smart-popup-container smart-popup-layout-{$popup.layout|escape:'html':'UTF-8'} smart-popup-mobile-{$popup.mobile_behavior|escape:'html':'UTF-8'}"
         role="dialog"
         aria-modal="true"
         aria-label="{$popup.internal_name|escape:'html':'UTF-8'}"
         tabindex="-1"
         style="--popup-width: {$popup.width|intval}px;
                --popup-bg: {$popup.bg_color|escape:'html':'UTF-8'};
                --popup-text: {$popup.text_color|escape:'html':'UTF-8'};
                --popup-subtitle: {$popup.subtitle_color|default:'#4b5563'|escape:'html':'UTF-8'};
                --popup-accent: {$popup.accent_color|escape:'html':'UTF-8'};
                --popup-button: {$popup.button_color|escape:'html':'UTF-8'};
                --popup-button-text: {$popup.button_text_color|escape:'html':'UTF-8'};
                --popup-radius: {$popup.border_radius|intval}px;
                {if $popup.bg_image_url}--popup-image: url('{$popup.bg_image_url|escape:'html':'UTF-8'}');{/if}">

        <button type="button"
                class="smart-popup-close close-style-{$popup.close_button_style|escape:'html':'UTF-8'}"
                aria-label="{l s='Close' mod='ps_advanced_popup'}">
            {if $popup.close_button_style == 'text'}
                {l s='Close' mod='ps_advanced_popup'}
            {else}
                <span aria-hidden="true">&times;</span>
            {/if}
        </button>

        {if $popup.bg_image}
            <div class="smart-popup-media" aria-hidden="true"></div>
        {/if}

        <div class="smart-popup-body">
            {foreach from=$popup.variants item=variant}
                <div class="smart-popup-variant"
                     data-variant-id="{$variant.id_variant|intval}"
                     data-variant-key="{$variant.variant_key|escape:'html':'UTF-8'}"
                     data-traffic="{$variant.traffic_percentage|intval}"
                     hidden>
                    <div class="smart-popup-copy">
                        <h3 class="smart-popup-title" id="smart-popup-title-{$popup.id_popup|intval}-{$variant.id_variant|intval}">
                            {if $variant.title}{$variant.title|escape:'html':'UTF-8'}{else}{$popup.title|escape:'html':'UTF-8'}{/if}
                        </h3>

                        {if $variant.subtitle || $popup.subtitle}
                            <p class="smart-popup-subtitle">
                                {if $variant.subtitle}{$variant.subtitle|escape:'html':'UTF-8'}{else}{$popup.subtitle|escape:'html':'UTF-8'}{/if}
                            </p>
                        {/if}

                        {if $variant.content || $popup.content}
                            <div class="smart-popup-content">
                                {if $variant.content}{$variant.content nofilter}{else}{$popup.content nofilter}{/if}
                            </div>
                        {/if}
                    </div>

                    {if $popup.popup_type == 'coupon' || $variant.coupon_code || $popup.coupon_code}
                        <div class="smart-popup-coupon">
                            <code>{if $variant.coupon_code}{$variant.coupon_code|escape:'html':'UTF-8'}{else}{$popup.coupon_code|escape:'html':'UTF-8'}{/if}</code>
                            <button type="button"
                                    class="smart-popup-copy-coupon"
                                    data-coupon="{if $variant.coupon_code}{$variant.coupon_code|escape:'html':'UTF-8'}{else}{$popup.coupon_code|escape:'html':'UTF-8'}{/if}">
                                {l s='Copy code' mod='ps_advanced_popup'}
                            </button>
                        </div>
                    {/if}

                    {if $popup.popup_type == 'newsletter'}
                        <form class="smart-popup-newsletter-form"
                              data-popup-id="{$popup.id_popup|intval}"
                              data-variant-id="{$variant.id_variant|intval}">
                            <label class="sr-only" for="smart-popup-email-{$popup.id_popup|intval}-{$variant.id_variant|intval}">
                                {l s='Email address' mod='ps_advanced_popup'}
                            </label>
                            <input id="smart-popup-email-{$popup.id_popup|intval}-{$variant.id_variant|intval}"
                                   type="email"
                                   name="email"
                                   autocomplete="email"
                                   placeholder="{l s='Your email address' mod='ps_advanced_popup'}"
                                   required>
                            {if $popup.consent_text}
                                <label class="smart-popup-consent">
                                    <input type="checkbox" name="consent" value="1">
                                    <span>{$popup.consent_text|escape:'html':'UTF-8'}</span>
                                </label>
                            {/if}
                            <button type="submit">
                                {if $variant.cta_text}{$variant.cta_text|escape:'html':'UTF-8'}{elseif $popup.cta_text}{$popup.cta_text|escape:'html':'UTF-8'}{else}{l s='Subscribe' mod='ps_advanced_popup'}{/if}
                            </button>
                        </form>
                        <div class="smart-popup-message" aria-live="polite" hidden></div>
                    {elseif $variant.cta_url || $popup.cta_url}
                        <a class="smart-popup-cta"
                           href="{if $variant.cta_url}{$variant.cta_url|escape:'html':'UTF-8'}{else}{$popup.cta_url|escape:'html':'UTF-8'}{/if}"
                           data-popup-id="{$popup.id_popup|intval}"
                           data-variant-id="{$variant.id_variant|intval}">
                            {if $variant.cta_text}{$variant.cta_text|escape:'html':'UTF-8'}{elseif $popup.cta_text}{$popup.cta_text|escape:'html':'UTF-8'}{else}{l s='Learn more' mod='ps_advanced_popup'}{/if}
                        </a>
                    {/if}
                </div>
            {/foreach}
        </div>
    </div>
</div>
{/foreach}
