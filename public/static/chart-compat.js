(function(root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root) root.AppChartConfig = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function() {
    function major(chart) {
        const value = Number.parseInt(String(chart && chart.version || '').split('.')[0], 10);
        if (value !== 2 && value !== 3 && value !== 4) {
            throw new Error('Unsupported Chart.js version: ' + String(chart && chart.version));
        }
        return value;
    }

    function lineOptions(chart, settings) {
        settings = settings || {};
        const reverseX = settings.reverseX === true;
        const yStepSize = settings.yStepSize;
        const transparent = 'rgba(0,0,0,0.0)';
        if (major(chart) === 2) {
            return {
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: { intersect: false },
                hover: { intersect: true },
                plugins: { filler: { propagate: false } },
                scales: {
                    xAxes: [{ reverse: reverseX, gridLines: { color: transparent } }],
                    yAxes: [{ ticks: { stepSize: yStepSize }, display: true, borderDash: [3, 3], gridLines: { color: transparent } }]
                }
            };
        }
        return {
            maintainAspectRatio: false,
            interaction: { intersect: true },
            plugins: {
                legend: { display: false },
                tooltip: { intersect: false },
                filler: { propagate: false }
            },
            scales: {
                x: { reverse: reverseX, grid: { color: transparent } },
                y: { ticks: { stepSize: yStepSize }, display: true, grid: { color: transparent } }
            }
        };
    }

    function doughnutOptions(chart, settings) {
        settings = settings || {};
        const display = settings.legendDisplay !== false;
        const position = settings.legendPosition || 'bottom';
        const cutout = settings.cutout || 75;
        const common = {
            responsive: !(typeof window !== 'undefined' && window.MSInputMethodContext),
            maintainAspectRatio: false
        };
        if (major(chart) === 2) {
            common.legend = { display: display, position: position };
            common.cutoutPercentage = cutout;
        } else {
            common.plugins = { legend: { display: display, position: position } };
            common.cutout = String(cutout) + '%';
        }
        return common;
    }

    return { lineOptions, doughnutOptions };
});
