// resources/js/models/method-payments/main.js
import { showMethodPayment, deleteMethodPayment } from "./actions.js";
import { NewCrudDataTable } from '../../utils/datatables.js';
import { toggleLoadingState } from "../../utils/utils.js";
import { SwalToast } from "../../utils/sweetalert.js";

window.toggleLoadingState = toggleLoadingState;
window.deleteMethodPayment = deleteMethodPayment;
window.SwalToast = SwalToast;
window.showMethodPayment = showMethodPayment;

$(document).ready(() => {
    const columns = [
        { data: 'id', name: 'id' },
        { data: 'amount', name: 'amount' },
        { data: 'type_payment', name: 'type_payment' },
        { data: 'created_at', name: 'created_at' }
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
            tooltip: 'Editar método de pago' 
        },
        delete: {
            route: methodPaymentDeleteRoute,
            tooltip: 'Eliminar método de pago',
            func: deleteMethodPayment,
        }
    };

    const customButtons = [
        {
            text: 'Crear Método de Pago',
            href: methodPaymentCreateRoute,
            class: 'create-button btn-primary',
            icon: 'bi-credit-card',
            func: toggleLoadingState,
            params: ['.create-button', 'create', true],
        }
    ];

    NewCrudDataTable('method-payments-table', methodPaymentRoute, columns, actions, customButtons);
});