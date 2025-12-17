import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, toggleLoadingState } from "../../utils/utils.js";
import { SwalToast } from "../../utils/sweetalert.js";
import { formatDate } from '../../utils/utils.js';

// Constants
const MODEL_NAME = 'proveedor';

// Expose functions globally
window.SwalToast = SwalToast;
window.toggleLoadingState = toggleLoadingState;
window.deleteSupplier = function deleteSupplier(e) { return deleteModel(e, MODEL_NAME); };
window.showSupplier = function showSupplier(url, anchor) { return showModelInfo(url, anchor, MODEL_NAME); };

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
	// Define columns for suppliers table (only for server-side processing)
	const columns = [
		{
			data: 'name',
			name: 'name',
			render: function (data, type, row) {
				let nameText = data;
				if (row.deleted_at) {
					nameText += ' <span class="badge bg-danger">Eliminado</span>';
				}
				return nameText;
			}
		},
		{ data: 'email', name: 'email' },
		{ data: 'phone', name: 'phone' },
		{
			data: 'created_at',
			name: 'created_at',
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
		show: { route: supplierShowRoute, func: showSupplier, funcName: 'showSupplier', tooltip: 'Ver detalles' },
		edit: { route: supplierEditRoute, func: toggleLoadingState, funcName: 'toggleLoadingState', tooltip: `Editar ${MODEL_NAME}` },
		delete: {
			route: supplierDeleteRoute,
			tooltip: `Eliminar ${MODEL_NAME}`,
			func: deleteSupplier,
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
			href: supplierCreateRoute,
			class: 'create-button btn-primary',
			icon: 'bi-building-add',
			func: toggleLoadingState,
			funcName: 'toggleLoadingState',
			params: ['.create-button', 'create', true],
		}
	];

	// Initialize the CRUD DataTable
	CreateNewDataTable('suppliers-table', supplierIndexRoute, columns, actions, customButtons);
});