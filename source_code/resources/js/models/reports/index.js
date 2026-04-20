import '../../config/datatables.js';

$(document).ready(function () {
    function getCurrentTheme() {
        return $('html').attr('data-bs-theme') || 'light';
    }

    const reportsData = window.ReportsData || {};
    const chartInstances = [];

    function initStaticReportTable(tableId) {
        const table = $(`#${tableId}`);

        if (!table.length || $.fn.dataTable.isDataTable(table[0])) {
            return null;
        }

        const bodyRows = table.find('tbody tr');
        const hasOnlyPlaceholderRow = bodyRows.length === 1 && bodyRows.first().find('td[colspan]').length > 0;

        // Prevent DataTables warnings when an empty-state row uses colspan.
        if (hasOnlyPlaceholderRow) {
            return null;
        }

        const dataTable = table.DataTable({
            order: [],
        });

        const searchBox = $('#customSearchBox');
        if (searchBox.length) {
            searchBox.off('keyup.reportsTable').on('keyup.reportsTable', function () {
                dataTable.search(this.value).draw();
            });
        }

        return dataTable;
    }

    function createChart(containerSelector, labels, values, axisTitle) {
        const container = $(containerSelector)[0];

        if (!container) {
            return null;
        }

        const chart = new ApexCharts(container, {
            series: [{
                name: 'Ingresos',
                data: values,
            }],
            xaxis: {
                categories: labels,
                title: { text: axisTitle },
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
                        reset: true,
                    },
                    autoSelected: 'pan',
                },
                sparkline: { enabled: true },
            },
            theme: {
                mode: getCurrentTheme(),
            },
            stroke: {
                curve: 'smooth',
                width: 2,
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                    stops: [0, 90, 100],
                },
            },
            colors: ['#198754'],
            tooltip: {
                theme: getCurrentTheme(),
                y: {
                    formatter: function (val) {
                        return '₡ ' + val.toLocaleString();
                    },
                },
            },
        });

        chart.render();

        return chart;
    }

    if (reportsData.sales) {
        const salesChart = createChart(
            reportsData.sales.container,
            reportsData.sales.labels,
            reportsData.sales.values,
            reportsData.sales.axisTitle
        );

        if (salesChart) {
            chartInstances.push(salesChart);
        }
    }

    if (reportsData.products) {
        const productsChart = createChart(
            reportsData.products.container,
            reportsData.products.labels,
            reportsData.products.values,
            reportsData.products.axisTitle
        );

        if (productsChart) {
            chartInstances.push(productsChart);
        }
    }

    if (document.getElementById('sales-report-table')) {
        initStaticReportTable('sales-report-table');
    }

    if (document.getElementById('products-report-table')) {
        initStaticReportTable('products-report-table');
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
                    tooltip: { theme: newTheme },
                });
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-bs-theme'],
        });
    }
});
