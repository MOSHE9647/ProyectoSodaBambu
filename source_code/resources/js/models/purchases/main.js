import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatCurrency, formatDate, toggleLoadingState } from "../../utils/utils.js";
import { SwalConfirmation, SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

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

/**
 * Renders an optimized itemized breakdown modal for the supplier utilizing 
 * robust SweetAlert2 structures instead of traditional Bootstrap models.
 * @param {number} supplierId - ID of the target supplier
 * @param {string} supplierName - Display text for header titles
 */
window.showSupplierItems = async function (supplierId, supplierName) {
    try {
        // Trigger a native clean loading sequence inside SweetAlert
        SwalConfirmation.fire({
            title: `<i class="bi bi-truck me-2"></i> Productos/Insumos de <span class="text-primary">${supplierName}</span>`,
            html: `
                <div class="text-center py-4" id="swal-loader-container">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Cargando catálogo suministrado...</p>
                </div>
            `,
            showConfirmButton: false,
            showCloseButton: true,
            width: '750px',
            customClass: { popup: 'p-4 rounded-3 text-start' }
        });

        const response = await fetch(`${MODEL_ROUTES.index}?report=true&supplier_id=${supplierId}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" }
        });
        const items = await response.json();
        const $htmlContainer = $('#swal2-html-container');

        if (!items || items.length === 0) {
            $htmlContainer.html(`
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                    <p>Este proveedor no tiene ítems registrados en el sistema actualmente.</p>
                </div>
            `);
            return;
        }

        // Build elegant template using the point-of-sale layout scheme
        let tableRows = items.map(item => `
            <tr>
                <td><span class="badge ${item.type === 'Producto' ? 'bg-info text-dark' : 'bg-warning text-dark'}">${item.type}</span></td>
                <td class="fw-semibold">${item.name}</td>
                <td class="text-center bg-light text-primary fw-bold">${item.times}</td>
            </tr>
        `).join('');

        $htmlContainer.html(`
            <p class="text-muted small mb-3">Historial de suministros provistos detectados en inventario:</p>
            <div class="table-responsive rounded-2 border" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light position-sticky top-0">
                        <tr>
                            <th>Tipo</th>
                            <th>Nombre del Ítem</th>
                            <th class="text-center">Suministros Totales</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                </table>
            </div>
        `);
    } catch (error) {
        console.error("Error building supplier dynamic context modal:", error);
        SwalToast.fire({ icon: 'error', title: 'Error al recuperar información del proveedor.' });
    }
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
		{
			data: "invoice_number",
			name: "invoice_number",
			title: "N° Factura",
			render: (data) =>
				`<span class="font-monospace">${data}</span>`,
		},
		{
			data: "supplier.name",
			name: "supplier.name",
			title: "Proveedor",
			render: (data, type, row) => `
                <a href="javascript:void(0)" class="fw-bold text-decoration-none" onclick="showSupplierItems(${row.supplier_id}, '${escape(data)}')" style="color: var(--bambu-logo-bg);">
                    <i class="bi bi-box-seam me-1"></i>${data}
                </a>
            `,
		},
		{
			data: "date",
			name: "date",
			title: "Fecha",
			render: (data) => formatDate(data),
		},
		{
			data: "total",
			name: "total",
			title: "Total",
			render: (data) => formatCurrency(data),
		},
		{
			data: "payment_status",
			name: "payment_status",
			title: "Estado de Pago",
			render: (data) => {
				return getStatusBadge(data);
			},
		},
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