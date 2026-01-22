import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, toggleLoadingState, formatDate } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'proveedor';

// String Constants
const BTN_CLASS_PRIMARY = 'btn-primary';
const BADGE_DELETED = '<span class="badge bg-danger">Eliminado</span>';

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
window.deleteSupplier = function deleteSupplier(e) { return deleteModel(e, MODEL_NAME); };
window.showSupplier = function showSupplier(url, anchor) { return showModelInfo(url, anchor, MODEL_NAME); };

// ==================== Helper Functions ====================

/**
 * Renders the supplier name with a deleted badge if applicable.
 * @param {string} data - The supplier name
 * @param {Object} row - The full row data
 * @returns {string} The formatted name HTML
 */
function renderSupplierName(data, row) {
    if (row?.deleted_at) {
        return `${data} ${BADGE_DELETED}`;
    }
    return data;
}

// ==================== DataTable Initialization ====================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
	// Define columns for suppliers table (only for server-side processing)
	const columns = [
		{
			data: 'name',
			name: 'name',
			// Nombre del proveedor con badge si está marcado como eliminado
			render: (data, _type, row) => renderSupplierName(data, row)
		},
		{
			data: 'email',
			name: 'email',
			// Correo electrónico del proveedor
		},
		{
			data: 'phone',
			name: 'phone'
			// Teléfono de contacto del proveedor
		},
		{
			data: 'created_at',
			name: 'created_at',
			// Fecha de creación formateada como 'DD de Month del YYYY'
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
			func: showSupplier, 
			tooltip: 'Ver detalles' 
		},
		edit: { 
			route: MODEL_ROUTES.edit, 
			func: toggleLoadingState, 
			tooltip: `Editar ${MODEL_NAME}` 
		},
		delete: {
			route: MODEL_ROUTES.delete,
			tooltip: `Eliminar ${MODEL_NAME}`,
			func: deleteSupplier,
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
			params: ['.create-button', 'create', true],
		}
	];

	// Initialize the CRUD DataTable
	CreateNewDataTable('suppliers-table', MODEL_ROUTES.index, columns, actions, customButtons);
});