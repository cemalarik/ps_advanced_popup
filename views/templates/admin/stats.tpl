{*
 * Advanced Popup Studio statistics.
 *}

<div class="aps-admin">
    <div class="aps-header">
        <div>
            <h2>{l s='Statistics' mod='ps_advanced_popup'}: {$popup.internal_name|escape:'html':'UTF-8'}</h2>
            <p>{l s='Anonymous event analytics for the last 30 days.' mod='ps_advanced_popup'}</p>
        </div>
        <a href="{$back_url|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="icon-arrow-left"></i> {l s='Back to dashboard' mod='ps_advanced_popup'}
        </a>
    </div>

    <div class="aps-metrics">
        <div class="aps-metric"><span>{l s='Impressions' mod='ps_advanced_popup'}</span><strong>{$stats.overview.impression|intval}</strong></div>
        <div class="aps-metric"><span>{l s='Conversions' mod='ps_advanced_popup'}</span><strong>{$stats.overview.conversions|intval}</strong></div>
        <div class="aps-metric"><span>{l s='Conversion rate' mod='ps_advanced_popup'}</span><strong>{$stats.overview.conversion_rate|escape:'html':'UTF-8'}%</strong></div>
        <div class="aps-metric"><span>{l s='Close rate' mod='ps_advanced_popup'}</span><strong>{$stats.overview.close_rate|escape:'html':'UTF-8'}%</strong></div>
    </div>

    <div class="aps-section">
        <div class="aps-section-heading">
            <h3>{l s='Performance over time' mod='ps_advanced_popup'}</h3>
        </div>
        <canvas id="apsStatsChart" class="aps-chart" height="110"></canvas>
    </div>

    <div class="aps-grid aps-grid-3">
        <div class="aps-section">
            <div class="aps-section-heading"><h3>{l s='Devices' mod='ps_advanced_popup'}</h3></div>
            <table class="table aps-table">
                <tbody>
                    {foreach from=$stats.devices key=device item=deviceStats}
                        <tr>
                            <td>{$device|escape:'html':'UTF-8'}</td>
                            <td>{$deviceStats.impression|intval}</td>
                            <td>{$deviceStats.conversion_rate|escape:'html':'UTF-8'}%</td>
                        </tr>
                    {foreachelse}
                        <tr><td>{l s='No device data yet.' mod='ps_advanced_popup'}</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>

        <div class="aps-section">
            <div class="aps-section-heading"><h3>{l s='Pages' mod='ps_advanced_popup'}</h3></div>
            <table class="table aps-table">
                <tbody>
                    {foreach from=$stats.pages key=page item=pageStats}
                        <tr>
                            <td>{$page|escape:'html':'UTF-8'}</td>
                            <td>{$pageStats.impression|intval}</td>
                            <td>{$pageStats.conversion_rate|escape:'html':'UTF-8'}%</td>
                        </tr>
                    {foreachelse}
                        <tr><td>{l s='No page data yet.' mod='ps_advanced_popup'}</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>

        <div class="aps-section">
            <div class="aps-section-heading"><h3>{l s='Variants' mod='ps_advanced_popup'}</h3></div>
            <table class="table aps-table">
                <tbody>
                    {foreach from=$stats.variants item=variant}
                        <tr>
                            <td>{$variant.name|escape:'html':'UTF-8'}</td>
                            <td>{$variant.stats.impression|intval}</td>
                            <td>{$variant.stats.conversion_rate|escape:'html':'UTF-8'}%</td>
                        </tr>
                    {foreachelse}
                        <tr><td>{l s='No variant data yet.' mod='ps_advanced_popup'}</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
window.apsStatsChartData = {$chartData nofilter};
</script>
