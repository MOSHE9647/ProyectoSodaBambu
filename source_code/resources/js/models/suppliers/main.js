import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, toggleLoadingState, formatDate } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'proveedor';

// String Constants
const BTN_CLASS_PRIMARY = 'btn-primary';

// Routes Configuration
const MODEL_ROUTES = {
	index: 	route('suppliers.index'),
	create: route('suppliers.create'),
	show: 	route('suppliers.show', { supplier: ':id' }),
	edit: 	route('suppliers.edit', { supplier: ':id' }),
	delete: route('suppliers.destroy', { supplier: ':id' }),
};

// ==================== Global Functions ====================

// Expose functions globally
window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;

// ==================== Helper Functions ====================

/**
 * Shows information for a specific supplier.
 * @param {string} url - The URL to fetch supplier information from
 * @param {HTMLElement} anchor - The anchor element for the modal
 * @returns {Promise<void>} A promise resolving when the modal is shown
 */
window.showSupplier = function (url, anchor) {
	return showModelInfo(url, anchor, MODEL_NAME);
};

/**
 * Deletes a specific supplier.
 * @param {Event} e - The event object
 * @returns {Promise<void>} A promise resolving when the supplier is deleted
 */
window.deleteSupplier = function (e) {
	return deleteModel(e, MODEL_NAME);
};

// ==================== DataTable Initialization ====================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
	// Define columns for suppliers table (only for server-side processing)
	const columns = [
		{
			data: 'name',
			name: 'name',
			// Supplier's name
		},
		{
			data: 'email',
			name: 'email',
			// Supplier's contact email
		},
		{
			data: 'phone',
			name: 'phone',
			// Supplier's contact phone
		},
		{
			data: 'created_at',
			name: 'created_at',
			// Creation date formatted as 'DD of Month of YYYY'
			render: (data) => formatDate(data),
		}
	];

	/**
	 * Define actions for each supplier row in the DataTable.
	 * @type {{
	 * 	show: { route: string, func: function(url, anchor): Promise<void>, tooltip: string },
	 * 	edit: { route: string, tooltip: string },
	 * 	delete: { route: string, tooltip: string, func: function(event): void }
	 * }}
	 */
	const actions = {
		show: { 
			route: MODEL_ROUTES.show, 
			func: window.showSupplier,
			funcName: 'showSupplier',
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
			func: window.deleteSupplier,
			funcName: 'deleteSupplier',
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
			icon: 'bi-building-add',
			func: toggleLoadingState,
			funcName: 'toggleLoadingState',
			params: ['.create-button', 'create', true],
		}
	];

	// Initialize the CRUD DataTable
	CreateNewDataTable('suppliers-table', MODEL_ROUTES.index, columns, actions, customButtons);
});