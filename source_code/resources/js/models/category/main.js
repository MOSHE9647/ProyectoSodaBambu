import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatDate, toggleLoadingState } from "../../utils/utils.js";

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'categoría';

// String Constants
const BTN_CLASS_PRIMARY = 'btn-primary';

// Routes Configuration
const MODEL_ROUTES = {
    index: 	route('categories.index'),
    create: route('categories.create'),
    show: 	route('categories.show', { category: ':id' }),
    edit: 	route('categories.edit', { category: ':id' }),
    delete: route('categories.destroy', { category: ':id' }),
};

// ==================== Global Functions ====================

// Expose necessary functions to window object
window.toggleLoadingState = toggleLoadingState;

// ==================== Helper Functions ====================

/**
 * Shows information for a specific category.
 * @param {string} url - The URL to fetch category information from
 * @param {HTMLElement} anchor - The anchor element for the modal
 * @returns {Promise<void>} A promise resolving when the modal is shown
 */
window.showCategoryInfo = function (url, anchor) {
    return showModelInfo(url, anchor, MODEL_NAME);
};

/**
 * Deletes a specific category.
 * @param {Event} e - The event object
 * @returns {Promise<void>} A promise resolving when the category is deleted
 */
window.deleteCategory = function (e) {
    return deleteModel(e, MODEL_NAME);
};

// ==================== DataTable Initialization ====================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    // Define columns for categories table (only for server-side processing)
    const columns = [
        { 
            data: 'name', 
            name: 'name'
            // Category name
        },
        { 
            data: 'description',
            name: 'description',
            // Category description (N/A if not available)
            render: (data) => data ? data : 'N/A',
        },
        {
            data: 'created_at',
            name: 'created_at',
            // Creation date formatted as 'DD of Month of YYYY'
            render: (data) => formatDate(data),
        }
    ];

    /**
     * Define actions for each category row in the DataTable.
     * @type {{
     * 	show: { route: string, func: function(url, anchor): Promise<void>, tooltip: string },
     * 	edit: { route: string, tooltip: string },
     * 	delete: { route: string, tooltip: string, func: function(event): void }
     * }}
     */
    const actions = {
        show: { 
            route: MODEL_ROUTES.show, 
            func: window.showCategoryInfo,
            funcName: 'showCategoryInfo',
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
            func: window.deleteCategory,
            funcName: 'deleteCategory',
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
            icon: 'bi-plus-circle-fill',
            func: toggleLoadingState,
            funcName: 'toggleLoadingState',
            params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    CreateNewDataTable('categories-table', MODEL_ROUTES.index, columns, actions, customButtons);
});