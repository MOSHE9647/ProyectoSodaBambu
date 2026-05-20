import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatCurrency, toggleLoadingState } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ======================== Constants ========================

// Model Configuration
const MODEL_NAME = 'compra';
const BTN_CLASS_PRIMARY = 'btn-primary';

// Routes Configuration
const MODEL_ROUTES = {
    index: route('purchases.index'),
    create: route('purchases.create'),
    show: route('purchases.show', { purchase: ':id' }),
    edit: route('purchases.edit', { purchase: ':id' }),
    delete: route('purchases.destroy', { purchase: ':id' }),
};

// ==================== Global Functions ====================

window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;

// ==================== Helper Functions ====================

window.showPurchaseInfo = function (url, anchor) {
    return showModelInfo(url, anchor, MODEL_NAME);
};

window.deletePurchase = function (e) {
    return deleteModel(e, MODEL_NAME);
};

// DataTable del modal de proveedor — se destruye y recrea en cada apertura
let supplierItemsTable = null;

window.showSupplierItems = function (supplierId, supplierName) {
    const modal = new bootstrap.Modal(document.getElementById('supplierItemsModal'));
    const $loading = $('#supplier-items-loading');
    const $content = $('#supplier-items-content');
    const $empty = $('#supplier-items-empty');
    const $modalName = $('#modal-supplier-name');

    // Limpiar estado anterior
    if (supplierItemsTable) {
        supplierItemsTable.destroy();
        supplierItemsTable = null;
        $('#supplier-items-table').empty(); // Limpiar DOM del table si DataTables lo modificó
    }
    
    // Ocultar spinners/vacio manuales que ya no se ocupan porque DataTable se encarga
    $loading.addClass('d-none');
    $empty.addClass('d-none');
    $content.removeClass('d-none');
    
    $modalName.text(supplierName);
    modal.show();

    const columns = [
        {
            data: 'type', name: 'type', title: 'Tipo', orderable: false,
            render: (data) => {
                const badgeClass = data === 'Producto' ? 'bg-success' : 'bg-info text-dark';
                return `<span class="badge ${badgeClass}">${data}</span>`;
            }
        },
        { data: 'name', name: 'name', title: 'Nombre' },
        { 
            data: 'times', name: 'times', title: 'Veces Suministrado', 
            className: 'text-center fw-semibold' 
        }
    ];

    supplierItemsTable = CreateNewDataTable(
        'supplier-items-table',
        MODEL_ROUTES.index,
        columns,
        {},
        [],
        {
            serverSide: false, // Es una consulta que retorna {items: [...]}
            ajax: {
                data: { supplier_id: supplierId, report: 1 },
                dataSrc: 'items'
            },
        }
    );
};

/**
 * Create an HTML badge element and return its outer HTML as a string.
 * Uses semantic "type" to build Bootstrap-like utility classes.
 *
 * @param {string} text - Text to display inside the badge.
 * @param {string} [type='secondary'] - Semantic type (e.g. 'success', 'danger').
 * @returns {string} Outer HTML of the created badge element.
 */
const createStatusBadge = (text, type = 'secondary') => {
    const badge = document.createElement('span');
    badge.className = `badge border rounded-pill text-${type}-emphasis bg-${type}-subtle px-3 py-2`;
    badge.style.minWidth = '90px';
    badge.style.minHeight = '30px';
    badge.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle'} me-2"></i> ${text}`;
    return badge.outerHTML;
};

/**
 * Map a purchase status key to a styled badge HTML string.
 *
 * @param {string|null|undefined} status - Status identifier for the purchase.
 * @returns {string} HTML string of the corresponding status badge.
 */
const getStatusBadge = (status) => {
    if (status == null) {
        return createStatusBadge('Desconocido');
    }

    switch (status) {
        case 'paid':
            return createStatusBadge('Completo', 'success');
        case 'pending':
            return createStatusBadge('Pendiente', 'warning');
        default:
            return createStatusBadge('Desconocido');
    }
};

// Limpiar DataTable al cerrar el modal
$('#supplierItemsModal').on('hidden.bs.modal', function () {
    if (supplierItemsTable) {
        supplierItemsTable.destroy();
        $('#supplier-items-table').empty();
        supplierItemsTable = null;
    }
    $('#supplier-items-content').addClass('d-none');
});

$(() => {
    const columns = [
        { data: 'invoice_number', name: 'invoice_number', title: 'N° Factura' },
        {
            data: 'supplier',
            name: 'supplier.name',
            title: 'Proveedor',
            render: (data, type, row) => {
                if (!data?.name) return 'N/A';
                return `<a href="javascript:void(0)"
                           class="purchase-supplier-link fw-semibold"
                           onclick="showSupplierItems(${row.supplier_id}, '${data.name.replace(/'/g, "\\'")}')"
                           title="Ver productos/insumos de este proveedor">
                           <i class="bi bi-truck me-1"></i>${data.name}
                        </a>`;
            }
        },
        {
            data: 'date', name: 'date', title: 'Fecha',
            render: (data) => {
                if (!data) return '';
                const d = new Date(data + 'T00:00:00');
                return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' })
                    .replace(/(\d+) de (\w+) de (\d+)/, '$1 de $2 del $3');
            }
        },
        {
            data: 'total', name: 'total', title: 'Total',
            render: (data) => formatCurrency(data)
        },
        {
            data: 'payment_status',
            name: 'payment_status',
            title: 'Estado de Pago',
            render: (data) => {
                return getStatusBadge(data);
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