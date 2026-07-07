{*
 * Advanced Popup Studio editor.
 *}

<div class="aps-admin aps-editor">
    <div class="aps-header">
        <div>
            <h2>{$editor_title|escape:'html':'UTF-8'}</h2>
            <p>{l s='Build a campaign from intent to preview without touching raw HTML.' mod='ps_advanced_popup'}</p>
        </div>
        <a href="{$back_url|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="icon-arrow-left"></i> {l s='Back to dashboard' mod='ps_advanced_popup'}
        </a>
    </div>

    <form id="aps-editor-form" class="defaultForm form-horizontal" action="{$form_action|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data">
        <input type="hidden" name="submitSmartPopup" value="1">
        <input type="hidden" name="id_popup" value="{$popup.id_popup|intval}">
        <input type="hidden" name="token" value="{$token|escape:'html':'UTF-8'}">

        <div class="aps-wizard">
            <div class="aps-steps">
                <button type="button" class="aps-step is-active" data-step="1">{l s='Goal' mod='ps_advanced_popup'}</button>
                <button type="button" class="aps-step" data-step="2">{l s='Content' mod='ps_advanced_popup'}</button>
                <button type="button" class="aps-step" data-step="3">{l s='Design' mod='ps_advanced_popup'}</button>
                <button type="button" class="aps-step" data-step="4">{l s='Targeting' mod='ps_advanced_popup'}</button>
                <button type="button" class="aps-step" data-step="5">{l s='Preview' mod='ps_advanced_popup'}</button>
            </div>

            <section class="aps-panel aps-wizard-panel is-active" data-panel="1">
                <h3>{l s='Campaign goal' mod='ps_advanced_popup'}</h3>
                <div class="aps-template-grid aps-template-grid-select">
                    {foreach from=$templates key=template_key item=template}
                        <label class="aps-template-card {if $popup.template_key == $template_key}is-selected{/if}">
                            <input type="radio" name="template_key" value="{$template_key|escape:'html':'UTF-8'}" {if $popup.template_key == $template_key}checked{/if}
                                   data-goal="{$template.goal|escape:'html':'UTF-8'}"
                                   data-type="{$template.type|escape:'html':'UTF-8'}"
                                   data-layout="{$template.layout|escape:'html':'UTF-8'}">
                            <span>{$template.label|escape:'html':'UTF-8'}</span>
                            <small>{$template.goal|escape:'html':'UTF-8'} / {$template.layout|escape:'html':'UTF-8'}</small>
                        </label>
                    {/foreach}
                </div>

                <div class="aps-grid aps-grid-3">
                    <label>
                        <span>{l s='Internal name' mod='ps_advanced_popup'}</span>
                        <input type="text" name="internal_name" value="{$popup.internal_name|escape:'html':'UTF-8'}" required>
                    </label>
                    <label>
                        <span>{l s='Campaign goal' mod='ps_advanced_popup'}</span>
                        <select name="campaign_goal" id="aps-campaign-goal">
                            <option value="newsletter" {if $popup.campaign_goal == 'newsletter'}selected{/if}>newsletter</option>
                            <option value="coupon" {if $popup.campaign_goal == 'coupon'}selected{/if}>coupon</option>
                            <option value="cart_recovery" {if $popup.campaign_goal == 'cart_recovery'}selected{/if}>cart_recovery</option>
                            <option value="free_shipping" {if $popup.campaign_goal == 'free_shipping'}selected{/if}>free_shipping</option>
                            <option value="upsell" {if $popup.campaign_goal == 'upsell'}selected{/if}>upsell</option>
                            <option value="image_campaign" {if $popup.campaign_goal == 'image_campaign'}selected{/if}>image_campaign</option>
                            <option value="announcement" {if $popup.campaign_goal == 'announcement'}selected{/if}>announcement</option>
                        </select>
                    </label>
                    <label>
                        <span>{l s='Popup type' mod='ps_advanced_popup'}</span>
                        <select name="popup_type" id="aps-popup-type">
                            <option value="newsletter" {if $popup.popup_type == 'newsletter'}selected{/if}>newsletter</option>
                            <option value="coupon" {if $popup.popup_type == 'coupon'}selected{/if}>coupon</option>
                            <option value="cta" {if $popup.popup_type == 'cta'}selected{/if}>cta</option>
                            <option value="image" {if $popup.popup_type == 'image'}selected{/if}>image</option>
                            <option value="announcement" {if $popup.popup_type == 'announcement'}selected{/if}>announcement</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="aps-panel aps-wizard-panel" data-panel="2">
                <div class="aps-section-heading">
                    <h3>{l s='Content' mod='ps_advanced_popup'}</h3>
                    <div class="aps-language-tabs">
                        {foreach from=$languages item=language}
                            <button type="button" class="aps-lang-tab {if $language.id_lang == $default_language_id}is-active{/if}" data-lang="{$language.id_lang|intval}">
                                {$language.iso_code|escape:'html':'UTF-8'}
                            </button>
                        {/foreach}
                    </div>
                </div>

                {foreach from=$languages item=language}
                    {assign var=id_lang value=$language.id_lang}
                    <div class="aps-language-panel {if $id_lang == $default_language_id}is-active{/if}" data-lang-panel="{$id_lang|intval}">
                        <div class="aps-grid aps-grid-2">
                            <label>
                                <span>{l s='Title' mod='ps_advanced_popup'}</span>
                                <input type="text" name="title_{$id_lang|intval}" data-preview="title" value="{$lang_values.$id_lang.title|default:''|escape:'html':'UTF-8'}">
                            </label>
                            <label>
                                <span>{l s='Subtitle' mod='ps_advanced_popup'}</span>
                                <input type="text" name="subtitle_{$id_lang|intval}" data-preview="subtitle" value="{$lang_values.$id_lang.subtitle|default:''|escape:'html':'UTF-8'}">
                            </label>
                        </div>
                        <label>
                            <span>{l s='Body copy' mod='ps_advanced_popup'}</span>
                            <textarea name="content_{$id_lang|intval}" rows="5" data-preview="content">{$lang_values.$id_lang.content|default:''|escape:'html':'UTF-8'}</textarea>
                        </label>
                        <div class="aps-grid aps-grid-2">
                            <label>
                                <span>{l s='CTA text' mod='ps_advanced_popup'}</span>
                                <input type="text" name="cta_text_{$id_lang|intval}" data-preview="cta" value="{$lang_values.$id_lang.cta_text|default:''|escape:'html':'UTF-8'}">
                            </label>
                            <label>
                                <span>{l s='CTA URL' mod='ps_advanced_popup'}</span>
                                <input type="url" name="cta_url_{$id_lang|intval}" value="{$lang_values.$id_lang.cta_url|default:''|escape:'html':'UTF-8'}" placeholder="https://example.com/campaign">
                            </label>
                            <label>
                                <span>{l s='Coupon code' mod='ps_advanced_popup'}</span>
                                <input type="text" name="coupon_code_{$id_lang|intval}" data-preview="coupon" value="{$lang_values.$id_lang.coupon_code|default:''|escape:'html':'UTF-8'}">
                            </label>
                            <label>
                                <span>{l s='Success message' mod='ps_advanced_popup'}</span>
                                <input type="text" name="success_message_{$id_lang|intval}" value="{$lang_values.$id_lang.success_message|default:''|escape:'html':'UTF-8'}">
                            </label>
                        </div>
                        <label>
                            <span>{l s='Consent text' mod='ps_advanced_popup'}</span>
                            <input type="text" name="consent_text_{$id_lang|intval}" value="{$lang_values.$id_lang.consent_text|default:''|escape:'html':'UTF-8'}">
                        </label>
                    </div>
                {/foreach}
            </section>

            <section class="aps-panel aps-wizard-panel" data-panel="3">
                <h3>{l s='Design and behavior' mod='ps_advanced_popup'}</h3>
                <div class="aps-grid aps-grid-4">
                    <label>
                        <span>{l s='Layout' mod='ps_advanced_popup'}</span>
                        <select name="layout" id="aps-layout">
                            {foreach from=$layout_options item=layout}
                                <option value="{$layout|escape:'html':'UTF-8'}" {if $popup.layout == $layout}selected{/if}>{$layout|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </label>
                    <label>
                        <span>{l s='Width' mod='ps_advanced_popup'}</span>
                        <input type="number" name="width" min="320" max="920" value="{$popup.width|intval}">
                    </label>
                    <label>
                        <span>{l s='Radius' mod='ps_advanced_popup'}</span>
                        <input type="number" name="border_radius" min="0" max="32" value="{$popup.border_radius|intval}">
                    </label>
                    <label>
                        <span>{l s='Overlay opacity' mod='ps_advanced_popup'}</span>
                        <input type="number" name="overlay_opacity" min="0" max="0.85" step="0.05" value="{$popup.overlay_opacity|escape:'html':'UTF-8'}">
                    </label>
                </div>

                <div class="aps-grid aps-grid-5 aps-color-grid">
                    <label><span>{l s='Background' mod='ps_advanced_popup'}</span><input type="color" name="bg_color" value="{$popup.bg_color|escape:'html':'UTF-8'}"></label>
                    <label><span>{l s='Text' mod='ps_advanced_popup'}</span><input type="color" name="text_color" value="{$popup.text_color|escape:'html':'UTF-8'}"></label>
                    <label><span>{l s='Accent' mod='ps_advanced_popup'}</span><input type="color" name="accent_color" value="{$popup.accent_color|escape:'html':'UTF-8'}"></label>
                    <label><span>{l s='Button' mod='ps_advanced_popup'}</span><input type="color" name="button_color" value="{$popup.button_color|escape:'html':'UTF-8'}"></label>
                    <label><span>{l s='Button text' mod='ps_advanced_popup'}</span><input type="color" name="button_text_color" value="{$popup.button_text_color|escape:'html':'UTF-8'}"></label>
                </div>

                <div class="aps-grid aps-grid-3">
                    <label>
                        <span>{l s='Background image' mod='ps_advanced_popup'}</span>
                        <input type="file" name="bg_image" accept="image/*">
                        {if $popup.bg_image}
                            <small>{$popup.bg_image|escape:'html':'UTF-8'}</small>
                        {/if}
                    </label>
                    <label>
                        <span>{l s='Animation' mod='ps_advanced_popup'}</span>
                        <select name="animation">
                            <option value="fadeIn" {if $popup.animation == 'fadeIn'}selected{/if}>fadeIn</option>
                            <option value="fadeInUp" {if $popup.animation == 'fadeInUp'}selected{/if}>fadeInUp</option>
                            <option value="zoomIn" {if $popup.animation == 'zoomIn'}selected{/if}>zoomIn</option>
                        </select>
                    </label>
                    <label>
                        <span>{l s='Mobile behavior' mod='ps_advanced_popup'}</span>
                        <select name="mobile_behavior">
                            {foreach from=$mobile_options item=mobile_option}
                                <option value="{$mobile_option|escape:'html':'UTF-8'}" {if $popup.mobile_behavior == $mobile_option}selected{/if}>{$mobile_option|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </label>
                </div>

                <div class="aps-grid aps-grid-4">
                    <label><span>{l s='Close style' mod='ps_advanced_popup'}</span>
                        <select name="close_button_style">
                            <option value="default" {if $popup.close_button_style == 'default'}selected{/if}>default</option>
                            <option value="circle" {if $popup.close_button_style == 'circle'}selected{/if}>circle</option>
                            <option value="square" {if $popup.close_button_style == 'square'}selected{/if}>square</option>
                            <option value="text" {if $popup.close_button_style == 'text'}selected{/if}>text</option>
                        </select>
                    </label>
                    <label class="aps-check"><input type="checkbox" name="close_on_overlay" value="1" {if $popup.close_on_overlay}checked{/if}> <span>{l s='Close on overlay click' mod='ps_advanced_popup'}</span></label>
                    <label class="aps-check"><input type="checkbox" name="active" value="1" {if $popup.active}checked{/if}> <span>{l s='Publish popup' mod='ps_advanced_popup'}</span></label>
                    <label><span>{l s='Priority' mod='ps_advanced_popup'}</span><input type="number" name="priority" min="0" value="{$popup.priority|intval}"></label>
                </div>
            </section>

            <section class="aps-panel aps-wizard-panel" data-panel="4">
                <h3>{l s='Targeting and triggers' mod='ps_advanced_popup'}</h3>
                <div class="aps-grid aps-grid-4">
                    <label>
                        <span>{l s='Trigger' mod='ps_advanced_popup'}</span>
                        <select name="trigger_type">
                            {foreach from=$trigger_options item=trigger}
                                <option value="{$trigger|escape:'html':'UTF-8'}" {if $popup.trigger_type == $trigger}selected{/if}>{$trigger|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </label>
                    <label><span>{l s='Trigger value' mod='ps_advanced_popup'}</span><input type="number" name="trigger_value" min="0" value="{$popup.trigger_value|intval}"></label>
                    <label><span>{l s='Show again after days' mod='ps_advanced_popup'}</span><input type="number" name="frequency_days" min="0" value="{$popup.frequency_days|intval}"></label>
                    <label><span>{l s='Winner metric' mod='ps_advanced_popup'}</span>
                        <select name="winner_metric">
                            <option value="conversion_rate" {if $popup.winner_metric == 'conversion_rate'}selected{/if}>conversion_rate</option>
                            <option value="cta_click" {if $popup.winner_metric == 'cta_click'}selected{/if}>cta_click</option>
                            <option value="newsletter_success" {if $popup.winner_metric == 'newsletter_success'}selected{/if}>newsletter_success</option>
                            <option value="coupon_copy" {if $popup.winner_metric == 'coupon_copy'}selected{/if}>coupon_copy</option>
                        </select>
                    </label>
                </div>

                <div class="aps-grid aps-grid-2">
                    <label><span>{l s='Start date' mod='ps_advanced_popup'}</span><input type="datetime-local" name="date_start" value="{$popup.date_start|default:''|replace:' ':'T'|escape:'html':'UTF-8'}"></label>
                    <label><span>{l s='End date' mod='ps_advanced_popup'}</span><input type="datetime-local" name="date_end" value="{$popup.date_end|default:''|replace:' ':'T'|escape:'html':'UTF-8'}"></label>
                </div>

                <div class="aps-grid aps-grid-3">
                    <label>
                        <span>{l s='Page types' mod='ps_advanced_popup'}</span>
                        <select name="target_page_types[]" multiple>
                            {foreach from=$page_type_options item=page_type}
                                <option value="{$page_type|escape:'html':'UTF-8'}" {if in_array($page_type, $targeting.page_types)}selected{/if}>{$page_type|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </label>
                    <label>
                        <span>{l s='Devices' mod='ps_advanced_popup'}</span>
                        <select name="target_devices[]" multiple>
                            {foreach from=$device_options item=device}
                                <option value="{$device|escape:'html':'UTF-8'}" {if in_array($device, $targeting.devices)}selected{/if}>{$device|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </label>
                    <label>
                        <span>{l s='Customer groups' mod='ps_advanced_popup'}</span>
                        <select name="target_groups[]" multiple>
                            {foreach from=$groups item=group}
                                <option value="{$group.id_group|intval}" {if in_array($group.id_group, $targeting.groups)}selected{/if}>{$group.name|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </label>
                </div>

                <div class="aps-grid aps-grid-3">
                    <label><span>{l s='Login state' mod='ps_advanced_popup'}</span>
                        <select name="target_login_state">
                            <option value="" {if !$targeting.login_state}selected{/if}>{l s='Any' mod='ps_advanced_popup'}</option>
                            <option value="guest" {if $targeting.login_state == 'guest'}selected{/if}>guest</option>
                            <option value="logged" {if $targeting.login_state == 'logged'}selected{/if}>logged</option>
                        </select>
                    </label>
                    <label><span>{l s='URL contains' mod='ps_advanced_popup'}</span><input type="text" name="target_url_contains" value="{$targeting.url_contains|escape:'html':'UTF-8'}"></label>
                    <label><span>{l s='Minimum cart total' mod='ps_advanced_popup'}</span><input type="number" step="0.01" min="0" name="target_min_cart_total" value="{$targeting.min_cart_total|escape:'html':'UTF-8'}"></label>
                </div>

                <div class="aps-grid aps-grid-2">
                    <label><span>{l s='Cart product IDs' mod='ps_advanced_popup'}</span><input type="text" name="target_cart_products" value="{$targeting.cart_products|escape:'html':'UTF-8'}" placeholder="12,18,24"></label>
                    <label><span>{l s='Cart category IDs' mod='ps_advanced_popup'}</span><input type="text" name="target_cart_categories" value="{$targeting.cart_categories|escape:'html':'UTF-8'}" placeholder="3,8,11"></label>
                </div>

                <div class="aps-grid aps-grid-2">
                    <label>
                        <span>{l s='Languages' mod='ps_advanced_popup'}</span>
                        <select name="target_languages[]" multiple>
                            {foreach from=$languages item=language}
                                <option value="{$language.iso_code|escape:'html':'UTF-8'}" {if in_array($language.iso_code, $targeting.languages)}selected{/if}>{$language.name|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </label>
                    <label>
                        <span>{l s='Currencies' mod='ps_advanced_popup'}</span>
                        <select name="target_currencies[]" multiple>
                            {foreach from=$currencies item=currency}
                                <option value="{$currency.iso_code|escape:'html':'UTF-8'}" {if in_array($currency.iso_code, $targeting.currencies)}selected{/if}>{$currency.iso_code|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </label>
                </div>
            </section>

            <section class="aps-panel aps-wizard-panel" data-panel="5">
                <div class="aps-section-heading">
                    <h3>{l s='Preview and variants' mod='ps_advanced_popup'}</h3>
                    <div class="aps-device-toggle">
                        <button type="button" class="is-active" data-preview-device="desktop">{l s='Desktop' mod='ps_advanced_popup'}</button>
                        <button type="button" data-preview-device="tablet">{l s='Tablet' mod='ps_advanced_popup'}</button>
                        <button type="button" data-preview-device="mobile">{l s='Mobile' mod='ps_advanced_popup'}</button>
                    </div>
                </div>

                <div class="aps-grid aps-grid-2">
                    <div class="aps-preview-shell" id="popup-preview" data-device="desktop">
                        <div class="aps-preview-popup">
                            <button type="button" aria-label="{l s='Close' mod='ps_advanced_popup'}">×</button>
                            <div class="aps-preview-title">{$lang_values.$default_language_id.title|default:''|escape:'html':'UTF-8'}</div>
                            <div class="aps-preview-subtitle">{$lang_values.$default_language_id.subtitle|default:''|escape:'html':'UTF-8'}</div>
                            <div class="aps-preview-content">{$lang_values.$default_language_id.content|default:''|escape:'html':'UTF-8'}</div>
                            <div class="aps-preview-coupon">{$lang_values.$default_language_id.coupon_code|default:''|escape:'html':'UTF-8'}</div>
                            <div class="aps-preview-cta">{$lang_values.$default_language_id.cta_text|default:''|escape:'html':'UTF-8'}</div>
                        </div>
                    </div>

                    <div class="aps-variant-box">
                        <label class="aps-check"><input type="checkbox" name="ab_test_enabled" value="1" {if $popup.ab_test_enabled}checked{/if}> <span>{l s='Enable A/B test' mod='ps_advanced_popup'}</span></label>
                        <div class="aps-grid aps-grid-2">
                            <label><span>{l s='Variant A name' mod='ps_advanced_popup'}</span><input type="text" name="variant_a_name" value="{$variants.a.name|escape:'html':'UTF-8'}"></label>
                            <label><span>{l s='Variant A traffic' mod='ps_advanced_popup'}</span><input type="number" name="variant_a_traffic" min="1" max="99" value="{$variants.a.traffic_percentage|intval}"></label>
                            <label><span>{l s='Variant B name' mod='ps_advanced_popup'}</span><input type="text" name="variant_b_name" value="{$variants.b.name|escape:'html':'UTF-8'}"></label>
                            <label><span>{l s='Variant B traffic' mod='ps_advanced_popup'}</span><input type="number" value="{$variants.b.traffic_percentage|intval}" disabled></label>
                        </div>

                        {foreach from=$languages item=language}
                            {assign var=id_lang value=$language.id_lang}
                            <div class="aps-variant-lang">
                                <h4>{l s='Variant copy' mod='ps_advanced_popup'} {$language.iso_code|escape:'html':'UTF-8'}</h4>
                                <div class="aps-grid aps-grid-2">
                                    <label><span>A title</span><input type="text" name="variant_a_title_{$id_lang|intval}" value="{$variants.a.lang.$id_lang.title|default:''|escape:'html':'UTF-8'}"></label>
                                    <label><span>B title</span><input type="text" name="variant_b_title_{$id_lang|intval}" value="{$variants.b.lang.$id_lang.title|default:''|escape:'html':'UTF-8'}"></label>
                                    <label><span>A CTA</span><input type="text" name="variant_a_cta_text_{$id_lang|intval}" value="{$variants.a.lang.$id_lang.cta_text|default:''|escape:'html':'UTF-8'}"></label>
                                    <label><span>B CTA</span><input type="text" name="variant_b_cta_text_{$id_lang|intval}" value="{$variants.b.lang.$id_lang.cta_text|default:''|escape:'html':'UTF-8'}"></label>
                                    <label><span>A coupon</span><input type="text" name="variant_a_coupon_code_{$id_lang|intval}" value="{$variants.a.lang.$id_lang.coupon_code|default:''|escape:'html':'UTF-8'}"></label>
                                    <label><span>B coupon</span><input type="text" name="variant_b_coupon_code_{$id_lang|intval}" value="{$variants.b.lang.$id_lang.coupon_code|default:''|escape:'html':'UTF-8'}"></label>
                                </div>
                                <div class="aps-grid aps-grid-2">
                                    <label><span>A body</span><textarea rows="3" name="variant_a_content_{$id_lang|intval}">{$variants.a.lang.$id_lang.content|default:''|escape:'html':'UTF-8'}</textarea></label>
                                    <label><span>B body</span><textarea rows="3" name="variant_b_content_{$id_lang|intval}">{$variants.b.lang.$id_lang.content|default:''|escape:'html':'UTF-8'}</textarea></label>
                                </div>
                                <input type="hidden" name="variant_a_subtitle_{$id_lang|intval}" value="{$variants.a.lang.$id_lang.subtitle|default:''|escape:'html':'UTF-8'}">
                                <input type="hidden" name="variant_b_subtitle_{$id_lang|intval}" value="{$variants.b.lang.$id_lang.subtitle|default:''|escape:'html':'UTF-8'}">
                                <input type="hidden" name="variant_a_cta_url_{$id_lang|intval}" value="{$variants.a.lang.$id_lang.cta_url|default:''|escape:'html':'UTF-8'}">
                                <input type="hidden" name="variant_b_cta_url_{$id_lang|intval}" value="{$variants.b.lang.$id_lang.cta_url|default:''|escape:'html':'UTF-8'}">
                            </div>
                        {/foreach}
                    </div>
                </div>
            </section>

            <div class="aps-form-actions">
                <button type="button" class="btn btn-default aps-prev-step">{l s='Previous' mod='ps_advanced_popup'}</button>
                <button type="button" class="btn btn-default aps-next-step">{l s='Next' mod='ps_advanced_popup'}</button>
                <button type="submit" name="saveAndStay" value="1" class="btn btn-default">
                    <i class="icon-save"></i> {l s='Save and stay' mod='ps_advanced_popup'}
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="icon-check"></i> {l s='Save popup' mod='ps_advanced_popup'}
                </button>
            </div>
        </div>
    </form>
</div>
