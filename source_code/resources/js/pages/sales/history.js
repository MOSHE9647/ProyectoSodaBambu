import { SwalConfirmation, SwalToast } from "../../utils/sweetalert.js";
import { formatDate, printReceipt } from "../../utils/utils.js";


import 'datatables.net-bs5'; 


const $ = window.$ || window.jQuery;


$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || document.querySelector('input[name="_token"]')?.value
    }
});

$(function () {
    const tableElement = $('#history-sales-table');
    const invoiceInput = $('#search-invoice');
    const startDateInput = $('#start-date');
    const endDateInput = $('#end-date');
    const filterButton = $('#btn-filter');
    const clearButton = $('#btn-clear');

    if (!tableElement.length) return;

    const dt = tableElement.DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ordering: false,
        responsive: false, 
        pagingType: 'simple_numbers', 
        dom: "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3 align-items-center'<'col-sm-12 col-md-5 small text-muted'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
        ajax: {
            url: route('sales.index'),
            data: function (d) {
                d.invoice_number = invoiceInput.val();
                d.start_date = startDateInput.val();
                d.end_date = endDateInput.val();
            }
        },
        columns: [
            { 
                data: 'invoice_number', 
                name: 'invoice_number',
                className: 'align-middle text-start px-3'
            },
            { 
                data: 'date', 
                name: 'date',
                className: 'align-middle text-start px-3',
                render: function (data) {
                    if (!data) return 'N/A';
                    const datePart = data.split(' ')[0];
                    return formatDate(datePart + 'T00:00:00');
                }
            },
            { 
                data: 'total', 
                name: 'total', 
                className: 'align-middle text-start fw-bold text-success px-3' 
            },
            { 
                data: 'payment_status', 
                name: 'payment_status',
                className: 'align-middle text-start px-3'
            },
            { 
                data: 'actions', 
                name: 'actions', 
                orderable: false, 
                searchable: false,
                width: '140px',
                className: 'align-middle text-center px-3'
            }
        ],
        language: {
            processing: "Procesando...",
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Ningún dato disponible en esta tabla",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
            infoFiltered: "(filtrado de un total de _MAX_ registros)",
            search: "Buscar:",
            loadingRecords: "Cargando...",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        drawCallback: function() {
            this.api().columns.adjust();
        }
    });

    // Control de Filtros
    filterButton.on('click', function (e) {
        e.preventDefault();
        const startVal = startDateInput.val();
        const endVal = endDateInput.val();

        if ((startVal && !endVal) || (!startVal && endVal)) {
            SwalToast.fire({ icon: 'warning', title: 'Para filtrar por fecha debe ingresar tanto la fecha de inicio como la de fin.' });
            return;
        }
        dt.ajax.reload();
    });

    clearButton.on('click', function (e) {
        e.preventDefault();
        invoiceInput.val('');
        startDateInput.val('');
        endDateInput.val('');
        dt.ajax.reload();
    });

    tableElement.find('tbody').on('click', '.btn-view-sale', function (e) {
        e.preventDefault();
        const saleId = $(this).data('id');

        $.ajax({
            url: route('receipts.show', { model: 'sales', id: saleId }),
            type: 'GET',
            success: function (response) {
                if (!response.data) return;
                const receipt = response.data;
                const formatCurrency = (value) => '₡ ' + Number(value).toLocaleString('es-CR', { minimumFractionDigits: 0 });

                // ── Fecha formateada ──
                const fechaVenta = receipt.date
                    ? formatDate(receipt.date.split('T')[0] + 'T00:00:00')
                    : 'N/A';

                // ── Badge estado de pago ──
                const pagosBadge = receipt.payments && receipt.payments.length > 0
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">Pagado</span>'
                    : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2">Pendiente</span>';

                // ── Encabezado ──
                let detailsHtml = `
                    <div class="row g-3 mb-3 pb-3 border-bottom">
                        <div class="col-6 col-md-4">
                            <div class="small text-muted mb-1">N° Factura</div>
                            <div class="fw-semibold">${receipt.receipt_number}</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="small text-muted mb-1">Fecha</div>
                            <div class="fw-semibold">${fechaVenta}</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="small text-muted mb-1">Estado</div>
                            <div>${pagosBadge}</div>
                        </div>
                    </div>`;

                // ── Tabla de productos ──
                detailsHtml += `
                    <h6 class="fw-bold text-muted small text-uppercase mb-2">Productos</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>`;

                receipt.items.forEach(item => {
                    detailsHtml += `
                        <tr>
                            <td>${item.name}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">${formatCurrency(item.unit_price)}</td>
                            <td class="text-end">${formatCurrency(item.sub_total)}</td>
                        </tr>`;
                });

                detailsHtml += `</tbody></table></div>`;

                // ── Resumen de totales (idéntico al tiquete) ──
                detailsHtml += `
                    <div class="row justify-content-end mb-3">
                        <div class="col-12 col-md-5">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="text-muted">Subtotal</td>
                                    <td class="text-end">${formatCurrency(receipt.subtotal)}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Impuestos</td>
                                    <td class="text-end">${formatCurrency(receipt.tax_total)}</td>
                                </tr>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-end text-success">${formatCurrency(receipt.total)}</td>
                                </tr>
                            </table>
                        </div>
                    </div>`;

                // ── Detalle de pagos ──
                if (receipt.payments && receipt.payments.length > 0) {
                    detailsHtml += `
                        <h6 class="fw-bold text-muted small text-uppercase mb-2">Detalle de Pago</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Método</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-end">Vuelto</th>
                                        <th>Referencia</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                    receipt.payments.forEach(payment => {
                        const referencia = payment.reference ?? '—';
                        detailsHtml += `
                            <tr>
                                <td>${payment.method_label ?? payment.method ?? 'N/A'}</td>
                                <td class="text-end">${formatCurrency(payment.amount)}</td>
                                <td class="text-end">${formatCurrency(payment.change_amount ?? 0)}</td>
                                <td>${referencia}</td>
                            </tr>`;
                    });

                    detailsHtml += `</tbody></table></div>`;
                }

                $('#sale-modal-content').html(detailsHtml);
                new bootstrap.Modal(document.getElementById('viewSaleModal')).show();
            }
        });
    });

    // Reimprimir Tiquete
    tableElement.find('tbody').on('click', '.btn-print-sale', function (e) {
        e.preventDefault();
        const saleId = $(this).data('id');
        printReceipt(route('receipts.show', { model: 'sales', id: saleId }));
    });

    // Eliminar Venta
    tableElement.find('tbody').on('click', '.btn-delete-sale', function (e) {
        e.preventDefault();
        const saleId = $(this).data('id');

        SwalConfirmation.fire({
            title: '¿Estás seguro de eliminar esta venta?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('sales.destroy', { sale: saleId }),
                    type: 'DELETE',
                    success: function () {
                        dt.ajax.reload();
                        SwalToast.fire({ icon: 'success', title: 'Registro eliminado correctamente.' });
                    }
                });
            }
        });
    });
});