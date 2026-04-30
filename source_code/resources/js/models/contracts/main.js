import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, toggleLoadingState, formatDate } from "../../utils/utils.js";

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

// ==================== DataTable Initialization ====================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    // Define columns for suppliers table (only for server-side processing)
    const columns = [
		{
			data: "business_name",
			name: "business_name",
			// Contract's business name
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
            className: "dt-left",
            type: "string",
            // Number of portions per day defined in the contract
		},
        {
            data: "total_value",
            name: "total_value",
            // Total contract value formatted as Costa Rican Colón currency
            render: (data) => formatCurrency(data),
        }
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
            funcName: 'showContract',
            tooltip: 'Ver detalles' 
        },
        edit: { 
            route: MODEL_ROUTES.edit, 
            func: toggleLoadingState, 
            funcName: 'toggleLoadingState',
            tooltip: `Editar ${MODEL_NAME}` 
        },
        delete: {
            route: MODEL_ROUTES.delete,
            tooltip: `Eliminar ${MODEL_NAME}`,
            func: window.deleteContract,
            funcName: 'deleteContract',
        }
    };

    /**
     * Define custom buttons for the DataTable interface.
     * @type {[
     * 	{ text: string, href: string, class: string, icon: string }
     * ]}
     */
    const customButtons = [
        {
            text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
            href: MODEL_ROUTES.create,
            class: `create-button ${BTN_CLASS_PRIMARY}`,
            icon: 'bi-plus-circle',
            func: toggleLoadingState,
            funcName: 'toggleLoadingState',
            params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    CreateNewDataTable('contracts-table', MODEL_ROUTES.index, columns, actions, customButtons);
});