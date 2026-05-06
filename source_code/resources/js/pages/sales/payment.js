import { fetchWithErrorHandling } from "../../utils/error-handling.js";
import {
	SwalModal,
	SwalNotificationTypes,
	SwalToast,
} from "../../utils/sweetalert.js";
import { escapeHtml, setLoadingState } from "../../utils/utils.js";
import { PaymentMethods, processSale } from "./api.js";
import { getActiveSaleData } from "./cart.js";

const METHOD_LABELS = {
	[PaymentMethods.CASH]: "Efectivo",
	[PaymentMethods.CARD]: "Tarjeta",
	[PaymentMethods.SINPE]: "SINPE",
};

const formatCurrency = (amount) => {
	const value = Number(amount) || 0;
	return `₡ ${value.toLocaleString("es-CR", {
		minimumFractionDigits: 0,
		maximumFractionDigits: 0,
	})}`;
};

const roundToTwo = (value) => Math.round((Number(value) || 0) * 100) / 100;

const formatAmountInputValue = (value) => roundToTwo(value).toFixed(2);

const parseAmountInputValue = (value) => {
	const normalizedValue = String(value || "")
		.replace(/\s+/g, "")
		.replace(",", ".");
	const parsedValue = Number.parseFloat(normalizedValue);
	return Number.isFinite(parsedValue) ? roundToTwo(parsedValue) : 0;
};

const sanitizeAmountInputValue = (value) => {
	const sanitized = String(value || "")
		.replace(/[^0-9.,]/g, "")
		.replace(/[,\.]/g, (match, index, str) => {
			return str.indexOf(match) === index ? "." : "";
		});
	return sanitized;
};

const appendKeyboardValue = (currentValue, appendedValue) => {
	if (!currentValue || currentValue === "0") {
		return String(appendedValue);
	}

	return `${currentValue}${appendedValue}`;
};

const getSaleSuccessSummaryHtml = (saleResult) => {
	const saleData = saleResult?.data || {};
	const invoiceNumber = saleData.invoice_number || "N/A";
	const paymentStatusRaw = String(saleData.payment_status || "").toLowerCase();
	const paymentStatus =
		paymentStatusRaw === "paid"
			? "Pagada"
			: paymentStatusRaw === "pending"
				? "Pendiente"
				: "N/A";
	const total = Number(saleData.total || 0);
	const dateValue = saleData.date ? new Date(saleData.date) : null;
	const formattedDate = dateValue && !Number.isNaN(dateValue.getTime())
		? dateValue.toLocaleString("es-CR")
		: "N/A";

	return `
		<div style="text-align: left; color: #1f1f1f;">
			<div style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; color: #198754;">Venta exitosa</div>
			<div style="border: 1px solid #e9ecef; border-radius: 10px; background: #ffffff; padding: 14px; font-size: 1rem;">
				<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
					<span>Factura:</span>
					<span style="font-weight: 600;">${escapeHtml(invoiceNumber)}</span>
				</div>
				<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
					<span>Estado del pago:</span>
					<span style="font-weight: 600;">${escapeHtml(paymentStatus)}</span>
				</div>
				<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
					<span>Total:</span>
					<span style="font-weight: 700; color: #198754;">${formatCurrency(total)}</span>
				</div>
				<div style="display: flex; justify-content: space-between;">
					<span>Fecha:</span>
					<span style="font-weight: 600;">${escapeHtml(formattedDate)}</span>
				</div>
			</div>
		</div>
	`;
};

const renderHiddenPaymentRows = (rowsContainer, payments) => {
	rowsContainer.innerHTML = "";

	payments.forEach((payment) => {
		const row = document.createElement("div");
		row.className = "payment-row d-none";

		const methodInput = document.createElement("input");
		methodInput.type = "hidden";
		methodInput.className = "payment-method";
		methodInput.value = payment.method;

		const amountInput = document.createElement("input");
		amountInput.type = "hidden";
		amountInput.className = "payment-amount";
		amountInput.value = formatAmountInputValue(payment.amount);

		const referenceInput = document.createElement("input");
		referenceInput.type = "hidden";
		referenceInput.className = "payment-reference";
		referenceInput.value = payment.reference || "";

		row.appendChild(methodInput);
		row.appendChild(amountInput);
		row.appendChild(referenceInput);

		rowsContainer.appendChild(row);
	});
};

