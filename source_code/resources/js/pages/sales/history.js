import { deleteModel, showModelInfo } from "../../models/actions";
import { CreateNewDataTable } from "../../utils/datatables";
import { SwalConfirmation } from "../../utils/sweetalert";
import { formatCurrency, formatDate, printReceipt, toggleLoadingState } from "../../utils/utils";
import { get } from "jquery";

// ==================== Environment Checks ====================

// Ensure jQuery is loaded
if (typeof $ === "undefined") {
	throw new Error("This script requires jQuery");
}

// ======================== Constants =========================

// Model Configuration
const MODEL_DATA = window.SalesHistoryData || [];
const MODEL_NAME = MODEL_DATA.modelName || "venta";
const MODEL_USERS = MODEL_DATA.users || [];

// String Constants
const BTN_CLASS_PRIMARY = 'btn-primary';

// Routes Configuration
const MODEL_ROUTES = {
    index: 	route('history.index'),
    show: 	route('sales.show', { sale: ':id' }),
    delete: route('sales.destroy', { sale: ':id' }),
    print:  route('receipts.show', { model: 'sales', id: ':id' }),
};

// ===================== Global Functions =====================

/**
 * Enables or disables the loading state on a generic button.
 * @param {string|HTMLElement|null} element - CSS selector or button reference.
 * @param {string} elementClass - Class prefix used to locate spinner and text elements.
 * @param {boolean} isLoading - Whether to show the loading state.
 * @returns {void}
 */
window.toggleLoadingState = toggleLoadingState;

/**
 * Shows information for a specific sale.
 * @param {string} url - The URL to fetch sale information from
 * @param {HTMLElement} anchor - The anchor element for the modal
 * @returns {Promise<void>} A promise resolving when the modal is shown
 */
window.showSaleInfo = function (url, anchor) {
    return showModelInfo(url, anchor, MODEL_NAME);
};

/**
 * Deletes a specific sale.
 * @param {Event} e - The event object
 * @returns {Promise<void>} A promise resolving when the sale is deleted
 */
window.deleteSale = function (e) {
    return deleteModel(e, MODEL_NAME);
};

/**
 * Reprints a sale receipt.
 * @param {string} url - The URL to fetch the receipt from
 * @returns {Promise<void>} A promise resolving when the receipt is printed
 */
window.reprintReceipt = function (url) {
    const confirmation = SwalConfirmation.fire({
        title: "¿Reimprimir recibo?",
        text: "¿Desea reimprimir el recibo de esta venta?",
        icon: "question",
        confirmButtonText: "Sí, reimprimir",
        cancelButtonText: "No, cancelar",
    });

    confirmation.then((result) => {
        if (result.isConfirmed) {
            return printReceipt(url);
        }
    });
};

// ===================== Helper Functions =====================

/**
 * Create an HTML badge element and return its outer HTML as a string.
 * Uses semantic "type" to build Bootstrap-like utility classes.
 *
 * @param {string} text - Text to display inside the badge.
 * @param {string} [type='secondary'] - Semantic type (e.g. 'success', 'danger').
 * @returns {string} Outer HTML of the created badge element.
 */
const createStatusBadge = (text, type = 'secondary') => {
    const badge = document.createElement('span');
    badge.className = `badge border rounded-pill text-${type}-emphasis bg-${type}-subtle px-3 py-2`;
    badge.style.minWidth = '90px';
    badge.style.minHeight = '30px';
    badge.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle'} me-2"></i> ${text}`;
    return badge.outerHTML;
};

/**
 * Map a purchase status key to a styled badge HTML string.
 *
 * @param {string|null|undefined} status - Status identifier for the purchase.
 * @returns {string} HTML string of the corresponding status badge.
 */
const getStatusBadge = (status) => {
    if (status == null) {
        return createStatusBadge('Desconocido');
    }

    switch (status) {
        case 'paid':
            return createStatusBadge('Completo', 'success');
        case 'pending':
            return createStatusBadge('Pendiente', 'warning');
        default:
            return createStatusBadge('Desconocido');
    }
};

