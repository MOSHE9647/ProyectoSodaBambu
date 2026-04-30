import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, toggleLoadingState, formatDate, escapeHtml } from "../../utils/utils.js";
import { get } from 'jquery';

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'contrato';

// String Constants
const BTN_CLASS_PRIMARY = 'btn-primary';

// Routes Configuration
const MODEL_ROUTES = {
    index: 	route('contracts.index'),
    create: route('contracts.create'),
    show: 	route('contracts.show', { contract: ':id' }),
    edit: 	route('contracts.edit', { contract: ':id' }),
    delete: route('contracts.destroy', { contract: ':id' }),
};

// Currency Formatter for Costa Rican Colón
const CURRENCY_FORMATTER = new Intl.NumberFormat("es-CR", {
	style: "currency",
	currency: "CRC",
	maximumFractionDigits: 0,
});

// ==================== Global Functions ====================

// Expose functions globally
window.toggleLoadingState = toggleLoadingState;

// ======================= Formatters =======================

/**
 * Formats a numeric value as Costa Rican Colón currency.
 * @param {number|string} value - The value to format as currency
 * @returns {string} The formatted currency string
 */
const formatCurrency = (value) => {
    const amount = Number.parseInt(value);
        let formatted = CURRENCY_FORMATTER.format(Number.isNaN(amount) ? 0 : amount);
        formatted = formatted.replace(/^([₡])(?=\d)/, '$1 ');
        return formatted;
};

// ==================== Helper Functions ====================

/**
 * Shows information for a specific contract.
 * @param {string} url - The URL to fetch contract information from
 * @param {HTMLElement} anchor - The anchor element for the modal
 * @returns {Promise<void>} A promise resolving when the modal is shown
 */
window.showContract = function (url, anchor) {
    return showModelInfo(url, anchor, MODEL_NAME);
};

/**
 * Deletes a specific contract.
 * @param {Event} e - The event object
 * @returns {Promise<void>} A promise resolving when the contract is deleted
 */
window.deleteContract = function (e) {
    return deleteModel(e, MODEL_NAME);
};

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
    badge.className = `badge border rounded-pill text-${type}-emphasis bg-${type}-subtle p-2`;
    badge.textContent = text;
    return badge.outerHTML;
};

const createPortionsBadge = (portions) => {
    const badge = document.createElement('span');
    badge.className = `badge border text-secondary-emphasis bg-secondary-subtle px-2 py-1`;
    badge.textContent = `${portions}`;
    return badge.outerHTML;
}

/**
 * Map a contract status key to a styled badge HTML string.
 *
 * @param {string|null|undefined} status - Status identifier for the contract.
 * @returns {string} HTML string of the corresponding status badge.
 */
const getStatusBadge = (status) => {
    if (status == null) {
        return createStatusBadge('Desconocido');
    }

    switch (status) {
        case 'active':
            return createStatusBadge('Activo', 'success');
        case 'inactive':
            return createStatusBadge('Inactivo', 'warning');
        case 'expired':
            return createStatusBadge('Vencido', 'danger');
        case 'upcoming':
            return createStatusBadge('Próximo', 'info');
        default:
            return createStatusBadge('Desconocido');
    }
};

// ==================== DataTable Initialization ====================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
	// Define columns for suppliers table (only for server-side processing)
	const columns = [
		{
			data: "business_name",
			name: "business_name",
			// Contract's business name
            render: (data) => `<span class="fw-bolder">${escapeHtml(data)}</span>`
		},
		{
			data: "start_date",
			name: "start_date",
			// Contract's start date formatted as 'DD of Month of YYYY'
			render: (data) => formatDate(data),
		},
		{
			data: "end_date",
			name: "end_date",
			// Contract's end date formatted as 'DD of Month of YYYY'
			render: (data) => formatDate(data),
		},
		{
			data: "portions_per_day",
			name: "portions_per_day",
			searchable: false,
			className: "dt-left",
			type: "string",
			// Number of portions per day defined in the contract
			render: (data) => createPortionsBadge(data),
		},
		{
			data: "total_value",
			name: "total_value",
			searchable: false,
			// Total contract value formatted as Costa Rican Colón currency
			render: (data) => formatCurrency(data),
		},
		{
			data: "status",
			name: "status",
			searchable: false,
			orderable: false,
			// Contract status displayed as a badge with appropriate styling
			render: (data) => {
				return getStatusBadge(data);
			},
		},
	];

	/**
	 * Define actions for each contract row in the DataTable.
	 * @type {{
	 * 	show: { route: string, func: function(url, anchor): Promise<void>, tooltip: string },
	 * 	edit: { route: string, tooltip: string },
	 * 	delete: { route: string, tooltip: string, func: function(event): void }
	 * }}
	 */
	const actions = {
		show: {
			route: MODEL_ROUTES.show,
			func: window.showContract,
			funcName: "showContract",
			tooltip: "Ver detalles",
		},
		edit: {
			route: MODEL_ROUTES.edit,
			func: toggleLoadingState,
			funcName: "toggleLoadingState",
			tooltip: `Editar ${MODEL_NAME}`,
		},
		delete: {
			route: MODEL_ROUTES.delete,
			tooltip: `Eliminar ${MODEL_NAME}`,
			func: window.deleteContract,
			funcName: "deleteContract",
		},
	};

	// Status filter options for the custom dropdown button
	const STATUS_OPTIONS = [
		{ value: "all", text: "Todos", selected: true },
		{ value: "active", text: "Activo" },
		{ value: "inactive", text: "Inactivo" },
		{ value: "expired", text: "Vencido" },
		{ value: "upcoming", text: "Próximo" },
	];

	/**
	 * Define custom buttons for the DataTable interface.
	 * - Status filter dropdown to filter contracts by their status.
     * - Create button to navigate to the contract creation page with loading state.
     * 
     * @type {Array<{id?: string, type?: string, label?: string, labelIcon?: string, class?: string, wrapperClass?: string, placeholderSelected?: boolean, placeholder?: string, options?: Array<{value: string, text: string, selected?: boolean}>, text?: string, href?: string, func?: function, funcName?: string, params?: Array}>}
	 */
    const customButtons = [
		// Status filter dropdown
		{
			id: "status-filter",
			type: "select",
			label: "Estado",
			labelIcon: "bi-funnel me-2",
			class: "contract-status-filter",
			wrapperClass: "mb-2 w-auto",
			placeholderSelected: true,
			placeholder: "Seleccione un estado",
			options: STATUS_OPTIONS,
		},
		// Contract create button
		{
			text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
			href: MODEL_ROUTES.create,
			class: `create-button mb-2 ${BTN_CLASS_PRIMARY}`,
			icon: "bi-plus-circle",
			func: toggleLoadingState,
			funcName: "toggleLoadingState",
			params: [".create-button", "create", true],
		},
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
				// Helper function to get the value of a filter input by its ID
				const getFilterValue = (selector) => {
					const el = document.getElementById(selector);
					return el ? el.value : undefined;
				};
				// Include the selected status filter value in the AJAX request parameters if it's not "all"
				const status = getFilterValue("status-filter");
				if (status && status !== "all") {
					req.status = status;
				}
			},
		},
	};

	// Initialize the DataTable with the defined columns, actions, custom buttons, and options
	const dataTable = CreateNewDataTable(
		"contracts-table", MODEL_ROUTES.index, columns,
		actions, customButtons, options
	);

	// Add event listener to the status filter dropdown to reload the DataTable when the filter changes
	document.getElementById("status-filter")?.addEventListener("change", () => {
		dataTable.ajax.reload();
	});
});