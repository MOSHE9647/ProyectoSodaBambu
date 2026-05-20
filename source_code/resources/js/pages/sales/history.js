import { SwalConfirmation, SwalToast } from "../../utils/sweetalert.js";
import { printReceipt } from "../../utils/utils.js";

// 1. Forzamos a Vite a registrar e importar DataTables de manera local en el módulo
import 'datatables.net-bs5'; 

// 2. Nos aseguramos de capturar la instancia correcta de jQuery
const $ = window.$ || window.jQuery;

$(function () {
    const tableElement = $('#history-sales-table');
    const invoiceInput = $('#search-invoice');
    const startDateInput = $('#start-date');
    const endDateInput = $('#end-date');
    const filterButton = $('#btn-filter');
    const clearButton = $('#btn-clear');

    console.log("¡Script de historial detectado y ejecutándose con el módulo DataTables!");

    if (!tableElement.length) {
        console.log("Error: No se encontró la tabla con ID #history-sales-table");
        return;
    }

    // Inicializar el DataTable con corrección visual completa para Bootstrap 5
    const dt = tableElement.DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ordering: false,
        responsive: false, 
        autoWidth: false,  
        width: "100%",     // Fuerza el estiramiento horizontal completo de la tabla
        pagingType: 'simple_numbers', // Usa la paginación estilizada de Bootstrap
        // El parámetro 'dom' redistribuye los elementos nativos eliminando los estilos viejos
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
            { data: 'invoice_number', name: 'invoice_number' },
            { data: 'date', name: 'date' },
            { data: 'total', name: 'total', className: 'fw-bold text-success' },
            { data: 'payment_status', name: 'payment_status', className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        initComplete: function() {
            // Reajusta las celdas automáticamente al renderizar por primera vez
            this.api().columns.adjust();
        }
    });

    // Acción del Botón Filtrar
    filterButton.on('click', function (e) {
        e.preventDefault();
        dt.ajax.reload();
    });

    // Acción del Botón Limpiar
    clearButton.on('click', function (e) {
        e.preventDefault();
        invoiceInput.val('');
        startDateInput.val('');
        endDateInput.val('');
        dt.ajax.reload();
        SwalToast.fire({ icon: 'success', title: 'Filtros restaurados correctamente.' });
    });

    // =========================================================================
    // ACCIONES DE LA TABLA CORREGIDAS CON DELEGACIÓN DE EVENTOS (tbody.on)
    // =========================================================================

    // ACCIÓN: Visualizar Información (Modal Detalle)
    tableElement.find('tbody').on('click', '.btn-view-sale', function (e) {
        e.preventDefault();
        
        const tr = $(this).closest('tr');
        const rowData = dt.row(tr).data();
        
        console.log("Datos de la fila seleccionada:", rowData);

        if (!rowData) {
            SwalToast.fire({ icon: 'error', title: 'No se pudieron precargar los datos de esta venta.' });
            return;
        }

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

        if (rowData.sale_details && rowData.sale_details.length > 0) {
            rowData.sale_details.forEach(detail => {
                const productName = detail.product ? detail.product.name : 'Producto No Disponible';
                const quantity = detail.quantity ?? 0;
                const unitPrice = Number(detail.unit_price ?? 0).toLocaleString('es-CR');
                const subTotal = Number(detail.sub_total ?? 0).toLocaleString('es-CR');

                detailsHtml += `
                    <tr>
                        <td>${productName}</td>
                        <td class="text-center">${quantity}</td>
                        <td class="text-end">₡ ${unitPrice}</td>
                        <td class="text-end fw-semibold">₡ ${subTotal}</td>
                    </tr>
                `;
            });
        } else {
            detailsHtml += `<tr><td colspan="4" class="text-center text-muted">No hay productos registrados en esta transacción.</td></tr>`;
        }

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
        
        // Inicializamos y levantamos el modal usando la instancia nativa de Bootstrap 5 para entornos con Vite
        const myModal = new bootstrap.Modal(document.getElementById('viewSaleModal'));
        myModal.show();
    });

    // ACCIÓN: Reimprimir Tiquete
    tableElement.find('tbody').on('click', '.btn-print-sale', function (e) {
        e.preventDefault();
        const saleId = $(this).data('id');
        printReceipt(route('receipts.show', { model: 'sales', id: saleId }));
        SwalToast.fire({ icon: 'success', title: 'Enviando tiquete a la impresora...' });
    });

    // ACCIÓN: Eliminar Venta
    tableElement.find('tbody').on('click', '.btn-delete-sale', function (e) {
        e.preventDefault();
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