import { fetchWithErrorHandling } from "../../utils/error-handling.js";
import {
	SwalModal,
	SwalNotificationTypes,
	SwalToast,
} from "../../utils/sweetalert.js";
import { setLoadingState } from "../../utils/utils.js";
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

const escapeHtml = (value) =>
	String(value ?? "")
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/\"/g, "&quot;")
		.replace(/'/g, "&#039;");

const roundToTwo = (value) => Math.round((Number(value) || 0) * 100) / 100;

const formatAmountInputValue = (value) => roundToTwo(value).toFixed(2);

const parseAmountInputValue = (value) => {
	const normalizedValue = String(value || "")
		.replace(/\s+/g, "")
		.replace(",", ".");
	const parsedValue = Number.parseFloat(normalizedValue);
	return Number.isFinite(parsedValue) ? roundToTwo(parsedValue) : 0;
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
		const existingAlert = popup.querySelector("#payment-inline-alert");
		if (existingAlert) {
			existingAlert.remove();
		}

		const alertBackdrop = document.createElement("div");
		alertBackdrop.id = "payment-inline-alert";
		alertBackdrop.className = "payment-inline-alert-backdrop";
		alertBackdrop.innerHTML = `
			<div class="payment-inline-alert-card" role="alertdialog" aria-live="assertive" aria-modal="true">
				<div class="payment-inline-alert-title">Referencia requerida</div>
				<div class="small">El número de referencia es obligatorio para SINPE y Tarjeta.</div>
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
				referenceInput.focus();
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
		updateCashChangePreview();
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
			SwalToast.fire({
				icon: SwalNotificationTypes.WARNING,
				title: "Ingresa un monto valido para agregar el pago.",
			});
			return;
		}

		if (selectedMethod !== PaymentMethods.CASH && reference.length === 0) {
			showReferenceRequiredAlert();
			return;
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
			SwalToast.fire({
				icon: SwalNotificationTypes.WARNING,
				title: "Tarjeta y SINPE no deben exceder el restante.",
			});
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
 * Handles the payment form submission flow for a sale.
 *
 * @async
 * @function paymentFormEventListener
 * @param {SubmitEvent} event - The form submission event.
 * @param {{ total: number|string }} saleData - Sale information used for payment validation and change calculation.
 * @returns {Promise<void>} Resolves when the payment submission flow completes.
 */
const paymentFormEventListener = async (event, saleData) => {
	event.preventDefault();

	const paymentForm = event.currentTarget;
	if (!paymentForm) {
		return;
	}

	const paymentDetails = [];
	let totalTendered = 0;
	const saleTotal = Number(saleData.total || 0);

	const paymentRows = paymentForm.querySelectorAll(".payment-row");
	paymentRows.forEach((row) => {
		const methodElement = row.querySelector(".payment-method");
		const amountElement = row.querySelector(".payment-amount");
		const referenceElement = row.querySelector(".payment-reference");

		const method = methodElement?.value;
		const amount = parseFloat(amountElement?.value || "0") || 0;
		const reference = referenceElement?.value || null;

		if (!method) {
			return;
		}

		totalTendered += amount;

		paymentDetails.push({
			method,
			amount,
			reference: method !== PaymentMethods.CASH ? reference : null,
			change_amount: 0,
		});
	});

	if (paymentDetails.length === 0) {
		SwalToast.fire({
			icon: SwalNotificationTypes.WARNING,
			title: "Debes agregar al menos un método de pago.",
		});
		return;
	}

	if (totalTendered < saleTotal) {
		SwalToast.fire({
			icon: SwalNotificationTypes.WARNING,
			title: `El monto ingresado (${formatCurrency(totalTendered)}) es menor al total de la venta (${formatCurrency(saleTotal)}).`,
		});
		return;
	}

	const changeAmount = Math.max(0, totalTendered - saleTotal);
		const roundedChangeAmount = roundToTwo(changeAmount);
	const cashPaymentIndex = paymentDetails.findIndex(
		(payment) => payment.method === PaymentMethods.CASH,
	);

	if (cashPaymentIndex >= 0) {
			paymentDetails[cashPaymentIndex].change_amount = roundedChangeAmount;
	}

	SwalModal.showLoading();

	const saleResult = await processSale(paymentDetails);
	if (saleResult?.success) {
		SwalModal.close();

		await SwalModal.fire({
			title: "",
			html: getSaleSuccessSummaryHtml(saleResult),
			width: 760,
			background: "#ffffff",
			color: "#1f1f1f",
			showConfirmButton: true,
			showCancelButton: false,
			confirmButtonText: "Cerrar",
			allowEscapeKey: true,
			allowOutsideClick: true,
			customClass: {
				popup: "swal-popup w-auto h-auto",
				title: "d-flex justify-content-start align-items-center border-bottom pb-3 mb-3",
				closeButton: "swal-close-btn fs-3",
				htmlContainer: "w-auto h-auto p-1 overflow-x-hidden",
				confirmButton: "btn btn-success mx-1",
				cancelButton: "btn btn-outline-secondary mx-1",
				icon: "mb-4",
			},
		});
	} else {
		SwalModal.hideLoading();
	}
};

/**
 * Opens and renders the payment modal for the current sale flow.
 *
 * @async
 * @function showPaymentModal
 * @returns {Promise<void>} Resolves when the modal flow has been handled.
 */
export async function showPaymentModal() {
	const saleData = getActiveSaleData();
	if (!saleData || saleData.sale_details.length === 0) {
		SwalToast.fire({
			icon: SwalNotificationTypes.ERROR,
			title: "El carrito está vacío. Agrega productos antes de cobrar.",
		});
		return;
	}

	setLoadingState("finalize-sale", true);

	try {
		const url = route("sales.payment-modal", {
			paymentTotal: saleData.total,
		});
		const response = await fetchWithErrorHandling(url);
		const modalHtml = await response.text();

		setLoadingState("finalize-sale", false);

		if (modalHtml) {
			SwalModal.fire({
				title: `Procesar pago: ${formatCurrency(saleData.total)}`,
				showConfirmButton: false,
				showCancelButton: false,
				showCloseButton: true,
				allowEscapeKey: false,
				allowOutsideClick: false,
				html: `${modalHtml}`,
				didOpen: () => {
					const popup = SwalModal.getPopup();
					if (!popup) {
						return;
					}

					initializePaymentModalUI(popup, saleData);

					const paymentForm = popup?.querySelector("#payment-form");
					if (paymentForm) {
						paymentForm.addEventListener("submit", (event) =>
							paymentFormEventListener(event, saleData),
						);
					}
				},
			});
		}
	} catch (error) {
		console.error("Error loading payment modal:", error);
		setLoadingState("finalize-sale", false);
		SwalToast.fire({
			icon: SwalNotificationTypes.ERROR,
			title: "Ocurrió un problema al abrir la pantalla de pago.",
		});
	}
}
