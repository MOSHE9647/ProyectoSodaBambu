import { showCategory, deleteCategory } from "./actions.js";
import { NewCrudDataTable } from '../../utils/datatables.js';
import { toggleLoadingState } from "../../utils/utils.js";
import { SwalToast } from "../../utils/sweetalert.js";

// Expose necessary functions to window object
window.toggleLoadingState = toggleLoadingState;
window.deleteCategory = deleteCategory;
window.SwalToast = SwalToast;
window.showCategory = showCategory;

// Ensure the DOM is fully loaded before initializing the DataTable
$(document).ready(() => {
    // Define columns for categories table
    const columns = [
        { 
            data: 'name',
            name: 'name'
        },
        { 
            data: 'description',
            name: 'description',
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
     * Define actions for each category row in the DataTable.
     */
    const actions = {
        show: { route: categoryShowRoute, func: showCategory, tooltip: 'Ver detalles' },
        edit: { route: categoryEditRoute, func: toggleLoadingState, tooltip: 'Editar categoría' },
        delete: {
            route: categoryDeleteRoute,
            tooltip: 'Eliminar categoría',
            func: deleteCategory,
        }
    };

    /**
     * Define custom buttons for the DataTable interface.
     */
    const customButtons = [
        {
            text: 'Crear Categoría',
            href: categoryCreateRoute,
            class: 'create-button btn-primary',
            icon: 'bi-plus-circle-fill',
            func: toggleLoadingState,
            params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    NewCrudDataTable('categories-table', categoryRoute, columns, actions, customButtons);
});