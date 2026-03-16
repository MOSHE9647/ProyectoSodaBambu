import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatDate, toggleLoadingState } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ==================== Constants ====================
const MODEL_NAME = 'insumo';
const BTN_CLASS_PRIMARY = 'btn-primary';

const MODEL_ROUTES = {
    index:  route('supplies.index'),
    create: route('supplies.create'),
    show:   route('supplies.show', { supply: ':id' }),
    edit:   route('supplies.edit', { supply: ':id' }),
    delete: route('supplies.destroy', { supply: ':id' }),
};

// ==================== Global Functions ====================
window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;

window.showSupply = function (url, anchor) {
    return showModelInfo(url, anchor, MODEL_NAME);
};

window.deleteSupply = function (e) {
    return deleteModel(e, MODEL_NAME);
};

// ==================== DataTable Initialization ====================
$(() => {
    const columns = [
        { 
            data: 'name', 
            name: 'name'
        },
        { 
            data: 'measure_unit', 
            name: 'measure_unit'
        },
        {
            data: 'created_at',
            name: 'created_at',
            render: (data) => formatDate(data),
        }
    ];

    const actions = {
        show: { 
            route: MODEL_ROUTES.show, 
            func: window.showSupply,
            funcName: 'showSupply',
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
            func: window.deleteSupply,
            funcName: 'deleteSupply',
        }
    };

    const customButtons = [
        {
            text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
            href: MODEL_ROUTES.create,
            class: `create-button ${BTN_CLASS_PRIMARY}`,
            icon: 'bi-patch-plus',
            func: toggleLoadingState,
            funcName: 'toggleLoadingState',
            params: ['.create-button', 'create', true],
        }
    ];

    CreateNewDataTable('supplies-table', MODEL_ROUTES.index, columns, actions, customButtons);
});