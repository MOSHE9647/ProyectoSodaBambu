import { showModelInfo, deleteModel } from '../../models/actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { formatDate, formatCurrency, escapeHtml, printReceipt } from '../../utils/utils.js';
import { SwalToast } from '../../utils/sweetalert.js';

// ==================== Constants ====================

const MODEL_NAME = 'la venta';
const TODAY = new Date().toISOString().split('T')[0];

const MODEL_ROUTES = {
    index:  route('sales.index'),
    show:   route('sales.show', { sale: ':id' }),
    print:  route('receipts.show', { model: 'sales', id: ':id' }),
    delete: route('sales.destroy', { sale: ':id' }),
};

const FILTER_SELECTORS = {
    invoice:   '#invoice-filter',
    startDate: '#start-date-filter',
    endDate:   '#end-date-filter',
};

// ==================== Global Functions ====================

window.showSale = function (url, anchor) {
    return showModelInfo(url, anchor, MODEL_NAME);
};

window.deleteSale = function (e) {
    return deleteModel(e, MODEL_NAME);
};

window.printSale = function (url) {
    printReceipt(url);
};

window.applySalesFilters = function () {
    syncClearButtonVisibility();
    salesDataTable?.ajax.reload();
};

window.clearSalesFilters = function () {
    const invoice   = document.querySelector(FILTER_SELECTORS.invoice);
    const startDate = document.querySelector(FILTER_SELECTORS.startDate);
    const endDate   = document.querySelector(FILTER_SELECTORS.endDate);
    if (invoice)   invoice.value   = '';
    if (startDate) startDate.value = '';
    if (endDate)   endDate.value   = '';
    syncClearButtonVisibility();
    salesDataTable?.ajax.reload();
};

// ==================== Helper Functions ====================

const syncClearButtonVisibility = () => {
    const invoice   = document.querySelector(FILTER_SELECTORS.invoice)?.value?.trim() ?? '';
    const startDate = document.querySelector(FILTER_SELECTORS.startDate)?.value ?? '';
    const endDate   = document.querySelector(FILTER_SELECTORS.endDate)?.value ?? '';
    const hasFilters = invoice || startDate || endDate;
    document.getElementById('clear-sales-filters')?.classList.toggle('d-none', !hasFilters);
};

// ==================== DataTable Initialization ====================

let salesDataTable = null;