const initializePaymentModalUI = (popup, saleData) => {
	const methodButtons = popup.querySelectorAll("[data-payment-method]");
	const amountInput = popup.querySelector("#payment-amount-input");
	const clearPaymentAmountButton = popup.querySelector("#clear-payment-amount-button");
	const paymentChangePreview = popup.querySelector("#payment-change-preview");
	const referenceInput = popup.querySelector("#payment-reference-input");
	const referenceGroup = popup.querySelector("#payment-reference-group");
	const keyboardBtns = popup.querySelectorAll("[data-keyboard-key]");
	const addPaymentButton = popup.querySelector("#add-payment-button");
	const paymentSummaryList = popup.querySelector("#payment-summary-list");
	const paymentTotalElement = popup.querySelector("#payment-total");
	const paymentPaidElement = popup.querySelector("#payment-paid");
	const paymentRemainingElement = popup.querySelector("#payment-remaining");
	const paymentChangeElement = popup.querySelector("#payment-change");
	const completeSaleButton = popup.querySelector("#complete-sale-button");
	const paymentRowsContainer = popup.querySelector("#payment-rows-container");

	if (
		!methodButtons.length ||
		!amountInput ||
		!clearPaymentAmountButton ||
		!paymentChangePreview ||
		!referenceInput ||
		!referenceGroup ||
		!keyboardBtns.length ||
		!addPaymentButton ||
		!paymentSummaryList ||
		!paymentTotalElement ||
		!paymentPaidElement ||
		!paymentRemainingElement ||
		!paymentChangeElement ||
		!completeSaleButton ||
		!paymentRowsContainer
	) {
		return;
	}

	const saleTotal = Number(saleData.total || 0);
	const payments = [];
	let selectedMethod = PaymentMethods.CASH;

	const showReferenceRequiredAlert = () => {
		showPaymentInlineAlert(
			"Referencia requerida",
			"El número de referencia es obligatorio para SINPE y Tarjeta.",
			referenceInput,
		);
	};

	const showReferenceInvalidAlert = () => {
		showPaymentInlineAlert(
			"Referencia inválida",
			"La referencia debe tener entre 8 y 12 dígitos para Tarjeta o SINPE.",
			referenceInput,
		);
	};

	const showPaymentInlineAlert = (title, message, focusElement = null) => {
		const existingAlert = popup.querySelector("#payment-inline-alert");
		if (existingAlert) {
			existingAlert.remove();
		}

		const alertBackdrop = document.createElement("div");
		alertBackdrop.id = "payment-inline-alert";
		alertBackdrop.className = "payment-inline-alert-backdrop";
		alertBackdrop.innerHTML = `
			<div class="payment-inline-alert-card" role="alertdialog" aria-live="assertive" aria-modal="true">
				<div class="payment-inline-alert-title">${title}</div>
				<div class="small">${message}</div>
				<div class="payment-inline-alert-actions">
					<button type="button" class="btn btn-success btn-sm" id="payment-inline-alert-accept">Aceptar</button>
				</div>
			</div>
		`;

		popup.appendChild(alertBackdrop);

		const acceptButton = alertBackdrop.querySelector("#payment-inline-alert-accept");
		if (acceptButton) {
			acceptButton.addEventListener("click", () => {
				alertBackdrop.remove();
				if (focusElement) {
					focusElement.focus();
				}
			});
		}
	};

	const renderSummary = () => {
		if (payments.length === 0) {
			paymentSummaryList.innerHTML = `
				<div class="small text-muted">Aun no agregaste pagos.</div>
			`;
			return;
		}

		paymentSummaryList.innerHTML = payments
			.map(
				(payment, index) => `
					<div class="payment-summary-item d-flex justify-content-between align-items-center">
						<div class="d-flex flex-column">
							<span class="fw-semibold">${METHOD_LABELS[payment.method] || payment.method}</span>
							${payment.reference ? `<span class="text-muted small">Ref: ${payment.reference}</span>` : ""}
						</div>
						<div class="d-flex align-items-center gap-2">
							<span class="fw-semibold text-success">${formatCurrency(payment.amount)}</span>
							<button type="button" class="btn btn-danger btn-sm payment-remove-btn" data-remove-index="${index}" aria-label="Eliminar pago">
								<i class="bi bi-x-lg"></i>
							</button>
						</div>
					</div>
				`,
			)
			.join("");

		paymentSummaryList
			.querySelectorAll(".payment-remove-btn")
			.forEach((button) => {
				button.addEventListener("click", () => {
					const removeIndex = Number(button.dataset.removeIndex);
					if (Number.isInteger(removeIndex) && payments[removeIndex]) {
						payments.splice(removeIndex, 1);
						refreshTotals();
					}
				});
			});
	};

	const setSelectedMethod = (method) => {
		selectedMethod = method;
		const requiresReference = method !== PaymentMethods.CASH;

		methodButtons.forEach((button) => {
			const isActive = button.dataset.paymentMethod === method;
			button.classList.toggle("is-active", isActive);
		});

		referenceGroup.classList.toggle("d-none", !requiresReference);
		if (!requiresReference) {
			referenceInput.value = "";
		}
	};

	const updateCashChangePreview = () => {
		if (selectedMethod !== PaymentMethods.CASH) {
			paymentChangePreview.classList.add("d-none");
			paymentChangePreview.textContent = "";
			return;
		}

		const paidTotal = payments.reduce(
			(sum, payment) => roundToTwo(sum + Number(payment.amount || 0)),
			0,
		);
		const remainingBeforeCurrent = roundToTwo(Math.max(0, saleTotal - paidTotal));
		const receivedAmount = parseAmountInputValue(amountInput.value);
		const estimatedChange = roundToTwo(Math.max(0, receivedAmount - remainingBeforeCurrent));

		paymentChangePreview.textContent = `Vuelto estimado: ${formatCurrency(estimatedChange)}`;
		paymentChangePreview.classList.remove("d-none");
	};

	const refreshTotals = () => {
		const paidTotal = payments.reduce(
			(sum, payment) => roundToTwo(sum + Number(payment.amount || 0)),
			0,
		);
		const remaining = roundToTwo(Math.max(0, saleTotal - paidTotal));
		const change = roundToTwo(Math.max(0, paidTotal - saleTotal));

		paymentTotalElement.textContent = formatCurrency(saleTotal);
		paymentPaidElement.textContent = formatCurrency(paidTotal);
		paymentRemainingElement.textContent = formatCurrency(remaining);
		paymentChangeElement.textContent = formatCurrency(change);

		completeSaleButton.disabled = remaining > 0 || payments.length === 0;
		addPaymentButton.disabled = remaining === 0;

		if (remaining > 0) {
			amountInput.value = formatAmountInputValue(remaining);
		} else {
			amountInput.value = formatAmountInputValue(0);
		}

		renderSummary();
		renderHiddenPaymentRows(paymentRowsContainer, payments);
		updateCashChangePreview();
	};

	methodButtons.forEach((button) => {
		button.addEventListener("click", () => {
			setSelectedMethod(button.dataset.paymentMethod);
			refreshTotals();
		});
	});

	amountInput.addEventListener("input", () => {
		amountInput.value = sanitizeAmountInputValue(amountInput.value);
		updateCashChangePreview();
	});

	referenceInput.addEventListener("input", () => {
		referenceInput.value = referenceInput.value.replace(/\D/g, "");
	});

	referenceInput.addEventListener("keydown", (event) => {
		const allowedKeys = [
			"Backspace",
			"Tab",
			"ArrowLeft",
			"ArrowRight",
			"Delete",
			"Home",
			"End",
			"Enter",
		];

		if (allowedKeys.includes(event.key)) {
			return;
		}

		if (/^[0-9]$/.test(event.key)) {
			return;
		}

		event.preventDefault();
	});

	amountInput.addEventListener("keydown", (event) => {
		const allowedKeys = [
			"Backspace",
			"Tab",
			"ArrowLeft",
			"ArrowRight",
			"Delete",
			"Home",
			"End",
			"Enter",
			".",
			",",
		];

		if (allowedKeys.includes(event.key)) {
			return;
		}

		if (/^[0-9]$/.test(event.key)) {
			return;
		}

		event.preventDefault();
	});

	clearPaymentAmountButton.addEventListener("click", () => {
		amountInput.value = "";
		amountInput.focus();
		updateCashChangePreview();
	});

	keyboardBtns.forEach((btn) => {
		btn.addEventListener("click", () => {
			const key = btn.dataset.keyboardKey;
			let currentValue = amountInput.value || "0";
			const currentAmount = parseAmountInputValue(currentValue);

			switch (key) {
				case "0":
				case "1":
				case "2":
				case "3":
				case "4":
				case "5":
				case "6":
				case "7":
				case "8":
				case "9":
					amountInput.value = appendKeyboardValue(currentValue, key);
					break;

				case "00":
				case "000":
					amountInput.value = appendKeyboardValue(currentValue, key);
					break;

				case "delete":
					if (currentValue.length > 1) {
						amountInput.value = currentValue.slice(0, -1);
					} else {
						amountInput.value = "0";
					}
					break;

				case "100":
				case "500":
				case "1000":
					amountInput.value = formatAmountInputValue(
						roundToTwo(currentAmount + Number(key)),
					);
					break;

				default:
					break;
			}

			updateCashChangePreview();
		});
	});

	addPaymentButton.addEventListener("click", () => {
		const amount = parseAmountInputValue(amountInput.value);
		const reference = referenceInput.value.trim();

		if (!Number.isFinite(amount) || amount <= 0) {
			showPaymentInlineAlert(
				"Monto inválido",
				"Ingresa un monto válido para agregar el pago.",
				amountInput,
			);
			return;
		}

		if (selectedMethod !== PaymentMethods.CASH) {
			if (reference.length === 0) {
				showReferenceRequiredAlert();
				return;
			}

			if (!/^[0-9]{8,12}$/.test(reference)) {
				showReferenceInvalidAlert();
				return;
			}
		}

		const paidTotal = payments.reduce(
			(sum, payment) => roundToTwo(sum + Number(payment.amount || 0)),
			0,
		);
		const remaining = roundToTwo(Math.max(0, saleTotal - paidTotal));

		if (remaining === 0) {
			SwalToast.fire({
				icon: SwalNotificationTypes.INFO,
				title: "La factura ya esta cubierta por completo.",
			});
			return;
		}

		if (selectedMethod !== PaymentMethods.CASH && amount > remaining) {
			showPaymentInlineAlert(
				"Monto demasiado alto",
				"Tarjeta y SINPE no deben exceder el restante.",
				amountInput,
			);
			return;
		}

		// Acumular pagos en efectivo si ya existe uno
		if (selectedMethod === PaymentMethods.CASH) {
			const existingCashPayment = payments.find(
				(p) => p.method === PaymentMethods.CASH,
			);

			if (existingCashPayment) {
				existingCashPayment.amount = roundToTwo(existingCashPayment.amount + amount);
				referenceInput.value = "";
				refreshTotals();
				return;
			}
		}

		payments.push({
			method: selectedMethod,
			amount: roundToTwo(amount),
			reference:
				selectedMethod === PaymentMethods.CASH || reference.length === 0
					? null
					: reference,
		});

		referenceInput.value = "";
		refreshTotals();
	});

	setSelectedMethod(PaymentMethods.CASH);
	refreshTotals();
};

