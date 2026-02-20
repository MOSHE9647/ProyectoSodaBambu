import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatDate, toggleLoadingState } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'usuario';
const MODEL_DATA = window.UsersAppData || {};

// String Constants
const ROLE_ADMIN = 'admin';
const BTN_CLASS_PRIMARY = 'btn-primary';
const BTN_CLASS_OUTLINE_INFO = 'btn-outline-info';

// Routes Configuration
const MODEL_ROUTES = {
	index: 	route('users.index'),
	create: route('users.create'),
	show: 	route('users.show', { user: ':id' }),
	edit: 	route('users.edit', { user: ':id' }),
	delete: route('users.destroy', { user: ':id' }),
};

// ==================== Global Functions ====================

// Expose functions globally
window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;
window.deleteUser = function deleteUser(e) { return deleteModel(e, MODEL_NAME); };
window.showUserInfo = function showUserInfo(url, anchor) { return showModelInfo(url, anchor, MODEL_NAME); };

// ==================== Helper Functions ====================

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

/**
 * TODO: Remove this function when the feature is implemented
 * Displays a toast notification indicating that the functionality is under development.
 */
function underDevelopment(_param1, _param2, _param3) {
	SwalToast.fire({
		icon: SwalNotificationTypes.INFO,
		title: 'Funcionalidad en desarrollo',
	});
}
window.underDevelopment = underDevelopment;

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
            // Creation date formatted as 'DD de Month del YYYY'
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
			func: showUserInfo, 
			tooltip: 'Ver detalles' 
		},
		edit: { 
			route: MODEL_ROUTES.edit, 
			func: toggleLoadingState, 
			tooltip: `Editar ${MODEL_NAME}` 
		},
		delete: {
			route: MODEL_ROUTES.delete,
			disabledIf: (row) => shouldDisableUserDelete(row, MODEL_DATA.user),
			disabledIfTooltip: (row) => getDeleteDisabledTooltip(row, MODEL_DATA.user),
			tooltip: `Eliminar ${MODEL_NAME}`,
			func: deleteUser,
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
            text: 'Registrar Asistencia',
            href: '#',
            class: BTN_CLASS_OUTLINE_INFO,
            icon: 'bi-card-checklist',
			func: underDevelopment,
			params: ['.attendance-button', 'attendance', true],
        },
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
    CreateNewDataTable('users-table', MODEL_ROUTES.index, columns, actions, customButtons);
});