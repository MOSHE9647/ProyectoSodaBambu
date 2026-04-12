import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, toggleLoadingState } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

const MODEL_NAME = 'compra';
const BTN_CLASS_PRIMARY = 'btn-primary';

const MODEL_ROUTES = {
    index: route('purchases.index'),
    create: route('purchases.create'),
    show: route('purchases.show', { purchase: ':id' }),
    edit: route('purchases.edit', { purchase: ':id' }),
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

// DataTable del modal de proveedor — se destruye y recrea en cada apertura
let supplierItemsTable = null;

window.showSupplierItems = function (supplierId, supplierName) {
    const modal = new bootstrap.Modal(document.getElementById('supplierItemsModal'));
    const $loading = $('#supplier-items-loading');
    const $content = $('#supplier-items-content');
    const $empty = $('#supplier-items-empty');
    const $tbody = $('#supplier-items-tbody');
    const $modalName = $('#modal-supplier-name');

    // Limpiar estado anterior
    if (supplierItemsTable) {
        supplierItemsTable.destroy();
        supplierItemsTable = null;
    }
    $tbody.empty();
    $loading.removeClass('d-none');
    $content.addClass('d-none');
    $empty.addClass('d-none');
    $modalName.text(supplierName);

    modal.show();

    $.ajax({
        url: MODEL_ROUTES.index,
        method: 'GET',
        data: { supplier_id: supplierId, report: 1 },
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        success: function (data) {
            $loading.addClass('d-none');

            if (!data.items || data.items.length === 0) {
                $empty.removeClass('d-none');
                return;
            }

            data.items.forEach(item => {
                // Mismos colores que la tabla de detalles en show.blade.php
                const badgeClass = item.type === 'Producto'
                    ? 'bg-success'
                    : 'bg-info text-dark';

                $tbody.append(`
                    <tr>
                        <td><span class="badge ${badgeClass}">${item.type}</span></td>
                        <td>${item.name}</td>
                        <td class="text-center fw-semibold">${item.times}</td>
                    </tr>
                `);
            });

            $content.removeClass('d-none');

            // Misma configuración de DataTable que la tabla de detalles de compra
            supplierItemsTable = $('#supplier-items-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                    search: 'Buscar:',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ ítems',
                    infoEmpty: 'Sin resultados',
                    zeroRecords: 'No se encontraron ítems para esta búsqueda',
                    paginate: {
                        first: '<i class="bi-skip-start-fill" style="font-size: .69rem"></i>',
                        last: '<i class="bi-skip-end-fill" style="font-size: .69rem"></i>',
                        next: '<i class="bi-caret-right-fill" style="font-size: .69rem"></i>',
                        previous: '<i class="bi-caret-left-fill" style="font-size: .69rem"></i>'
                    }
                },
                pageLength: 5,
                lengthMenu: [5, 10, 25],
                order: [[2, 'desc']], // Ordenar por "Veces suministrado" descendente
                columnDefs: [
                    { orderable: false, targets: 0 }, // Columna Tipo no ordenable
                    { className: 'text-center', targets: 2 },
                ],
                dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>rt<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>',
            });
        },
        error: function () {
            $loading.addClass('d-none');
            SwalToast.fire({ icon: 'error', text: 'No se pudo cargar la información del proveedor.' });
        }
    });
};

// Limpiar DataTable al cerrar el modal
$('#supplierItemsModal').on('hidden.bs.modal', function () {
    if (supplierItemsTable) {
        supplierItemsTable.destroy();
        supplierItemsTable = null;
    }
    $('#supplier-items-tbody').empty();
    $('#supplier-items-content').addClass('d-none');
    $('#supplier-items-empty').addClass('d-none');
    $('#supplier-items-loading').addClass('d-none');
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
            render: (data) => `₡${parseFloat(data).toFixed(2)}`
        },
        {
            data: 'payment_status',
            name: 'payment_status',
            title: 'Estado de Pago',
            render: (data) => {
                const badgeClass = {
                    'paid': 'bg-success',
                    'partial': 'bg-info text-dark',
                    'pending': 'bg-warning text-dark',
                    'cancelled': 'bg-danger',
                    'void': 'bg-danger'
                }[data] || 'bg-light text-dark';

                const labels = {
                    'paid': 'Completo',
                    'partial': 'Parcial',
                    'pending': 'Pendiente',
                    'cancelled': 'Anulado',
                    'void': 'Anulado'
                };

                return `<span class="badge ${badgeClass}">${labels[data] ?? data}</span>`;
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