$(() => {
    const columns = [
        {
            data: 'invoice_number',
            name: 'invoice_number',
            render: (data) => `<span class="fw-bolder">${escapeHtml(data)}</span>`,
        },
        {
            data: 'date',
            name: 'date',
            render: (data) => {
                if (!data) return 'N/A';
                return formatDate(data.slice(0, 10) + 'T00:00:00');
            },
        },
        {
            data: 'total',
            name: 'total',
            searchable: false,
            render: (data) => formatCurrency(data),
        },
        {
            data: 'payment_status',
            name: 'payment_status',
            searchable: false,
            orderable: false,
            className: 'dt-center',
            render: (data) => {
                return data === 'paid'
                    ? '<span class="badge border rounded-pill text-success-emphasis bg-success-subtle p-2">Pagado</span>'
                    : '<span class="badge border rounded-pill text-warning-emphasis bg-warning-subtle p-2">Pendiente</span>';
            },
        },
        {
            data: 'id',
            name: 'actions',
            searchable: false,
            orderable: false,
            className: 'dt-center',
            render: (id, type, row) => {
                const isAdmin   = row.is_admin;
                const printUrl  = MODEL_ROUTES.print.replace(':id', id);
                const showUrl   = MODEL_ROUTES.show.replace(':id', id);
                const deleteUrl = MODEL_ROUTES.delete.replace(':id', id);

                const btnPrint = `
                    <a class="btn btn-sm btn-primary me-2"
                       onclick="printSale('${printUrl}');"
                       data-bs-toggle="tooltip" data-bs-title="Reimprimir tiquete">
                        <i class="bi bi-printer"></i>
                    </a>`;

                const btnShow = `
                    <a class="info-button btn btn-sm btn-info me-2"
                       onclick="showSale('${showUrl}', this);"
                       data-bs-toggle="tooltip" data-bs-title="Ver detalles">
                        <div class="info-spinner d-none flex-row align-items-center justify-content-center">
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        </div>
                        <div class="info-button-text d-flex flex-row align-items-center justify-content-center">
                            <i class="bi bi-info-circle"></i>
                        </div>
                    </a>`;

                const btnDelete = isAdmin ? `
                    <form method="POST" action="${deleteUrl}"
                          onsubmit="deleteSale(event);"
                          class="delete-form d-inline-flex align-items-center">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit"
                                class="delete-form-button btn btn-sm btn-danger"
                                data-bs-toggle="tooltip" data-bs-title="Eliminar venta">
                            <div class="delete-form-spinner d-none flex-row align-items-center justify-content-center">
                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            </div>
                            <div class="delete-form-button-text d-flex flex-row align-items-center justify-content-center">
                                <i class="bi bi-trash"></i>
                            </div>
                        </button>
                    </form>` : '';

                return `<div class="d-flex align-items-center justify-content-center">${btnPrint}${btnShow}${btnDelete}</div>`;
            },
        },
    ];

    const customButtons = [
        {
            id: 'invoice-filter',
            type: 'input',
            inputType: 'text',
            placeholder: 'Ej: FAC-0001',
            label: 'N° Factura',
            wrapperClass: 'mb-0 w-auto',
        },
        {
            id: 'start-date-filter',
            type: 'date',
            label: 'Fecha Inicio',
            wrapperClass: 'mb-0 w-auto',
        },
        {
            id: 'end-date-filter',
            type: 'date',
            label: 'Fecha Fin',
            wrapperClass: 'mb-0 w-auto',
        },
        {
            type: 'button',
            id: 'apply-sales-filters',
            text: 'Filtrar',
            icon: 'bi-search',
            class: 'btn-primary btn-sm mb-0 align-self-center',
            func: window.applySalesFilters,
            funcName: 'applySalesFilters',
        },
        {
            type: 'button',
            id: 'clear-sales-filters',
            text: 'Limpiar',
            icon: 'bi-eraser-fill',
            class: 'btn-outline-primary btn-sm mb-0 align-self-center d-none',
            func: window.clearSalesFilters,
            funcName: 'clearSalesFilters',
        },
    ];

    const options = {
        showSearchBar: false,
        customButtonsPosition: 'top-end',
        order: [[1, 'desc']],
        ajax: {
            data: (req) => {
                const getVal    = (sel) => document.querySelector(sel)?.value ?? '';
                const invoice   = getVal(FILTER_SELECTORS.invoice).trim();
                const startDate = getVal(FILTER_SELECTORS.startDate);
                const endDate   = getVal(FILTER_SELECTORS.endDate);

                if (startDate && startDate > TODAY) {
                    SwalToast.fire({ icon: 'warning', title: 'La fecha de inicio no puede ser mayor a la fecha de hoy.' });
                    return req;
                }

                if ((startDate && !endDate) || (!startDate && endDate)) {
                    SwalToast.fire({ icon: 'warning', title: 'Debe ingresar tanto la fecha de inicio como la de fin.' });
                    return req;
                }

                if (invoice)   req.invoice_number = invoice;
                if (startDate) req.start_date      = startDate;
                if (endDate)   req.end_date        = endDate;
            },
        },
    };

    salesDataTable = CreateNewDataTable(
        'sales-history-table', MODEL_ROUTES.index, columns,
        {}, customButtons, options
    );

    $(document).on('submit', '.delete-form', function (e) {
        window.deleteSale(e);
    });

    Object.values(FILTER_SELECTORS).forEach((sel) => {
        document.querySelector(sel)?.addEventListener('input', syncClearButtonVisibility);
        document.querySelector(sel)?.addEventListener('change', syncClearButtonVisibility);
    });

    syncClearButtonVisibility();
});