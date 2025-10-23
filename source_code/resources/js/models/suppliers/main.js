/**
 * Módulo JS para la gestión de suppliers.
 *
 * - Inicializa el DataTable con soporte server-side para mejor rendimiento.
 * - Define columnas, acciones CRUD (ver, editar, eliminar).
 * - Integra SweetAlert, control de carga y botones personalizados.
 *
 */

import { showSupplier, deleteSupplier } from "./actions.js";
import { NewCrudDataTable } from '../../utils/datatables.js';
import { toggleLoadingState } from "../../utils/utils.js";
import { SwalToast } from "../../utils/sweetalert.js";

window.toggleLoadingState = toggleLoadingState; // Make globally accessible for inline usage
window.deleteSupplier = deleteSupplier; // Make globally accessible for inline onclick handlers
window.SwalToast = SwalToast; // Make globally accessible for inline usage
window.showSupplier = showSupplier; // Make globally accessible for inline onclick handlers

/**
 * Initialize the Suppliers DataTable with server-side processing.
 * This provides better performance and scalability for large datasets.
 */

// Ensure the DOM is fully loaded before initializing the DataTable
$(document).ready(() => {
    // Define columns for suppliers table (only for server-side processing)
    const columns = [
        { 
            data: 'name', 
            name: 'name',
            render: function(data, type, row) {
                let nameText = data;
                if (row.deleted_at) {
                    nameText += ' <span class="badge bg-danger">Eliminado</span>';
                }
                return nameText;
            }
        },
        { data: 'phone', name: 'phone' },
        { data: 'email', name: 'email' },
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
	 * Define actions for each supplier row in the DataTable.
	 * @type {{
	 * 		show: {
	 * 			route: string,
	 * 			func: function(url, anchor): Promise<void>,
	 * 			tooltip: string
	 * 		},
	 * 		edit: {
	 * 			route: string,
	 * 			tooltip: string
	 * 		},
	 * 		delete: {
	 * 			route: string,
	 * 			tooltip: string,
	 * 			func: function(event): void
	 * 		}
	 * 	}}
	 */
	const actions = {
		show: { route: supplierShowRoute, func: showSupplier, funcName: 'showSupplier', tooltip: 'Ver detalles' },
		edit: { route: supplierEditRoute, func: toggleLoadingState, funcName: 'toggleLoadingState', tooltip: 'Editar proveedor' },
		delete: {
			route: supplierDeleteRoute,
			tooltip: 'Eliminar proveedor',
			func: deleteSupplier,
			funcName: 'deleteSupplier',
		}
	};

	/**
	 * Define custom buttons for the DataTable interface.
	 * @type {[
	 * 		{
	 * 			text: string,
	 * 			href: string,
	 * 			class: string,
	 * 			icon: string
	 * 		}
	 * ]}
	 */
    const customButtons = [
        {
            text: 'Crear Proveedor',
            href: supplierCreateRoute,
            class: 'create-button btn-primary',
            icon: 'bi-building-add',
			func: toggleLoadingState,
			funcName: 'toggleLoadingState',
			params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    NewCrudDataTable('suppliers-table', supplierRoute, columns, actions, customButtons);
});