/**
 * Muestra el modal de pago de forma genérica.
 * @param {Object} config
 * @param {number} config.total - Monto total a cobrar.
 * @param {string} config.title - Título del modal (ej: "Pago de Contrato").
 * @param {Function} config.onComplete - Callback que recibe (paymentDetails, totalTendered).
 * @param {string} [config.loadingId] - ID del botón para el estado de carga.
 */
export async function openPaymentModal({ total, title, onComplete, loadingId = "finalize-action" }) {
    if (!total || total <= 0) {
        SwalToast.fire({ icon: SwalNotificationTypes.ERROR, title: "El monto debe ser mayor a 0." });
        return;
    }

    setLoadingState(loadingId, true);

    try {
        // El endpoint ahora podría ser más genérico en el backend
        const url = route("sales.payment-modal", { paymentTotal: total }); 
        const response = await fetch(url);
        const modalHtml = await response.text();

        setLoadingState(loadingId, false);

        if (modalHtml) {
            SwalModal.fire({
                title: `${title}: ${formatCurrency(total)}`,
                html: modalHtml,
                showConfirmButton: false,
                didOpen: () => {
                    const popup = SwalModal.getPopup();
                    const paymentData = { total, payments: [] };

                    // Inicializar la UI pasando el callback de completado
                    initializePaymentModalUI(popup, paymentData);

                    const paymentForm = popup.querySelector("#payment-form");
                    paymentForm.addEventListener("submit", async (e) => {
                        e.preventDefault();
                        const { details, tendered } = extractPaymentDetails(paymentForm, total);
                        
                        if (details.length > 0) {
                            await onComplete(details, tendered);
                        }
                    });
                },
            });
        }
    } catch (error) {
        console.error("Error loading payment modal:", error);
        setLoadingState(loadingId, false);
        SwalToast.fire({ icon: SwalNotificationTypes.ERROR, title: "Error al abrir la pantalla de pago." });
    }
}

