import { createIncomeAreaChart, observeThemeChanges } from '../utils/charts.js';

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

    if (chartInstances.length > 0) {
        observeThemeChanges(chartInstances);
    }
});