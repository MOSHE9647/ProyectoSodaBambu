import { formatTimeAgo, PaymentMethods, PaymentStatus, processSale, startTimeUpdateInterval } from "./api.js";
import { initializeSalesCart, getActiveSaleData, clearActiveCart } from "./cart.js";
import { initializeCashRegister } from "./cash-register.js";
import { initializeSalesProducts } from "./products.js";
import { initializeSalesOrderTabs } from "./orders.js";
import { setLoadingState } from "../../utils/utils.js";
import { initializeHotkeys } from "./hotkeys.js";
import { openPaymentModal } from "./payment.js";
import { SwalModal, SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

/**
 * Updates the sales page clock element with the current local time.
 * If the target element does not exist, no update is performed.
 */
const tickClock = () => {
	const currentTimeElement = $("#current-time");
	if (currentTimeElement.length) {
		const now = new Date();
		const formattedTime = now.toLocaleTimeString();
		currentTimeElement.text(formattedTime);
	}
};

/**
 * Updates the "last sale" label with a relative timestamp (e.g., "2 minutes ago").
 *
 * Reads the original sale timestamp from the `data-sale-time` attribute in
 * `#last-sale-time`, renders the relative text once, and then starts a periodic
 * updater so the displayed value stays current over time.
 *
 * If the target element or timestamp is missing, no action is performed.
 */
const updateLastSaleTime = () => {
	const lastSaleTimeElement = $("#last-sale-time");
	if (lastSaleTimeElement.length) {
		const lastSaleTime = lastSaleTimeElement.data("sale-time");
		if (lastSaleTime) {
			const relativeTime = formatTimeAgo(lastSaleTime);
			lastSaleTimeElement.text(relativeTime);

			// Start periodic updates so relative time stays current.
			startTimeUpdateInterval(lastSaleTimeElement, lastSaleTime);
		}
	}
}

/**
 * Boots all sales page modules and wires primary UI events.
 *
 * @returns {void}
 */
$(() => {
	// Initialize all sales-related components
	initializeCashRegister();
  	initializeSalesProducts();
  	initializeSalesCart();
	initializeSalesOrderTabs();
	initializeHotkeys();

	// Start current time ticker.
	tickClock();
	setInterval(tickClock, 1000); // Refresh clock every second.

	// Render and start auto-updating the last sale relative time.
	updateLastSaleTime();

	// Handle finalize sale action.
    const finalizeSaleButton = $("#finalize-sale-button");
    if (finalizeSaleButton.length) {
        finalizeSaleButton.on("click", async () => {
			const saleData = getActiveSaleData();

			let successMessage = "Venta registrada con éxito.";

			const { completed, printed } = await openPaymentModal({
				total: saleData.total,
				title: "Procesar Venta",
				loadingId: "finalize-sale",
				onComplete: async (paymentDetails, totalTendered) => {
					SwalModal.showLoading();
					const saleResult = await processSale(paymentDetails);
					if (saleResult?.success) {
						if (saleResult.message) {
							successMessage = saleResult.message;
						}
						return saleResult;
					}

					SwalModal.hideLoading();
					return false;
				}
			});

			if (completed && !printed) {
				SwalToast.fire({
					icon: SwalNotificationTypes.SUCCESS,
					title: successMessage,
				});
			}
		});
    }

	const rePrintLastSaleButton = $("#reprint-last-sale");
	if (rePrintLastSaleButton.length) {
		const SweetModalCustomClass = {
			title: "d-flex justify-content-center align-items-center border-bottom pb-3 mb-3",
			popup: "swal-popup w-auto h-auto",
			closeButton: "swal-close-btn fs-3",
			htmlContainer: "w-auto h-auto p-1 overflow-x-hidden",
			confirmButton: "btn btn-primary mx-1",
			cancelButton: "btn btn-danger mx-1",
			icon: "mb-4",
		};

		rePrintLastSaleButton.on("click", () => {
			SwalModal.fire({
				title: "Reimprimir última venta",
				text: "¿Deseas reimprimir el recibo de la última venta?",
				icon: "question",
				showCancelButton: true,
				customClass: SweetModalCustomClass,
				confirmButtonText: "Sí, reimprimir",
				cancelButtonText: "No, cancelar",
			}).then((result) => {
				if (result.isConfirmed) {
					// Placeholder for reprint logic; implement actual reprint functionality here.
					const lastSaleOrderId = $("#last-sale-order-id").text().trim();
					
if (lastSaleOrderId !== 'N/A') {
                        // Aquí llamamos a la ruta de impresión que ya existe
                        // Como en el historial usamos window.open, aquí hacemos lo mismo:
                        window.open(`/receipts/sale/${window.lastSaleId}`, '_blank');
                    } else {
                        SwalToast.fire({
                            icon: SwalNotificationTypes.ERROR,
                            title: "No hay ventas recientes para reimprimir",
                        });
                    }				}
			});
		});
	}
});

import $ from 'jquery';

$(document).ready(function() {
    let historyTable = null;
    
    // Inicializar DataTable cuando el modal está listo
    $('#historyModal').on('shown.bs.modal', function() {
        if (!historyTable) {
            historyTable = $('#sales-history-table').DataTable({
                language: { 
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' 
                },
                order: [[1, 'desc']],
                destroy: true // Permite reinicializar si es necesario
            });
        }
        
        // Cargar datos
        $.ajax({
            url: '/sales',
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                if (!historyTable) return;
                
                historyTable.clear();
                
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(sale => {
                        const date = new Date(sale.date).toLocaleString('es-CR');
                        const total = '₡ ' + parseFloat(sale.total).toLocaleString('es-CR');
                        const items = sale.sale_details ? sale.sale_details.length : 0;
                        
                        const actions = `
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-info view-details" data-id="${sale.id}" data-invoice="${sale.invoice_number}">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary reprint-sale" data-id="${sale.id}">
                                    <i class="bi bi-printer"></i>
                                </button>
                            </div>`;

                        historyTable.row.add([
                            `<strong>${sale.invoice_number}</strong>`,
                            date,
                            `<span class="text-success fw-bold">${total}</span>`,
                            `<span class="badge bg-secondary">${items}</span>`,
                            actions
                        ]);
                    });
                    historyTable.draw();
                } else {
                    // Mostrar mensaje de no hay datos
                    historyTable.row.add([
                        'No hay ventas registradas hoy',
                        '-',
                        '-',
                        '-',
                        '-'
                    ]);
                    historyTable.draw();
                }
            },
            error: function(xhr) {
                console.error("Error cargando ventas:", xhr.responseText);
                SwalToast.fire({
                    icon: SwalNotificationTypes.ERROR,
                    title: "Error al cargar el historial"
                });
            }
        });
    });
    
    // Ver detalles de la venta
    $('#sales-history-table').on('click', '.view-details', function() {
        const saleId = $(this).data('id');
        const invoiceNumber = $(this).data('invoice');
        
        // Mostrar loading en el modal
        $('#modal-details-body').html(`
            <tr>
                <td colspan="4" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </td>
            </tr>
        `);
        
        // Actualizar título
        $('#modal-invoice-number').text(invoiceNumber);
        
        // Cargar detalles
        $.ajax({
            url: `/sales/${saleId}/details`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                let html = '';
                let total = 0;
                
                if (response.details && response.details.length > 0) {
                    response.details.forEach(detail => {
                        const price = '₡ ' + parseFloat(detail.price).toLocaleString('es-CR');
                        const subtotal = '₡ ' + parseFloat(detail.subtotal).toLocaleString('es-CR');
                        total = parseFloat(response.total);
                        
                        html += `
                            <tr>
                                <td>${detail.product_name}</td>
                                <td class="text-end">${price}</td>
                                <td class="text-center">${detail.quantity}</td>
                                <td class="text-end">${subtotal}</td>
                            </tr>
                        `;
                    });
                    
                    $('#modal-total-amount').text('₡ ' + total.toLocaleString('es-CR'));
                } else {
                    html = '<tr><td colspan="4" class="text-center text-muted">No hay detalles disponibles</td></tr>';
                    $('#modal-total-amount').text('₡ 0');
                }
                
                $('#modal-details-body').html(html);
                $('#saleDetailsModal').modal('show');
            },
            error: function(xhr) {
                console.error("Error cargando detalles:", xhr);
                $('#modal-details-body').html(`
                    <tr>
                        <td colspan="4" class="text-center text-danger">
                            Error al cargar los detalles de la venta
                        </td>
                    </tr>
                `);
                $('#saleDetailsModal').modal('show');
            }
        });
    });
    
    // Reimprimir desde el historial
    $('#sales-history-table').on('click', '.reprint-sale', function() {
        const saleId = $(this).data('id');
        if (saleId) {
            window.open(`/receipts/sale/${saleId}`, '_blank');
        }
    });
});