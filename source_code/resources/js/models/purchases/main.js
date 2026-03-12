import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, toggleLoadingState } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

const MODEL_NAME = 'compra';
const BTN_CLASS_PRIMARY = 'btn-primary';

const MODEL_ROUTES = {
    index:  route('purchases.index'),
    create: route('purchases.create'),
    show:   route('purchases.show', { purchase: ':id' }),
    edit:   route('purchases.edit', { purchase: ':id' }),
    delete: route('purchases.destroy', { purchase: ':id' }),
};

window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;

window.showPurchaseInfo = function (url, anchor) {
    return showModelInfo(url, anchor, MODEL_NAME);
};

window.deletePurchase = function (e) {
    return deleteModel(e, MODEL_NAME);
};

$(() => {
    const columns = [
        { data: 'invoice_number', name: 'invoice_number', title: 'N° Factura' },
        { data: 'supplier.name', name: 'supplier.name', title: 'Proveedor', render: (data) => data || 'N/A' },
        { data: 'date', name: 'date', title: 'Fecha', render: (data) => data ? new Date(data).toLocaleDateString('es-ES') : '' },
        { data: 'total', name: 'total', title: 'Total', render: (data) => `$${parseFloat(data).toFixed(2)}` },
        {
            data: 'payment_status',
            name: 'payment_status',
            title: 'Estado de Pago',
            render: (data) => {
                const badgeClass = {
                    'Completo': 'bg-success',
                    'Parcial': 'bg-warning text-dark',
                    'Pendiente': 'bg-secondary',
                    'Anulado': 'bg-danger'
                }[data] || 'bg-light text-dark';
                return `<span class="badge ${badgeClass}">${data}</span>`;
            }
        }
    ];

    const actions = {
        show: {
            route: MODEL_ROUTES.show,
            func: window.showPurchaseInfo,
            funcName: 'showPurchaseInfo',
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
            func: window.deletePurchase,
            funcName: 'deletePurchase',
        }
    };

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

    CreateNewDataTable('purchases-table', MODEL_ROUTES.index, columns, actions, customButtons);
});