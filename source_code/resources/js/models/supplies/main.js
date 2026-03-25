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

const urlParams = new URLSearchParams(window.location.search);
let showOnlyExpiring = urlParams.get('filter') === 'expiring_soon';

let suppliesDataTable = null;

/** * NUEVA LÓGICA DE PERMISOS: 
 * Lee el atributo 'data-can-manage-products' que agregamos a la tabla en Blade.
 */
const canManageSupplies = ($('#supplies-table').data('can-manage-products') ?? '').toString() === '1';

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
    const columns = [
        { data: 'name', name: 'name' },
        { data: 'measure_unit', name: 'measure_unit' },
        { 
            data: 'quantity', 
            name: 'quantity', 
            className: 'text-center',
            render: (data) => `<strong>${data}</strong>`,
            orderable: false,
            searchable: false,
        },
        { 
            data: 'unit_price', 
            name: 'unit_price', 
            className: 'text-end',
            orderable: false,
            searchable: false,
        },
        { 
            data: 'expiration_date', 
            name: 'expiration_date', 
            className: 'text-center',
            searchable: false,
        },
        {
            data: 'created_at',
            name: 'created_at',
            render: (data) => formatDate(data),
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

    // Si tiene permisos, inyectamos Editar y Eliminar al objeto de acciones
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
     * Definición de botones personalizados
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

    // Si tiene permisos, inyectamos el botón de "Crear Insumo"
    if (canManageSupplies) {
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

    // Inicialización de la DataTable con las acciones y botones filtrados
    suppliesDataTable = CreateNewDataTable('supplies-table', MODEL_ROUTES.index, columns, actions, customButtons, {
        ajax: {
            url: MODEL_ROUTES.index,
            data: (d) => {
                d.expiring_soon = showOnlyExpiring ? 1 : 0;
            }
        }
    });
});