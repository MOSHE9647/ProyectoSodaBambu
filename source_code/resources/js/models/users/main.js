import { showUser, deleteUser } from "./actions.js";
import { NewCrudDataTable } from '../../utils/datatables.js';
import { toggleLoadingState } from "../../utils/utils.js";
import { SwalToast } from "../../utils/sweetalert.js";

window.SwalToast = SwalToast; // Make globally accessible for inline usage
window.toggleLoadingState = toggleLoadingState; // Make globally accessible for inline usage
window.deleteUser = deleteUser; // Make globally accessible for inline onclick handlers
window.showUser = showUser; // Make globally accessible for inline onclick handlers

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    // Define columns for users table (only for server-side processing)
    const columns = [
        { data: 'name', name: 'name' },
        { data: 'email', name: 'email' },
        { // Assuming roles is an array, and we want the first role's name
            data: 'roles.0.name',
            name: 'role',
            render: function(data, type, row) {
				// Try to find the role label from userRoles (passed from Blade)
                const role = userRoles.find(role => role.value === data);
                return role ? role.label : data; // Fallback to raw data if not found
            }
        },
        { // Format created_at date to 'DD de Month del YYYY'
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
	 * Define actions for each user row in the DataTable.
	 * @type {{
	 * 	show: { route: string, func: function(url, anchor): Promise<void>, tooltip: string },
	 * 	edit: { route: string, tooltip: string },
	 * 	delete: { route: string, disabledIf: function(any): boolean, disabledIfTooltip: string, tooltip: string, func: function(event): void }
	 * }}
	 */
	const actions = {
		show: { route: userShowRoute, func: showUser, tooltip: 'Ver detalles' },
		edit: { route: userEditRoute, func: toggleLoadingState, tooltip: 'Editar usuario' },
		delete: {
			route: userDeleteRoute,
			disabledIf: (row) => {
				const isAdmin = row.roles[0].name === 'admin';
				const isLoggedInUser = row.email === loggedInUserEmail;
				return isAdmin && (isUserUniqueAdmin || isLoggedInUser);
			},
			disabledIfTooltip: (row) => {
				const isLoggedInUser = row.email === loggedInUserEmail;
				if (isLoggedInUser) {
					return 'No puedes eliminar tu propio usuario.';
				}
				return 'No se puede eliminar al único usuario administrador.';
			},
			tooltip: 'Eliminar usuario',
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
            class: 'btn-outline-info',
            icon: 'bi-card-checklist',
			func: underDevelopment,
        },
        {
            text: 'Crear Usuario',
            href: userCreateRoute,
            class: 'create-button btn-primary',
            icon: 'bi-person-plus-fill',
			func: toggleLoadingState,
			params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    NewCrudDataTable('users-table', userRoute, columns, actions, customButtons);
});

// TODO: Remove this function when the feature is implemented
/**
 * Displays a toast notification indicating that the functionality is under development.
 */
function underDevelopment() {
	SwalToast.fire({
		icon: 'info',
		title: 'Funcionalidad en desarrollo',
	});
}
window.underDevelopment = underDevelopment;
