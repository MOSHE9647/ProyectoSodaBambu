import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatDate, toggleLoadingState } from "../../utils/utils.js";
import { SwalToast } from "../../utils/sweetalert.js";

// Constants
const MODEL_NAME = 'categoría';

// Expose necessary functions to window object
window.SwalToast = SwalToast;
window.toggleLoadingState = toggleLoadingState;
window.deleteCategory = function deleteCategory(e) { return deleteModel(e, MODEL_NAME); };
window.showCategory = function showCategory(url, anchor) { return showModelInfo(url, anchor, MODEL_NAME); };

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    // Define columns for categories table
    const columns = [
        { data: 'name', name: 'name' },
        { 
            data: 'description',
            name: 'description',
            render: (data) => data ? data : 'N/A',
        },
        {
            data: 'created_at',
            name: 'created_at',
            render: (data) => formatDate(data),
        }
    ];

    /**
     * Define actions for each category row in the DataTable.
     */
    const actions = {
        show: { route: categoryShowRoute, func: showCategory, tooltip: 'Ver detalles' },
        edit: { route: categoryEditRoute, func: toggleLoadingState, tooltip: `Editar ${MODEL_NAME}` },
        delete: {
            route: categoryDeleteRoute,
            tooltip: `Eliminar ${MODEL_NAME}`,
            func: deleteCategory,
        }
    };

    /**
     * Define custom buttons for the DataTable interface.
     */
    const customButtons = [
        {
            text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
            href: categoryCreateRoute,
            class: 'create-button btn-primary',
            icon: 'bi-plus-circle-fill',
            func: toggleLoadingState,
            params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    CreateNewDataTable('categories-table', categoryRoute, columns, actions, customButtons);
});