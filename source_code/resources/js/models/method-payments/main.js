import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, toggleLoadingState, formatDate } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'método de pago';

// String Constants
const BTN_CLASS_PRIMARY = 'btn-primary';

// Routes Configuration
const MODEL_ROUTES = {
    index: 	route('method-payments.index'),
    create: route('method-payments.create'),
    show: 	route('method-payments.show', { payment: ':id' }),
    edit: 	route('method-payments.edit', { payment: ':id' }),
    delete: route('method-payments.destroy', { payment: ':id' }),
};

// ==================== Global Functions ====================

// Expose functions globally
window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;
window.deleteMethodPayment = function deleteMethodPayment(e) { return deleteModel(e, MODEL_NAME); };
window.showMethodPayment = function showMethodPayment(url, anchor) { return showModelInfo(url, anchor, MODEL_NAME); };

// ==================== DataTable Initialization ====================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    // Define columns for method payments table (only for server-side processing)
    const columns = [
        { 
            data: 'amount', 
            name: 'amount'
            // Monto del método de pago
        },
        { 
            data: 'type_payment', 
            name: 'type_payment'
            // Tipo de pago (efectivo, tarjeta, transferencia, etc.)
        },
        { 
            data: 'created_at', 
            name: 'created_at',
            // Fecha de creación formateada como 'DD de Month del YYYY'
            render: (data) => formatDate(data),
        }
    ];

    /**
     * Define actions for each method payment row in the DataTable.
     * @type {{
     * 	show: { route: string, func: function(url, anchor): Promise<void>, tooltip: string },
     * 	edit: { route: string, tooltip: string },
     * 	delete: { route: string, tooltip: string, func: function(event): void }
     * }}
     */
    const actions = {
        show: { 
            route: MODEL_ROUTES.show, 
            func: showMethodPayment, 
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
            func: deleteMethodPayment,
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
            icon: 'bi-credit-card',
            func: toggleLoadingState,
            params: ['.create-button', 'create', true],
        }
    ];

    // Initialize the CRUD DataTable
    CreateNewDataTable('method-payments-table', MODEL_ROUTES.index, columns, actions, customButtons);
});