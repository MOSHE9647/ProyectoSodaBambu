import { CreateNewDataTable } from "../../utils/datatables";
import { formatCurrency, formatDate } from "../../utils/utils";
import { createIncomeAreaChart, createTopProductsChart, observeThemeChanges } from "../../utils/charts";

// ==================== Environment Checks ====================

// Ensure jQuery is loaded
if (typeof $ === "undefined") {
	throw new Error("This script requires jQuery");
}

// ======================== Constants =========================

// Routes Configuration
const MODEL_ROUTES = {
    index: route('reports'),
};

// Global variables to hold chart instances for later updates
let salesChartInstance = null;
let productsChartInstance = null;

// ===================== Helper Functions =====================

/**
 * Binds change events to the period filter radio buttons and manages the visibility/state of custom date inputs.
 * @param {string} radioGroupId - The ID of the container holding the period filter radio buttons.
 * @param {string} customDatesClass - The class of the container holding the custom date inputs.
 * @param {string} clearBtnId - The ID of the button used to clear custom date inputs.
 * @param {string} tableId - The ID of the DataTable to reload when filters change.
 * @returns {void}
 */
const bindPeriodFilterChange = (radioGroupId, customDatesClass, clearBtnId, tableId) => {
    const radioGroup = document.getElementById(radioGroupId);
    const $radioButtons = $(`#${radioGroupId} input[type="radio"][name="period"]`);
    if ($radioButtons.length === 0 || !radioGroup) return;

    $radioButtons.on('change', function() {
        if (this.value !== "custom") {
            $(`.${customDatesClass}`).find("input").val("");
            $(`.${customDatesClass}`).find("input").prop("disabled", true);
            $(`.${customDatesClass}`).addClass("d-none");
            $(`#${clearBtnId}`).prop('disabled', true);
        } else {
            $(`.${customDatesClass}`).removeClass("d-none");
            $(`.${customDatesClass}`).find("input").prop("disabled", false);
        }

        $radioButtons.each(function () {
            const $label = $(`label[for="${this.id}"]`);
            $label.toggleClass('active', this.checked);
        });

        if (this.value !== "custom") {
            $(`#${tableId}`).DataTable().ajax.reload();
        }
    });
};

/**
 * Binds change events to the custom date inputs and reloads the DataTable when dates change.
 * @param {string} startId - The ID of the start date input.
 * @param {string} endId - The ID of the end date input.
 * @param {string} clearBtnId - The ID of the button used to clear custom date inputs.
 * @param {string} tableId - The ID of the DataTable to reload when dates change.
 * @returns {void}
 */
const bindCustomDateChange = (startId, endId, clearBtnId, tableId) => {
    const $inputs = $(`#${startId}, #${endId}`);
    if ($inputs.length === 0) return;

    $inputs.on('change', function() {
        const startDate = $(`#${startId}`).val();
        const endDate = $(`#${endId}`).val();
        $(`#${clearBtnId}`).prop('disabled', !(startDate || endDate));

        if (startDate && endDate) {
            $(`#${tableId}`).DataTable().ajax.reload();
        }
    });
};

/**
 * Binds a click event to the clear button for custom date inputs and reloads the DataTable when clicked.
 * @param {string} clearBtnId - The ID of the button used to clear custom date inputs.
 * @param {string} startId - The ID of the start date input.
 * @param {string} endId - The ID of the end date input.
 * @param {string} tableId - The ID of the DataTable to reload when dates change.
 * @returns {void}
 */
const clearCustomDates = (clearBtnId, startId, endId, tableId) => {
    const $button = $(`#${clearBtnId}`);
    if ($button.length === 0) return;

    $button.on('click', function() {
        $(`#${startId}, #${endId}`).val('');
        this.disabled = true;
        $(`#${tableId}`).DataTable().ajax.reload();
    });
};

/**
 * Binds change events to multiple select elements and reloads the DataTable when any of them change.
 * @param {string[]} selectors - An array of CSS selectors for the select elements.
 * @param {string} tableId - The ID of the DataTable to reload when selects change.
 * @returns {void}
 */
const bindAutoSubmitSelects = (selectors, tableId) => {
    if (!selectors || selectors.length === 0) return;
    $(selectors.join(', ')).on('change', function() {
        $(`#${tableId}`).DataTable().ajax.reload();
    });
};

// ===================== DataTables =====================

/**
 * Initializes the DataTable for the sales report section, sets up AJAX data fetching with filters, 
 * and connects it to the sales income area chart for dynamic updates.
 * @returns {void}
 */
