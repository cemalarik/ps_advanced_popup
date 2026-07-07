{*
 * Advanced Popup Studio dashboard.
 *}

<div class="aps-admin">
    <div class="aps-header">
        <div>
            <h2>{$dashboard_title|escape:'html':'UTF-8'}</h2>
            <p>{l s='Manage targeted popups, variants and conversion signals from one workspace.' mod='ps_advanced_popup'}</p>
        </div>
        {if $schema_ready}
            <a href="{$add_url|escape:'html':'UTF-8'}" class="btn btn-primary">
                <i class="icon-plus"></i> {l s='Create popup' mod='ps_advanced_popup'}
            </a>
        {/if}
    </div>

    {if !$schema_ready}
        <div class="alert alert-warning">
            {l s='The installed database schema belongs to an older module version. Run the module upgrade (or reset the module) to create the new tables.' mod='ps_advanced_popup'}
        </div>
    {/if}

    <div class="aps-metrics">
        <div class="aps-metric">
            <span>{l s='Impressions' mod='ps_advanced_popup'}</span>
            <strong>{$totals.impression|intval}</strong>
        </div>
        <div class="aps-metric">
            <span>{l s='Conversions' mod='ps_advanced_popup'}</span>
            <strong>{$totals.conversions|intval}</strong>
        </div>
        <div class="aps-metric">
            <span>{l s='Conversion rate' mod='ps_advanced_popup'}</span>
            <strong>{$totals.conversion_rate|escape:'html':'UTF-8'}%</strong>
        </div>
        <div class="aps-metric">
            <span>{l s='Close rate' mod='ps_advanced_popup'}</span>
            <strong>{$totals.close_rate|escape:'html':'UTF-8'}%</strong>
        </div>
    </div>

    {if $schema_ready}
        <div class="aps-section">
            <div class="aps-section-heading">
                <h3>{l s='Start from a proven template' mod='ps_advanced_popup'}</h3>
            </div>
            <div class="aps-template-grid">
                {foreach from=$templates key=template_key item=template}
                    <a class="aps-template-card" href="{$add_url|escape:'html':'UTF-8'}&template_key={$template_key|escape:'url'}">
                        <span>{$template.label|escape:'html':'UTF-8'}</span>
                        <small>{$template.goal|escape:'html':'UTF-8'} / {$template.layout|escape:'html':'UTF-8'}</small>
                    </a>
                {/foreach}
            </div>
        </div>
    {/if}

    <div class="aps-section">
        <div class="aps-section-heading">
            <h3>{l s='Campaigns' mod='ps_advanced_popup'}</h3>
            <span>{l s='Last 30 days' mod='ps_advanced_popup'}</span>
        </div>

        <div class="table-responsive-row clearfix">
            <table class="table aps-table">
                <thead>
                    <tr>
                        <th>{l s='Popup' mod='ps_advanced_popup'}</th>
                        <th>{l s='Status' mod='ps_advanced_popup'}</th>
                        <th>{l s='Trigger' mod='ps_advanced_popup'}</th>
                        <th>{l s='Targeting' mod='ps_advanced_popup'}</th>
                        <th>{l s='Impr.' mod='ps_advanced_popup'}</th>
                        <th>{l s='Conv.' mod='ps_advanced_popup'}</th>
                        <th>{l s='Rate' mod='ps_advanced_popup'}</th>
                        <th class="text-right">{l s='Actions' mod='ps_advanced_popup'}</th>
                    </tr>
                </thead>
                <tbody>
                    {if $popups|@count}
                        {foreach from=$popups item=popup}
                            <tr>
                                <td>
                                    <strong>{$popup.internal_name|escape:'html':'UTF-8'}</strong>
                                    <span class="aps-muted">{$popup.title|escape:'html':'UTF-8'}</span>
                                </td>
                                <td>
                                    {if $popup.active}
                                        <span class="badge badge-success">{l s='Live' mod='ps_advanced_popup'}</span>
                                    {else}
                                        <span class="badge badge-default">{l s='Draft' mod='ps_advanced_popup'}</span>
                                    {/if}
                                </td>
                                <td>{$popup.trigger_type|escape:'html':'UTF-8'} / {$popup.trigger_value|intval}</td>
                                <td><span class="aps-muted">{$popup.targeting_summary|escape:'html':'UTF-8'}</span></td>
                                <td>{$popup.stats.impression|intval}</td>
                                <td>{$popup.stats.conversions|intval}</td>
                                <td>{$popup.stats.conversion_rate|escape:'html':'UTF-8'}%</td>
                                <td class="text-right aps-actions">
                                    <a href="{$popup.edit_url|escape:'html':'UTF-8'}" class="btn btn-default btn-xs" title="{l s='Edit' mod='ps_advanced_popup'}">
                                        <i class="icon-pencil"></i>
                                    </a>
                                    <a href="{$popup.preview_url|escape:'html':'UTF-8'}" class="btn btn-default btn-xs" title="{l s='Preview' mod='ps_advanced_popup'}">
                                        <i class="icon-eye"></i>
                                    </a>
                                    <a href="{$popup.stats_url|escape:'html':'UTF-8'}" class="btn btn-default btn-xs" title="{l s='Stats' mod='ps_advanced_popup'}">
                                        <i class="icon-bar-chart"></i>
                                    </a>
                                    <a href="{$popup.duplicate_url|escape:'html':'UTF-8'}" class="btn btn-default btn-xs" title="{l s='Duplicate' mod='ps_advanced_popup'}">
                                        <i class="icon-copy"></i>
                                    </a>
                                    <a href="{$popup.toggle_url|escape:'html':'UTF-8'}" class="btn btn-default btn-xs" title="{l s='Toggle status' mod='ps_advanced_popup'}">
                                        <i class="icon-power-off"></i>
                                    </a>
                                    <a href="{$popup.delete_url|escape:'html':'UTF-8'}" class="btn btn-danger btn-xs aps-confirm-delete" title="{l s='Delete' mod='ps_advanced_popup'}">
                                        <i class="icon-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="8" class="text-center aps-empty">
                                {l s='No popups yet. Choose a template above to create the first campaign.' mod='ps_advanced_popup'}
                            </td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>
    </div>
</div>
