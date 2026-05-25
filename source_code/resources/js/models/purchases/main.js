import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatCurrency, formatDate, toggleLoadingState } from "../../utils/utils.js";
import { SwalModal, SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ======================== Constants ========================

// Model Configuration
const MODEL_DATA = window.purchasesData || {};
const MODEL_NAME = 'compra';

// String Constants
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
        const decodedSupplierName = (() => {
            try {
                return decodeURIComponent(supplierName);
            } catch {
                return supplierName;
            }
        })();

        // Trigger a native clean loading sequence inside SweetAlert
        SwalModal.fire({
			title: `
                <div class="d-flex align-items-start gap-3 text-start w-100">
                    <i class="bi bi-truck flex-shrink-0 mt-1"></i>
                    <div class="d-flex flex-column gap-1">
                        <span>Productos/Insumos</span>
                        <span class="fs-5 text-break" style="color: var(--bambu-logo-bg);">${decodedSupplierName}</span>
                    </div>
                </div>
            `.replace(/\n\s*/g, ''),
			html: `
                <div class="text-center py-4 h-100 my-auto" id="swal-loader-container">
                    <div class="spinner-border" style="color: var(--bambu-logo-bg);" role="status"></div>
                    <p class="mt-2 text-muted">Cargando catálogo suministrado...</p>
                </div>
            `,
			showConfirmButton: false,
			showCloseButton: true,
			width: "750px",
			customClass: {
				popup: "swal-popup w-50 h-auto",
				title: "d-flex justify-content-start align-items-center border-bottom pb-3 mb-3",
				closeButton: "swal-close-btn fs-3",
				cancelButton: "btn btn-danger mx-1",
				icon: "mb-4",
			},
		});

        const response = await fetch(`${MODEL_ROUTES.index}?report=true&supplier_id=${supplierId}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" }
        });
        const items = await response.json();
        const $htmlContainer = $('#swal2-html-container');

        if (!
            items || items.length === 0) {
            $htmlContainer.html(`
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                    <p>Este proveedor no tiene ítems registrados en el sistema actualmente.</p>
                </div>
            `);
            return;
        }

        // Build elegant template using the point-of-sale layout scheme
        let tableRows = items
			.map(
				(item) => {
                    const itemTheme = item.type === "Producto"
                        ? { color: 'info', icon: 'bi-box-seam' }
                        : { color: 'warning', icon: 'bi-basket' };
                    const itemTypeLabel = item.type === "Producto" ? "Producto" : "Insumo";
                    return `
                        <tr>
                            <td class="text-start">
                                <span class="badge bg-${itemTheme.color} text-${itemTheme.color}-emphasis border border-${itemTheme.color} bg-${itemTheme.color}-subtle rounded-pill px-3 py-2">
                                    <i class="bi ${itemTheme.icon} me-1"></i>
                                    ${itemTypeLabel}
                                </span>
                            </td>
                            <td class="text-start fw-semibold">${item.name}</td>
                            <td class="text-center fw-bold" style="color: var(--bambu-logo-bg);">${item.times}</td>
                        </tr>
                    `;
                },
			)
			.join("");

        $htmlContainer.html(`
            <p class="text-start text-muted mb-3">Historial de suministros provistos detectados en inventario:</p>
            <div class="table-responsive p-0" style="font-size: 1rem; max-height: 700px; overflow-y: auto;">
                <table class="init-datatable table table-hover align-middle" style="min-width: 600px;">
                    <thead class="table-subtle text-secondary-emphasis">
                        <tr>
                            <th style="width: 25%;">Tipo</th>
                            <th style="width: 50%;">Nombre del Ítem</th>
                            <th class="text-center" style="width: 25%;">Suministros Totales</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                </table>
            </div>
        `);

        const $tables = $(".swal2-popup .init-datatable");
		if ($tables.length) {
			$tables.each(function () {
				$(this).DataTable({
					pageLength: 5,
					lengthMenu: [5, 10, 25, 50],
					searching: false,
                    ordering: false,
					layout: {
						topStart: null,
					},
				});
			});
		}
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
			render: (data) => `<span class="font-monospace">${data}</span>`,
		},
		{
			data: "supplier.name",
			name: "supplier.name",
			title: "Proveedor",
			render: (data, type, row) => `
                <a href="javascript:void(0)" class="fw-bold text-decoration-none" onclick="showSupplierItems(${row.supplier_id}, '${encodeURIComponent(data)}')" style="color: var(--bambu-logo-bg);">
                    <i class="bi bi-box-seam me-1"></i>${data}
                </a>
            `,
		},
		{
			data: "date",
			name: "date",
			title: "Fecha",
			render: (data) => formatDate(data.slice(0, 10)),
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
        }
    };

    if (MODEL_DATA.canEdit) {
        actions.edit = {
            route: MODEL_ROUTES.edit,
            func: toggleLoadingState,
            funcName: 'toggleLoadingState',
            tooltip: `Editar ${MODEL_NAME}`
        };
    }

    if (MODEL_DATA.canDelete) {
        actions.delete = {
            route: MODEL_ROUTES.delete,
            tooltip: `Eliminar ${MODEL_NAME}`,
            func: window.deletePurchase,
            funcName: 'deletePurchase',
        };
    }

    let customButtons = [];
    if (MODEL_DATA.canCreate) {
        customButtons = [
			{
				text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
				href: MODEL_ROUTES.create,
				class: `create-button ${BTN_CLASS_PRIMARY}`,
				icon: "bi-plus-circle-fill",
				func: toggleLoadingState,
				funcName: "toggleLoadingState",
				params: [".create-button", "create", true],
			},
		];
    }

    CreateNewDataTable('purchases-table', MODEL_ROUTES.index, columns, actions, customButtons);
});