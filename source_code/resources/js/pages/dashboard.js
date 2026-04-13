$(document).ready(function () {
    function getCurrentTheme() {
        return $('html').attr('data-bs-theme') || 'light';
    }

    const dashboardData = window.DashboardData || {};
    const chartInstances = [];

    function createChart(containerSelector, labels, values, axisTitle) {
        const container = $(containerSelector)[0];

        if (!container) {
            return null;
        }

        const chart = new ApexCharts(container, {
            series: [{
                name: 'Ingresos',
                data: values
            }],
            xaxis: {
                categories: labels,
                title: { text: axisTitle }
            },
            chart: {
                type: 'area',
                height: 200,
                fontFamily: 'inherit',
                background: 'transparent',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    },
                    autoSelected: 'pan'
                },
                sparkline: { enabled: true }
            },
            theme: {
                mode: getCurrentTheme()
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                    stops: [0, 90, 100]
                }
            },
            colors: ['#198754'],
            tooltip: {
                theme: getCurrentTheme(),
                y: {
                    formatter: function (val) {
                        return '₡ ' + val.toLocaleString();
                    }
                }
            }
        });

        chart.render();

        return chart;
    }

    if (dashboardData.isAdmin) {
        const monthlyChart = createChart(
            '#chart-monthly-income',
            dashboardData.monthlySalesLabels,
            dashboardData.monthlySalesValues,
            'Días del mes'
        );

        if (monthlyChart) {
            chartInstances.push(monthlyChart);
        }
    }

    if (dashboardData.isEmployee) {
        const dailyChart = createChart(
            '#chart-daily-income',
            dashboardData.dailySalesLabels,
            dashboardData.dailySalesValues,
            'Horas del día'
        );

        if (dailyChart) {
            chartInstances.push(dailyChart);
        }
    }

    if (chartInstances.length > 0) {
        const observer = new MutationObserver(function (mutations) {
            const themeChanged = mutations.some(function (mutation) {
                return mutation.attributeName === 'data-bs-theme';
            });

            if (!themeChanged) {
                return;
            }

            const newTheme = getCurrentTheme();

            chartInstances.forEach(function (chart) {
                chart.updateOptions({
                    theme: { mode: newTheme },
                    tooltip: { theme: newTheme }
                });
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-bs-theme']
        });
    }
});