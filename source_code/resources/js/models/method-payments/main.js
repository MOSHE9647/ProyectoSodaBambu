// resources/js/models/method-payments/main.js
import { showMethodPayment, deleteMethodPayment } from "./actions.js";
import { NewCrudDataTable } from '../../utils/datatables.js';
import { toggleLoadingState } from "../../utils/utils.js";
import { SwalToast } from "../../utils/sweetalert.js";

window.toggleLoadingState = toggleLoadingState;
window.deleteMethodPayment = deleteMethodPayment;
window.SwalToast = SwalToast;
window.showMethodPayment = showMethodPayment;


function formatDate(dateString) {
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
        console.error('Fecha inválida:', dateString);
        return 'Fecha inválida';
    }

    const months = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
    ];

    const day = date.getDate();
    const month = months[date.getMonth()];
    const year = date.getFullYear();

    return `${day} de ${month} del ${year}`;
}

$(document).ready(() => {
    const columns = [
        { data: 'amount', name: 'amount' },
        { data: 'type_payment', name: 'type_payment' },
        { 
            data: 'created_at', 
            name: 'created_at',
            render: function(data, type, row) {
                return formatDate(data);
            }
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