const initSalesReportDataTable = () => {
    const tableId = 'sales-report-table';
    if (!$(`#${tableId}`).length) return;

    const columns = [
		{ data: "date", name: "date", render: (data) => formatDate(data) },
		{ data: "orders", name: "orders", type: "string" },
		{ data: "income", name: "income", render: (data) => formatCurrency(data) },
		{ data: "avg_ticket", name: "avg_ticket", render: (data) => formatCurrency(data) },
	];

    const options = {
        ajax: {
            data: (req) => {
                const getFilterValue = (selector) => document.getElementById(selector)?.value;
                const period = document.querySelector('#sales-period-filter input[name="period"]:checked')?.value || "month";
                
                req.section = 'sales';
                req.period = period;

                if (period === "custom") {
                    const startDate = getFilterValue("sales-filter-start-date");
                    const endDate = getFilterValue("sales-filter-end-date");
                    if (startDate) req.start_date = startDate;
                    if (endDate) req.end_date = endDate;
                }
            },
        },
        layout: { topStart: null },
        order: [[0, 'desc']]
    };

    CreateNewDataTable(tableId, MODEL_ROUTES.index, columns, {}, [], options);

    // Connects the sales income area chart to the data obtained by DataTables and updates it dynamically on each AJAX response
    $(`#${tableId}`).on('xhr.dt', function (e, settings, json) {
        if (json && json.data) {
            // Reverse the array so the chart flows chronologically from left to right
            const chartData = [...json.data].reverse();
            const labels = chartData.map((item) => formatDate(item.date));
            const values = chartData.map(item => item.income);

            if (salesChartInstance) {
                // Update existing chart with fluid animation
                salesChartInstance.updateSeries([{ name: 'Ingresos', data: values }]);
                salesChartInstance.updateOptions({ xaxis: { categories: labels } });
            } else {
                // Instantiate chart for the first time if it doesn't exist yet
                salesChartInstance = createIncomeAreaChart({
                    container: '#chart-sales-income',
                    labels: labels,
                    values: values,
                    axisTitle: 'Fechas'
                });
                
                if (salesChartInstance) {
                    observeThemeChanges([salesChartInstance]);
                }
            }
        }
    });

    // Bind filter events for the sales report section
    bindPeriodFilterChange('sales-period-filter', 'sales-report-custom-dates', 'clear-sales-report-custom-dates', tableId);
    bindCustomDateChange('sales-filter-start-date', 'sales-filter-end-date', 'clear-sales-report-custom-dates', tableId);
    clearCustomDates('clear-sales-report-custom-dates', 'sales-filter-start-date', 'sales-filter-end-date', tableId);
};

/**
 * Initializes the DataTable for the products report section, sets up AJAX data fetching with filters,
 * and connects it to the top selling products bar chart for dynamic updates.
 * @returns {void}
 */
const initProductsReportDataTable = () => {
    const tableId = 'products-report-table';
    if (!$(`#${tableId}`).length) return;

    const columns = [
        { data: "product_name", name: "product_name" },
        { data: "category_name", name: "category_name" },
        { data: "product_type_label", name: "product_type_label", orderable: false },
        { data: "sold_quantity", name: "sold_quantity", type: "string" },
        { data: "income", name: "income", render: (data) => formatCurrency(data) },
        { data: "total_percent", name: "total_percent", render: (data) => `${data}%`, orderable: false },
    ];

    const options = {
        ajax: {
            data: (req) => {
                const getFilterValue = (selector) => document.getElementById(selector)?.value;
                const period = document.querySelector('#products-period-filter input[name="period"]:checked')?.value || "month";
                
                req.section = 'products';
                req.period = period;
                
                const productType = getFilterValue("product_type");
                if (productType) req.product_type = productType;
                
                const categoryId = getFilterValue("category_id");
                if (categoryId) req.category_id = categoryId;

                if (period === "custom") {
                    const startDate = getFilterValue("products-filter-start-date");
                    const endDate = getFilterValue("products-filter-end-date");
                    if (startDate) req.start_date = startDate;
                    if (endDate) req.end_date = endDate;
                }
            },
        },
        layout: { topStart: null }
    };

    CreateNewDataTable(tableId, MODEL_ROUTES.index, columns, {}, [], options);

    // Connect the products chart to the data obtained by DataTables
    $(`#${tableId}`).on('xhr.dt', function (e, settings, json) {
        if (json && json.data) {
            // Take only the top 10 to prevent the bar chart from becoming visually saturated
            const chartData = json.data.slice(0, 10);
            const labels = chartData.map(item => item.product_name);
            const values = chartData.map(item => item.sold_quantity);

            // Store formatted income values in a global variable so it can be accessed in the tooltip formatter of the chart options. 
            // This is done to avoid coupling the chart configuration with the raw data structure and allows us to keep using DataTables' 
            // AJAX response directly for both the table and the chart without needing additional transformations or state management. 
            // The tooltip formatter can then access this global variable to display the formatted revenue for each product when hovering over the bars in the chart.
            window.DashboardData = window.DashboardData || {};
            window.DashboardData.topSellingProducts = chartData.map(item => ({ revenue: formatCurrency(item.income, false) }));

            if (productsChartInstance) {
                // Update existing chart with fluid animation
                productsChartInstance.updateSeries([{ name: 'Unidades Vendidas', data: values }]);
                productsChartInstance.updateOptions({ xaxis: { categories: labels } });
            } else {
                // Instantiate chart for the first time if it doesn't exist yet
                productsChartInstance = createTopProductsChart({
                    container: '#chart-products-sold',
                    labels: labels,
                    values: values,
                    theme: document.documentElement.getAttribute('data-bs-theme') || 'light'
                });

                if (productsChartInstance) {
                    observeThemeChanges([productsChartInstance]);
                }
            }
        }
    });

    // Bind filter events for the products report section
    bindPeriodFilterChange('products-period-filter', 'products-report-custom-dates', 'clear-products-custom-dates', tableId);
    bindCustomDateChange('products-filter-start-date', 'products-filter-end-date', 'clear-products-custom-dates', tableId);
    clearCustomDates('clear-products-custom-dates', 'products-filter-start-date', 'products-filter-end-date', tableId);
    bindAutoSubmitSelects(['#product_type', '#category_id'], tableId);
};

$(() => {
    initSalesReportDataTable();
    initProductsReportDataTable();
});