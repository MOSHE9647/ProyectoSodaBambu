import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatDate, toggleLoadingState } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ==================== Constants ====================

const MODEL_NAME = 'insumo';
const BTN_CLASS_PRIMARY = 'btn-primary';

// Filter Configuration
const FILTER_QUERY_PARAM = 'filter';
const FILTER_VALUES = new Set(['expiring_soon']);

const urlParams = new URLSearchParams(window.location.search);
const requestedFilter = urlParams.get(FILTER_QUERY_PARAM);
const initialFilter = FILTER_VALUES.has(requestedFilter) ? requestedFilter : null;

let showOnlyExpiring = initialFilter === 'expiring_soon';
let suppliesDataTable = null;

// Routes Configuration
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

// ==================== Helper Functions ====================

window.showSupply = function (url, anchor) {
    return showModelInfo(url, anchor, MODEL_NAME);
};

window.deleteSupply = function (e) {
    return deleteModel(e, MODEL_NAME);
};

window.toggleExpiringFilter = function () {
    showOnlyExpiring = !showOnlyExpiring;

    const $button = $('.expiring-filter-button');
    $button.toggleClass('btn-outline-danger btn-danger');
    $('.expiring-filter-button-text').text(showOnlyExpiring ? 'Mostrar todos' : 'Próximos a vencer');

    if (suppliesDataTable) {
        suppliesDataTable.ajax.reload(null, true);
    }
};

// ==================== DataTable Initialization ====================

$(() => {
    const $table = $('#supplies-table');
    const canManageSupplies = ($table.data('can-manage-supplies') ?? '').toString() === '1';
    const canCreateSupplies = ($table.data('can-create-supplies') ?? '').toString() === '1';
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
            data: 'quantity', 
            name: 'quantity', 
            type: 'string',
            searchable: false,
            className: 'text-left',
        },
        { 
            data: 'unit_price', 
            name: 'unit_price',
            searchable: false,
        },
        { 
            data: 'expiration_date', 
            name: 'expiration_date', 
            searchable: false,
            render: (data) => data ? formatDate(data) : 'N/A',
        },
        {
            data: 'expiration_alert_date',
            name: 'expiration_alert_date',
            render: (data) => data ? formatDate(data) : 'N/A',
        }
    ];

    /**
     * Definición de acciones dinámicas
     */
    const actions = {
        show: { 
            route: MODEL_ROUTES.show, 
            func: window.showSupply,
            funcName: 'showSupply',
            tooltip: 'Ver detalles' 
        }
    };

    if (canManageSupplies) {
        actions.edit = { 
            route: MODEL_ROUTES.edit, 
            func: toggleLoadingState, 
            funcName: 'toggleLoadingState',
            tooltip: `Editar ${MODEL_NAME}` 
        };
        actions.delete = {
            route: MODEL_ROUTES.delete,
            tooltip: `Eliminar ${MODEL_NAME}`,
            func: window.deleteSupply,
            funcName: 'deleteSupply',
        };
    }

    /**
     * 
     */
    const customButtons = [
        {
            text: 'Próximos a vencer',
            href: 'javascript:void(0)',
            class: 'expiring-filter-button btn-outline-danger',
            icon: 'bi-hourglass-split',
            func: window.toggleExpiringFilter,
            funcName: 'toggleExpiringFilter',
        }
    ];

    if (canCreateSupplies) {
        customButtons.push({
            text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
            href: MODEL_ROUTES.create,
            class: `create-button ${BTN_CLASS_PRIMARY}`,
            icon: 'bi-patch-plus',
            func: toggleLoadingState,
            funcName: 'toggleLoadingState',
            params: ['.create-button', 'create', true],
        });
    }

    if (showOnlyExpiring) {
        setTimeout(() => {
            const $button = $('.expiring-filter-button');
            $button.removeClass('btn-outline-danger').addClass('btn-danger');
            $('.expiring-filter-button-text').text('Mostrar todos');
        }, 100);
    }

    suppliesDataTable = CreateNewDataTable('supplies-table', MODEL_ROUTES.index, columns, actions, customButtons, {
        ajax: {
            url: MODEL_ROUTES.index,
            data: (d) => {
                if (showOnlyExpiring) {
                    d.expiring_soon = 1;
                }
            },
        },
    });
});