// ================= DataTable Initialization =================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    const columns = [
		{
			data: "invoice_number",
			name: "invoice_number",
			title: "N° Factura",
			render: (data) => `<span class="font-monospace">${data}</span>`,
		},
		{
			data: "date",
			name: "date",
			title: "Fecha",
            searchable: false,
			render: (data) => formatDate(data),
		},
		{
			data: "user.name",
			name: "user.name",
			title: "Cajero",
            searchable: false,
            defaultContent: "Desconocido",
            render: (data) => data || "Desconocido",
		},
        {
            data: "total",
            name: "total",
            title: "Total",
            render: (data) => formatCurrency(data),
        },
		{
			data: "payment_status",
			name: "payment_status",
			title: "Estado de Pago",
            orderable: false,
            searchable: false,
			render: (data) => {
				return getStatusBadge(data);
			},
		},
	];

    const actions = {
		show: {
			route: MODEL_ROUTES.show,
			func: window.showSaleInfo,
			funcName: "showSaleInfo",
			tooltip: "Ver detalles",
		},
		reprint: {
			route: MODEL_ROUTES.print,
			func: window.reprintReceipt,
			funcName: "reprintReceipt",
			tooltip: "Reimprimir recibo",
		}
	};

    if (MODEL_DATA.userCanDelete) {
        actions.delete = {
			route: MODEL_ROUTES.delete,
			tooltip: `Eliminar ${MODEL_NAME}`,
			func: window.deleteSale,
			funcName: "deleteSale",
		};
    }

    /**
     * Define custom buttons for the DataTable interface.
     * - User filter dropdown to filter sales by cashier.
     * - Date filter to filter sales by date.
     * - Clear filters button.
     * 
     * @type {Array<{id?: string, type?: string, label?: string, labelIcon?: string, class?: string, wrapperClass?: string, placeholderSelected?: boolean, placeholder?: string, options?: Array<{value: string, text: string, selected?: boolean}>, text?: string, href?: string, func?: function, funcName?: string, params?: Array}>}
     */
    const customButtons = [
        {
            id: "user-filter",
            type: "select",
            label: "Cajero",
            labelIcon: "bi-person me-2",
            class: "sales-user-filter",
            wrapperClass: "mb-2 w-auto",
            placeholderSelected: true,
            placeholder: "Todos los cajeros",
            options: MODEL_USERS.map(({ id, name }) => ({
                value: id,
                text: name,
            })),
        },
        {
            id: "date-filter",
            type: "date",
            label: "Fecha",
            labelIcon: "bi-calendar me-2",
            class: "sales-date-filter",
            wrapperClass: "mb-2 w-auto",
        },
        {
            id: "clear-sales-filters",
            type: "button",
            text: "Borrar Filtros",
            icon: "bi-eraser-fill",
            class: "btn-outline-danger mb-2 d-none",
        }
    ];

    /**
     * Options for DataTable initialization, including server-side processing and custom AJAX data 
     * function to include status filter.
     * 
     * @type {{ajax: {data: function(req): void}}}
     */
	const options = {
		ajax: {
			data: (req) => {
				const getFilterValue = (selector) => {
					const el = document.getElementById(selector);
					return el ? el.value : undefined;
				};
				
				const user = getFilterValue("user-filter");
				if (user && user !== "all" && user.trim() !== "") {
					req.user = user;
				}
				
				const date = getFilterValue("date-filter");
				if (date && date.trim() !== "") {
					req.date = date;
				}
			},
		},
	};

    // Initialize the DataTable with the defined columns, actions, custom buttons, and options
    const dataTable = CreateNewDataTable(
        "sales-table", MODEL_ROUTES.index, columns,
        actions, customButtons, options
    );

    // Add event listeners for filters
    const userFilterBtn = document.getElementById("user-filter");
    const dateFilterBtn = document.getElementById("date-filter");
    const clearFiltersBtn = document.getElementById("clear-sales-filters");

    const syncClearFiltersButtonVisibility = () => {
        const userVal = userFilterBtn?.value;
        const dateVal = dateFilterBtn?.value;
        
        const isUserActive = userVal && userVal !== "all" && userVal.trim() !== "";
        const isDateActive = Boolean(dateVal);
        
        const shouldShow = isUserActive || isDateActive;
        clearFiltersBtn?.classList.toggle("d-none", !shouldShow);
    };

    const reloadTableAndSync = () => {
        syncClearFiltersButtonVisibility();
        dataTable.ajax.reload();
    };

    userFilterBtn?.addEventListener("change", reloadTableAndSync);
    dateFilterBtn?.addEventListener("change", reloadTableAndSync);

    clearFiltersBtn?.addEventListener("click", () => {
        if (userFilterBtn) userFilterBtn.value = "";
        if (dateFilterBtn) dateFilterBtn.value = "";
        reloadTableAndSync();
    });
});