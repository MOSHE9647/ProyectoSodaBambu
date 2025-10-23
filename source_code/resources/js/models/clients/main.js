import { showClient, deleteClient } from "./actions.js";
import { NewCrudDataTable } from '../../utils/datatables.js';
import { toggleLoadingState } from "../../utils/utils.js";
import { SwalToast } from "../../utils/sweetalert.js";

window.toggleLoadingState = toggleLoadingState;
window.deleteClient = deleteClient;
window.SwalToast = SwalToast;
window.showClient = showClient;

// Ensure the DOM is fully loaded before initializing the DataTable
$(document).ready(() => {
    // Define columns for clients table
    const columns = [
        { 
			data: 'first_name',
			name: 'first_name'
		},
        { 
			data: 'last_name',
			name: 'last_name'
		},
        { 
			data: 'email', 
			name: 'email' 
		},
        { 
			data: 'phone', 
			name: 'phone',
			render: function(data) {
				return data && data !== 'N/A' ? data : 'N/A';
			}
		},
        {
            data: 'created_at',
            name: 'created_at',
            render: function(data) {
                // Format the created_at date to a more readable format
                const date = new Date(data);
                const day = String(date.getDate()).padStart(2, '0');
                const month = date.toLocaleDateString('es-ES', { month: 'long' });
                const year = date.getFullYear();
                return `${day} de ${month} del ${year}`;
            }
        }
    ];

	/**
	 * Define actions for each client row in the DataTable.
	 */
	const actions = {
		show: { route: clientShowRoute, func: showClient, tooltip: 'Ver detalles' },
		edit: { route: clientEditRoute, func: toggleLoadingState, tooltip: 'Editar cliente' },
		delete: {
			route: clientDeleteRoute,
			tooltip: 'Eliminar cliente',
			func: deleteClient,
		}
	};

	/**
	 * Define custom buttons for the DataTable interface.
	 */
    const customButtons = [
        {
            text: 'Crear Cliente',
            href: clientCreateRoute,
            class: 'create-button btn-primary',
            icon: 'bi-person-plus-fill',
			func: toggleLoadingState,
			params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    NewCrudDataTable('clients-table', clientRoute, columns, actions, customButtons);
});