// Función auxiliar para extraer datos sin procesar la lógica de negocio
const extractPaymentDetails = (form, saleTotal) => {
    const paymentDetails = [];
    let totalTendered = 0;

    form.querySelectorAll(".payment-row").forEach((row) => {
        const amount = parseFloat(row.querySelector(".payment-amount").value) || 0;
        const method = row.querySelector(".payment-method").value;
        totalTendered += amount;

        paymentDetails.push({
            method,
            amount,
            reference: method !== PaymentMethods.CASH ? row.querySelector(".payment-reference").value : null,
        });
    });

    // Validar que el monto cubra el total
    if (totalTendered < saleTotal) {
        SwalToast.fire({ 
            icon: SwalNotificationTypes.WARNING, 
            title: `Monto insuficiente (${formatCurrency(totalTendered)} de ${formatCurrency(saleTotal)})` 
        });
        return { details: [], tendered: 0 };
    }

	const changeAmount = Math.max(0, totalTendered - saleTotal);
	const roundedChangeAmount = roundToTwo(changeAmount);
	const cashPaymentIndex = paymentDetails.findIndex(
		(payment) => payment.method === PaymentMethods.CASH,
	);

	if (cashPaymentIndex >= 0) {
		paymentDetails[cashPaymentIndex].change_amount = roundedChangeAmount;
	}

    return { details: paymentDetails, tendered: totalTendered };
};
