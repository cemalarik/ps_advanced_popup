{*
 * Popup Statistics Template
 *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-bar-chart"></i>
        {l s='Statistics for:' mod='ps_advanced_popup'} {$popup->title|escape:'html':'UTF-8'}
        <span class="badge badge-info">{l s='Last 30 days' mod='ps_advanced_popup'}</span>
        <a href="{$back_url}" class="btn btn-default pull-right">
            <i class="icon-arrow-left"></i> {l s='Back to list' mod='ps_advanced_popup'}
        </a>
    </div>

    <div class="panel-body">
        {* Summary Cards *}
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card stat-impressions">
                    <div class="stat-icon"><i class="icon-eye"></i></div>
                    <div class="stat-content">
                        <div class="stat-value">{$stats.all_time.impressions|number_format:0:',':'.'}</div>
                        <div class="stat-label">{l s='Total Impressions' mod='ps_advanced_popup'}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card stat-conversions">
                    <div class="stat-icon"><i class="icon-check"></i></div>
                    <div class="stat-content">
                        <div class="stat-value">{$stats.all_time.conversions|number_format:0:',':'.'}</div>
                        <div class="stat-label">{l s='Total Conversions' mod='ps_advanced_popup'}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card stat-rate">
                    <div class="stat-icon"><i class="icon-percent"></i></div>
                    <div class="stat-content">
                        <div class="stat-value">{$conversionRate}%</div>
                        <div class="stat-label">{l s='Conversion Rate' mod='ps_advanced_popup'}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card stat-today">
                    <div class="stat-icon"><i class="icon-calendar"></i></div>
                    <div class="stat-content">
                        <div class="stat-value">{$stats.today.impressions|number_format:0:',':'.'}</div>
                        <div class="stat-label">{l s='Today Impressions' mod='ps_advanced_popup'}</div>
                    </div>
                </div>
            </div>
        </div>

        {* Chart *}
        <div class="chart-container">
            <h4>{l s='Performance Over Time' mod='ps_advanced_popup'}</h4>
            <canvas id="statsChart" height="100"></canvas>
        </div>

        {* Period Comparison *}
        <div class="period-table">
            <h4>{l s='Period Comparison' mod='ps_advanced_popup'}</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>{l s='Period' mod='ps_advanced_popup'}</th>
                        <th>{l s='Impressions' mod='ps_advanced_popup'}</th>
                        <th>{l s='Conversions' mod='ps_advanced_popup'}</th>
                        <th>{l s='Rate' mod='ps_advanced_popup'}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{l s='Today' mod='ps_advanced_popup'}</td>
                        <td>{$stats.today.impressions|number_format:0:',':'.'}</td>
                        <td>{$stats.today.conversions|number_format:0:',':'.'}</td>
                        <td>
                            {if $stats.today.impressions > 0}
                                {($stats.today.conversions / $stats.today.impressions * 100)|number_format:2}%
                            {else}
                                0%
                            {/if}
                        </td>
                    </tr>
                    <tr>
                        <td>{l s='Last 7 Days' mod='ps_advanced_popup'}</td>
                        <td>{$stats.week.impressions|number_format:0:',':'.'}</td>
                        <td>{$stats.week.conversions|number_format:0:',':'.'}</td>
                        <td>
                            {if $stats.week.impressions > 0}
                                {($stats.week.conversions / $stats.week.impressions * 100)|number_format:2}%
                            {else}
                                0%
                            {/if}
                        </td>
                    </tr>
                    <tr>
                        <td>{l s='Last 30 Days' mod='ps_advanced_popup'}</td>
                        <td>{$stats.month.impressions|number_format:0:',':'.'}</td>
                        <td>{$stats.month.conversions|number_format:0:',':'.'}</td>
                        <td>
                            {if $stats.month.impressions > 0}
                                {($stats.month.conversions / $stats.month.impressions * 100)|number_format:2}%
                            {else}
                                0%
                            {/if}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var chartData = {$chartData nofilter};

new Chart(document.getElementById('statsChart'), {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label: '{l s='Impressions' mod='ps_advanced_popup' js=1}',
                data: chartData.impressions,
                borderColor: '#25b9d7',
                backgroundColor: 'rgba(37, 185, 215, 0.1)',
                fill: true,
                tension: 0.3
            },
            {
                label: '{l s='Conversions' mod='ps_advanced_popup' js=1}',
                data: chartData.conversions,
                borderColor: '#70b580',
                backgroundColor: 'rgba(112, 181, 128, 0.1)',
                fill: true,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
