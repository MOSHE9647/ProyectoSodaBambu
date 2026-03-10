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

// ==================== Helper Functions ====================

/**
 * Shows information for a specific client.
 * @param {string} url - The URL to fetch client information from
 * @param {HTMLElement} anchor - The anchor element for the modal
 * @returns {Promise<void>} A promise resolving when the modal is shown
 */
window.showClient = function (url, anchor) {
    return showModelInfo(url, anchor, MODEL_NAME);
};

/**
 * Deletes a specific client.
 * @param {Event} e - The event object
 * @returns {Promise<void>} A promise resolving when the client is deleted
 */
window.deleteClient = function (e) {
    return deleteModel(e, MODEL_NAME);
};

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
            func: window.showClient,
            funcName: 'showClient',
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
            func: window.deleteClient,
            funcName: 'deleteClient',
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
			funcName: 'toggleLoadingState',
			params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    CreateNewDataTable('clients-table', MODEL_ROUTES.index, columns, actions, customButtons);
});