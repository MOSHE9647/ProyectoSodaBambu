import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatDate, toggleLoadingState } from "../../utils/utils.js";

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'usuario';
const MODEL_DATA = window.UsersAppData || {};

// String Constants
const ROLE_ADMIN = 'admin';
const BTN_CLASS_PRIMARY = 'btn-primary';

// Routes Configuration
const MODEL_ROUTES = {
	index: 	route('users.index'),
	create: route('users.create'),
	show: 	route('users.show', { user: ':id' }),
	edit: 	route('users.edit', { user: ':id' }),
	delete: route('users.destroy', { user: ':id' }),
};

// ==================== Global Functions ====================

window.toggleLoadingState = toggleLoadingState;

// ==================== Helper Functions ====================

/**
 * Displays the user information in a modal when the "show" action is triggered.
 * 
 * @param {string} url - The URL to fetch user information
 * @param {HTMLElement} anchor - The anchor element triggering the action
 * @returns {Promise<void>} A promise resolving when the modal is displayed
 */
window.showUserInfo = function (url, anchor) {
	return showModelInfo(url, anchor, MODEL_NAME);
};

/**
 * Deletes a user when the "delete" action is triggered.
 * 
 * @param {Event} e - The event object triggering the action
 * @returns {Promise<void>} A promise resolving when the user is deleted
 */
window.deleteUser = function (e) {
	return deleteModel(e, MODEL_NAME);
};

/**
 * Determines if the delete action should be disabled for a user.
 * @param {Object} row - The user row data
 * @param {Object} currentUser - The currently logged-in user
 * @returns {boolean} True if delete should be disabled
 */
function shouldDisableUserDelete(row, currentUser) {
    const isAdmin = row.roles?.[0]?.name === ROLE_ADMIN;
    const isLoggedInUser = row.id === currentUser.id;
    const canDeleteAdmins = currentUser.canDelete;
    
    return isAdmin && (canDeleteAdmins || isLoggedInUser);
}

/**
 * Gets the appropriate tooltip message when delete is disabled.
 * @param {Object} row - The user row data
 * @param {Object} currentUser - The currently logged-in user
 * @returns {string} The tooltip message
 */
function getDeleteDisabledTooltip(row, currentUser) {
    if (row.id === currentUser.id) {
        return `No puedes eliminar tu propio ${MODEL_NAME}.`;
    }
    return `No se puede eliminar al único ${MODEL_NAME} administrador.`;
}

/**
 * Renders the role label for a user in the DataTable.
 * @param {string} data - The raw role name
 * @returns {string} The formatted role label
 */
function renderUserRole(data) {
    const role = MODEL_DATA.roles?.find(role => role.value === data);
    return role ? role.label : data;
}

// ==================== DataTable Initialization ====================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    // Define columns for users table (only for server-side processing)
    const columns = [
        { 
            data: 'name', 
            name: 'name'
            // User's full name
        },
        { 
            data: 'email', 
            name: 'email'
            // User's email address
        },
        { 
            data: 'roles.0.name',
            name: 'role',
            render: renderUserRole
            // User's role (admin, employee, etc.)
        },
        { 
            data: 'created_at',
            name: 'created_at',
			render: (data) => formatDate(data),
			// Creation date formatted as 'DD of Month of YYYY'
        }
    ];

	/**
	 * Define actions for each user row in the DataTable.
	 * @type {{
	 * 	show: { route: string, func: function(url, anchor): Promise<void>, tooltip: string },
	 * 	edit: { route: string, tooltip: string },
	 * 	delete: { route: string, disabledIf: function(any): boolean, disabledIfTooltip: string, tooltip: string, func: function(event): void }
	 * }}
	 */
	const actions = {
		show: { 
			route: MODEL_ROUTES.show, 
            func: window.showUserInfo,
            funcName: 'showUserInfo',
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
			disabledIf: (row) => shouldDisableUserDelete(row, MODEL_DATA.user),
			disabledIfTooltip: (row) => getDeleteDisabledTooltip(row, MODEL_DATA.user),
			tooltip: `Eliminar ${MODEL_NAME}`,
            func: window.deleteUser,
            funcName: 'deleteUser',
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
    CreateNewDataTable('users-table', MODEL_ROUTES.index, columns, actions, customButtons);
});