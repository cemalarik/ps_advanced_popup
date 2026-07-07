(function () {
    'use strict';

    function drawLine(ctx, points, color) {
        if (!points.length) {
            return;
        }
        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        points.forEach(function (point, index) {
            if (index === 0) {
                ctx.moveTo(point.x, point.y);
            } else {
                ctx.lineTo(point.x, point.y);
            }
        });
        ctx.stroke();
    }

    window.ApsChartLite = {
        draw: function (canvas, data) {
            if (!canvas || !canvas.getContext || !data) {
                return;
            }

            var ctx = canvas.getContext('2d');
            var width = canvas.clientWidth || 760;
            var height = canvas.height || 110;
            var ratio = window.devicePixelRatio || 1;
            canvas.width = width * ratio;
            canvas.height = height * ratio;
            ctx.scale(ratio, ratio);

            ctx.clearRect(0, 0, width, height);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);

            var series = [
                { key: 'impression', color: '#25b9d7' },
                { key: 'conversions', color: '#16a34a' },
                { key: 'close', color: '#64748b' }
            ];
            var values = [];
            series.forEach(function (item) {
                values = values.concat(data[item.key] || []);
            });

            var max = Math.max.apply(Math, values.concat([1]));
            var padding = 20;
            var count = Math.max((data.labels || []).length - 1, 1);

            series.forEach(function (item) {
                var points = (data[item.key] || []).map(function (value, index) {
                    return {
                        x: padding + ((width - padding * 2) / count) * index,
                        y: height - padding - ((height - padding * 2) * (value / max))
                    };
                });
                drawLine(ctx, points, item.color);
            });
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        var canvas = document.getElementById('apsStatsChart');
        if (canvas && window.apsStatsChartData) {
            window.ApsChartLite.draw(canvas, window.apsStatsChartData);
        }
    });
})();
