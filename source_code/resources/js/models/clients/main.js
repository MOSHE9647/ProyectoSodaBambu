import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatDate, toggleLoadingState } from "../../utils/utils.js";
import { SwalToast } from "../../utils/sweetalert.js";

// Constants
const MODEL_NAME = 'cliente';

// Expose functions globally
window.SwalToast = SwalToast;
window.toggleLoadingState = toggleLoadingState;
window.deleteClient = function deleteClient(e) { return deleteModel(e, MODEL_NAME); };
window.showClient = function showClient(url, anchor) { return showModelInfo(url, anchor, MODEL_NAME); };

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    // Define columns for clients table
    const columns = [
        { data: 'first_name', name: 'first_name' },
        { data: 'last_name', name: 'last_name' },
        { data: 'email', name: 'email' },
        { 
			data: 'phone', 
			name: 'phone',
			render: (data) => data ? data : 'N/A',
		},
        {
            data: 'created_at',
            name: 'created_at',
            render: (data) => formatDate(data),
        }
    ];

	/**
	 * Define actions for each client row in the DataTable.
	 */
	const actions = {
		show: { route: clientShowRoute, func: showClient, tooltip: 'Ver detalles' },
		edit: { route: clientEditRoute, func: toggleLoadingState, tooltip: `Editar ${MODEL_NAME}` },
		delete: {
			route: clientDeleteRoute,
			tooltip: `Eliminar ${MODEL_NAME}`,
			func: deleteClient,
		}
	};

	/**
	 * Define custom buttons for the DataTable interface.
	 */
    const customButtons = [
        {
            text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
            href: clientCreateRoute,
            class: 'create-button btn-primary',
            icon: 'bi-person-plus-fill',
			func: toggleLoadingState,
			params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    CreateNewDataTable('clients-table', clientRoute, columns, actions, customButtons);
});