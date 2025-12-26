// resources/js/models/method-payments/main.js
import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { toggleLoadingState } from "../../utils/utils.js";
import { capitalizeSentence } from '../../utils/utils.js';
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";
import { formatDate } from "../../utils/utils.js";

const MODEL_NAME = 'método de pago';

// Expose functions globally
window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;
window.deleteMethodPayment = function deleteMethodPayment(e) { return deleteModel(e, MODEL_NAME); };
window.showMethodPayment = function showMethodPayment(url, anchor) { return showModelInfo(url, anchor, MODEL_NAME); };

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
    const columns = [
        { data: 'amount', name: 'amount' },
        { data: 'type_payment', name: 'type_payment' },
        { 
            data: 'created_at', 
            name: 'created_at',
            render: (data) => formatDate(data),
        }
    ];

    const actions = {
        show: { 
            route: methodPaymentShowRoute, 
            func: showMethodPayment, 
            tooltip: 'Ver detalles' 
        },
        edit: { 
            route: methodPaymentEditRoute, 
            func: toggleLoadingState, 
            tooltip: `Editar ${MODEL_NAME}` 
        },
        delete: {
            route: methodPaymentDeleteRoute,
            tooltip: `Eliminar ${MODEL_NAME}`,
            func: deleteMethodPayment,
        }
    };

    const customButtons = [
        {
            text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
            href: methodPaymentCreateRoute,
            class: 'create-button btn-primary',
            icon: 'bi-credit-card',
            func: toggleLoadingState,
            params: ['.create-button', 'create', true],
        }
    ];

    CreateNewDataTable('method-payments-table', methodPaymentRoute, columns, actions, customButtons);
});