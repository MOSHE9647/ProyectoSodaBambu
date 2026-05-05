import { createIncomeAreaChart, createTopProductsChart, observeThemeChanges, getCurrentTheme } from '../utils/charts.js';

$(document).ready(function () {
    const dashboardData = window.DashboardData || {};
    const chartInstances = [];

    if (dashboardData.isAdmin) {
        const monthlyChart = createIncomeAreaChart({
            container: '#chart-monthly-income',
            labels: dashboardData.monthlySalesLabels,
            values: dashboardData.monthlySalesValues,
            axisTitle: 'Días del mes'
        });

        if (monthlyChart) {
            chartInstances.push(monthlyChart);
        }
    }

    if (dashboardData.isEmployee) {
        const dailyChart = createIncomeAreaChart({
            container: '#chart-daily-income',
            labels: dashboardData.dailySalesLabels,
            values: dashboardData.dailySalesValues,
            axisTitle: 'Horas del día'
        });

        if (dailyChart) {
            chartInstances.push(dailyChart);
        }
    }

    if (dashboardData.topSellingProducts && dashboardData.topSellingProducts.length > 0) {
        const topProductsChart = createTopProductsChart({
            container: '#chart-top-products',
            labels: dashboardData.topSellingProducts.map(p => p.name),
            values: dashboardData.topSellingProducts.map(p => p.volume),
            theme: getCurrentTheme()
        });

        if (topProductsChart) {
            chartInstances.push(topProductsChart);
        }
    }

    if (chartInstances.length > 0) {
        observeThemeChanges(chartInstances);
    }
});