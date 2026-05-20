import { SwalConfirmation, SwalToast } from "../../utils/sweetalert.js";
import { printReceipt } from "../../utils/utils.js";

// Forzamos la ejecución directa apenas el DOM esté listo
$(function () {
    const tableElement = $('#history-sales-table');
    const invoiceInput = $('#search-invoice');
    const startDateInput = $('#start-date');
    const endDateInput = $('#end-date');
    const filterButton = $('#btn-filter');
    const clearButton = $('#btn-clear');

    console.log("¡Script de historial detectado y ejecutándose!"); // Esto saldrá en tu consola fija para saber que entró

    if (!tableElement.length) {
        console.log("Error: No se encontró la tabla con ID #history-sales-table");
        return;
    }

    // 1. Inicializar el DataTable rompiendo cualquier bloqueo previo
    const dt = tableElement.DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ordering: false,
        ajax: {
            url: route('sales.index'), // Si da error por Ziggy, usaremos la URL nativa '/sales'
            data: function (d) {
                d.invoice_number = invoiceInput.val();
                d.start_date = startDateInput.val();
                d.end_date = endDateInput.val();
            }
        },
        columns: [
            { data: 'invoice_number', name: 'invoice_number' },
            { data: 'date', name: 'date' },
            { data: 'total', name: 'total', className: 'fw-bold text-success' },
            { data: 'payment_status', name: 'payment_status', className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });

    // 2. Acción del Botón Filtrar
    filterButton.on('click', function (e) {
        e.preventDefault();
        dt.ajax.reload();
    });

    // 3. Acción del Botón Limpiar
    clearButton.on('click', function (e) {
        e.preventDefault();
        invoiceInput.val('');
        startDateInput.val('');
        endDateInput.val('');
        dt.ajax.reload();
        SwalToast.fire({ icon: 'success', title: 'Filtros restaurados correctamente.' });
    });

    // 4. ACCIÓN: Reimprimir Tiquete
    tableElement.on('click', '.btn-print-sale', function () {
        const saleId = $(this).data('id');
        printReceipt(route('receipts.show', { model: 'sales', id: saleId }));
        SwalToast.fire({ icon: 'success', title: 'Enviando tiquete a la impresora...' });
    });

    // 5. ACCIÓN: Visualizar Información (Modal Detalle)
    tableElement.on('click', '.btn-view-sale', function () {
        const rowData = dt.row($(this).closest('tr')).data();
        if (!rowData) return;

        let detailsHtml = `
            <div class="row mb-3 pb-2 border-bottom">
                <div class="col-md-6 mb-2"><strong>N° Factura:</strong> <span class="text-muted">${rowData.invoice_number}</span></div>
                <div class="col-md-6 mb-2"><strong>Fecha Emisión:</strong> <span class="text-muted">${rowData.date}</span></div>
            </div>
            <h6 class="fw-bold mb-2"><i class="bi bi-box-seam me-1"></i> Productos incluidos:</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered align-middle">
                    <thead>
                        <tr class="table-light">
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">Precio Unitario</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        rowData.sale_details.forEach(detail => {
            const productName = detail.product ? detail.product.name : 'Producto No Disponible';
            detailsHtml += `
                <tr>
                    <td>${productName}</td>
                    <td class="text-center">${detail.quantity}</td>
                    <td class="text-end">₡ ${Number(detail.unit_price).toLocaleString('es-CR')}</td>
                    <td class="text-end fw-semibold">₡ ${Number(detail.sub_total).toLocaleString('es-CR')}</td>
                </tr>
            `;
        });

        detailsHtml += `
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end align-items-center mt-3 pt-2 border-top">
                <span class="fs-5 fw-bold text-dark me-2">Monto Total:</span>
                <span class="fs-4 fw-bold text-success">${rowData.total}</span>
            </div>
        `;

        $('#sale-modal-content').html(detailsHtml);
        $('#viewSaleModal').modal('show');
    });

    // 6. ACCIÓN: Eliminar Venta
    tableElement.on('click', '.btn-delete-sale', function () {
        const saleId = $(this).data('id');

        SwalConfirmation.fire({
            title: '¿Estás seguro de eliminar esta venta?',
            text: "Esta acción removerá el registro de forma permanente del historial.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('sales.destroy', { sale: saleId }),
                    type: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        dt.ajax.reload();
                        SwalToast.fire({ icon: 'success', title: response.message });
                    },
                    error: function (xhr) {
                        SwalToast.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Error al procesar la solicitud.' });
                    }
                });
            }
        });
    });
});