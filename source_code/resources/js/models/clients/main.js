import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatDate, toggleLoadingState } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'cliente';

// String Constants
const BTN_CLASS_PRIMARY = 'btn-primary';

// Routes Configuration
const MODEL_ROUTES = {
    index: 	route('clients.index'),
    create: route('clients.create'),
    show: 	route('clients.show', { client: ':id' }),
    edit: 	route('clients.edit', { client: ':id' }),
    delete: route('clients.destroy', { client: ':id' }),
};

// ==================== Global Functions ====================

// Expose functions globally
window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;
window.deleteClient = function deleteClient(e) { return deleteModel(e, MODEL_NAME); };
window.showClient = function showClient(url, anchor) { return showModelInfo(url, anchor, MODEL_NAME); };

// ==================== DataTable Initialization ====================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    // Define columns for clients table (only for server-side processing)
    const columns = [
        { 
            data: 'first_name', 
            name: 'first_name'
            // Nombre del cliente
        },
        { 
            data: 'last_name', 
            name: 'last_name'
            // Apellido del cliente
        },
        { 
            data: 'email', 
            name: 'email'
            // Correo electrónico del cliente
        },
        { 
			data: 'phone', 
			name: 'phone',
			// Teléfono de contacto (N/A si no está disponible)
			render: (data) => data ? data : 'N/A',
		},
        {
            data: 'created_at',
            name: 'created_at',
            // Fecha de creación formateada como 'DD de Month del YYYY'
            render: (data) => formatDate(data),
        }
    ];

	/**
	 * Define actions for each client row in the DataTable.
	 * @type {{
	 * 	show: { route: string, func: function(url, anchor): Promise<void>, tooltip: string },
	 * 	edit: { route: string, tooltip: string },
	 * 	delete: { route: string, tooltip: string, func: function(event): void }
	 * }}
	 */
	const actions = {
		show: { 
			route: MODEL_ROUTES.show, 
			func: showClient, 
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
			func: deleteClient,
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
            icon: 'bi-person-plus-fill',
			func: toggleLoadingState,
			params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    CreateNewDataTable('clients-table', MODEL_ROUTES.index, columns, actions, customButtons);
});