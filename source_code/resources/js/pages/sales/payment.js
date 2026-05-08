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

const BUSINESS_NAME = document.title?.trim() || "Soda El Bambu";

const getReceiptDate = (saleData) => {
	const dateValue = saleData?.date ? new Date(saleData.date) : new Date();
	return Number.isNaN(dateValue.getTime())
		? new Date().toLocaleString("es-CR")
		: dateValue.toLocaleString("es-CR");
};

const getReceiptItems = (saleResultData, saleSnapshot) => {
	const snapshotItems = saleSnapshot?.receipt_details || [];
	const resultItems = saleResultData?.sale_details || [];

	if (snapshotItems.length > 0) {
		return snapshotItems;
	}

	return resultItems.map((item) => ({
		...item,
		name: item.product?.name || `Producto #${item.product_id || "N/A"}`,
		tax_amount: Number(item.sub_total || 0) * Number(item.applied_tax || 0),
		total:
			Number(item.sub_total || 0) +
			Number(item.sub_total || 0) * Number(item.applied_tax || 0),
	}));
};

const getReceiptStyles = () => `
	<style>
		@page {
			size: 80mm auto;
			margin: 0;
		}

		* {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			background: #f4f4f4;
			color: #111;
			font-family: Consolas, "Courier New", monospace;
			font-size: 11px;
			line-height: 1.25;
		}

		.receipt-shell {
			width: min(80mm, 100vw);
			min-height: 100vh;
			margin: 0 auto;
			background: #fff;
			padding: 18px;
		}

		.receipt {
			width: min(80mm, 100%);
			margin: 0 auto;
		}

		.receipt-header,
		.receipt-footer {
			text-align: center;
		}

		.business-name {
			font-size: 20px;
			font-weight: 700;
			text-transform: uppercase;
			margin-bottom: 8px;
		}

		.receipt-line {
			border-top: 1px dashed #111;
			margin: 10px 0;
		}

		.receipt-row {
			display: flex;
			justify-content: space-between;
			gap: 4px;
		}

		.receipt-label {
			white-space: nowrap;
		}

		.receipt-value {
			text-align: right;
			word-break: break-word;
		}

		.receipt-item {
			margin-bottom: 10px;
		}

		.receipt-item-name {
			font-weight: 700;
			word-break: break-word;
		}

		.receipt-total {
			font-size: 18px;
			font-weight: 700;
		}

		@media print {
			@page {
				size: 80mm auto;
				margin: 0;
			}

			body {
				background: #fff;
				font-size: 11px;
			}

			.receipt-shell {
				width: 80mm;
				min-height: auto;
				margin: 0;
				padding: 3mm;
			}

			.receipt {
				width: 72mm;
			}

			.business-name {
				font-size: 15px;
				margin-bottom: 2mm;
			}

			.receipt-line {
				margin: 2mm 0;
			}

			.receipt-item {
				margin-bottom: 2mm;
			}

			.receipt-total {
				font-size: 13px;
			}
		}
	</style>
`;

const buildReceiptHtml = ({
	saleResultData,
	saleSnapshot,
	paymentDetails,
	totalTendered,
	changeAmount,
}) => {
	const items = getReceiptItems(saleResultData, saleSnapshot);
	const subtotal = items.reduce(
		(sum, item) => toIntegerAmount(sum + Number(item.sub_total || 0)),
		0,
	);
	const taxTotal = items.reduce(
		(sum, item) =>
			toIntegerAmount(
				sum +
					(Number(item.tax_amount) ||
						Number(item.sub_total || 0) * Number(item.applied_tax || 0)),
			),
		0,
	);
	const total = Number(saleResultData?.total || saleSnapshot?.total || 0);
	const invoiceNumber = saleResultData?.invoice_number || "N/A";

	const itemsHtml = items
		.map((item) => {
			const quantity = Number(item.quantity || 0);
			const unitPrice = Number(item.unit_price || 0);
			const lineTotal =
				Number(item.total) ||
				Number(item.sub_total || 0) +
					Number(item.sub_total || 0) * Number(item.applied_tax || 0);

			return `
				<div class="receipt-item">
					<div class="receipt-item-name">${escapeHtml(item.name || `Producto #${item.product_id || "N/A"}`)}</div>
					<div class="receipt-row">
						<span>${escapeHtml(quantity)} x ${formatCurrency(unitPrice)}</span>
						<span>${formatCurrency(lineTotal)}</span>
					</div>
				</div>
			`;
		})
		.join("");

	const paymentsHtml = paymentDetails
		.map(
			(payment) => `
				<div class="receipt-row">
					<span>${escapeHtml(METHOD_LABELS[payment.method] || payment.method)}</span>
					<span>${formatCurrency(payment.amount)}</span>
				</div>
				${
					payment.reference
						? `<div class="receipt-row"><span>Ref.</span><span>${escapeHtml(payment.reference)}</span></div>`
						: ""
				}
			`,
		)
		.join("");

	return `
		<!doctype html>
		<html lang="es">
		<head>
			<meta charset="utf-8">
			<title>Tiquete ${escapeHtml(invoiceNumber)}</title>
			${getReceiptStyles()}
		</head>
		<body>
			<div class="receipt-shell">
				<main class="receipt">
					<header class="receipt-header">
						<div class="business-name">${escapeHtml(BUSINESS_NAME)}</div>
						<div>Comprobante de venta</div>
						<div>${escapeHtml(getReceiptDate(saleResultData))}</div>
						<div>Factura: ${escapeHtml(invoiceNumber)}</div>
					</header>

					<div class="receipt-line"></div>

					<section>
						${itemsHtml}
					</section>

					<div class="receipt-line"></div>

					<section>
						<div class="receipt-row">
							<span>Subtotal</span>
							<span>${formatCurrency(subtotal)}</span>
						</div>
						<div class="receipt-row">
							<span>Impuestos</span>
							<span>${formatCurrency(taxTotal)}</span>
						</div>
						<div class="receipt-row receipt-total">
							<span>Total</span>
							<span>${formatCurrency(total)}</span>
						</div>
					</section>

					<div class="receipt-line"></div>

					<section>
						<div class="receipt-item-name">Métodos de pago</div>
						${paymentsHtml}
						<div class="receipt-row">
							<span>Total recibido</span>
							<span>${formatCurrency(totalTendered)}</span>
						</div>
						<div class="receipt-row">
							<span>Vuelto</span>
							<span>${formatCurrency(changeAmount)}</span>
						</div>
					</section>

					<div class="receipt-line"></div>

					<footer class="receipt-footer">
						Gracias por su compra
					</footer>
				</main>
			</div>
		</body>
		</html>
	`;
};

const showReceiptPreview = async (receiptHtml) => {
	await SwalModal.fire({
		title: "Tiquete",
		html: `<iframe title="Vista previa del tiquete" style="width: 86mm; height: 70vh; border: 1px solid #ddd; background: #fff;" srcdoc="${escapeHtml(receiptHtml)}"></iframe>`,
		width: 420,
		showConfirmButton: true,
		confirmButtonText: "Cerrar",
		customClass: {
			confirmButton: "btn btn-success mx-1",
			htmlContainer: "w-auto h-auto p-1 overflow-x-hidden",
		},
	});
};

const printReceipt = async (receiptHtml) => {
	const printWindow = window.open("", "_blank", "width=520,height=760");

	if (!printWindow) {
		await showReceiptPreview(receiptHtml);
		return;
	}

	printWindow.document.open();
	printWindow.document.write(receiptHtml);
	printWindow.document.close();

	window.setTimeout(() => {
		printWindow.focus();
		printWindow.print();
	}, 250);
};

const toIntegerAmount = (value) => Math.round(Number(value) || 0);

const formatAmountInputValue = (value) => parseInt(toIntegerAmount(value));

const parseAmountInputValue = (value) => {
	const normalizedValue = String(value || "")
		.replace(/\s+/g, "")
		.replace(/[^\d]/g, "");
	const parsedValue = Number.parseInt(normalizedValue, 10);
	return Number.isFinite(parsedValue) ? parsedValue : 0;
};

const sanitizeAmountInputValue = (value) => {
	return String(value || "").replace(/[^\d]/g, "");
};

const appendKeyboardValue = (currentValue, appendedValue) => {
	if (!currentValue || currentValue === "0") {
		return String(appendedValue);
	}

	return `${currentValue}${appendedValue}`;
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

	const saleTotal = toIntegerAmount(saleData.total || 0);
	const payments = [];
	let selectedMethod = PaymentMethods.CASH;
	let keyboardTarget = "amount";

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
			keyboardTarget = "amount";
		}
	};

	const updateCashChangePreview = () => {
		if (selectedMethod !== PaymentMethods.CASH) {
			paymentChangePreview.classList.add("d-none");
			paymentChangePreview.textContent = "";
			return;
		}

		const paidTotal = payments.reduce(
			(sum, payment) => toIntegerAmount(sum + Number(payment.amount || 0)),
			0,
		);
		const remainingBeforeCurrent = toIntegerAmount(Math.max(0, saleTotal - paidTotal));
		const receivedAmount = parseAmountInputValue(amountInput.value);
		const estimatedChange = toIntegerAmount(Math.max(0, receivedAmount - remainingBeforeCurrent));

		paymentChangePreview.textContent = `Vuelto estimado: ${formatCurrency(estimatedChange)}`;
		paymentChangePreview.classList.remove("d-none");
	};

	const refreshTotals = () => {
		const paidTotal = payments.reduce(
			(sum, payment) => toIntegerAmount(sum + Number(payment.amount || 0)),
			0,
		);
		const remaining = toIntegerAmount(Math.max(0, saleTotal - paidTotal));
		const change = toIntegerAmount(Math.max(0, paidTotal - saleTotal));

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

	amountInput.addEventListener("focus", () => {
		keyboardTarget = "amount";
	});

	referenceInput.addEventListener("input", () => {
		referenceInput.value = referenceInput.value.replace(/\D/g, "");
	});

	referenceInput.addEventListener("focus", () => {
		keyboardTarget = "reference";
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
		keyboardTarget = "amount";
		amountInput.focus();
		updateCashChangePreview();
	});

	keyboardBtns.forEach((btn) => {
		btn.addEventListener("click", () => {
			const key = btn.dataset.keyboardKey;
			const isReferenceTarget =
				keyboardTarget === "reference" &&
				selectedMethod !== PaymentMethods.CASH &&
				!referenceGroup.classList.contains("d-none");

			if (isReferenceTarget) {
				if (/^\d+$/.test(key)) {
					referenceInput.value = `${referenceInput.value}${key}`.replace(/\D/g, "");
				} else if (key === "delete") {
					referenceInput.value = referenceInput.value.slice(0, -1);
				}

				referenceInput.focus();
				return;
			}

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
						toIntegerAmount(currentAmount + Number(key)),
					);
					break;

				default:
					break;
			}

			updateCashChangePreview();
			amountInput.focus();
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
			(sum, payment) => toIntegerAmount(sum + Number(payment.amount || 0)),
			0,
		);
		const remaining = toIntegerAmount(Math.max(0, saleTotal - paidTotal));

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
				existingCashPayment.amount = toIntegerAmount(existingCashPayment.amount + amount);
				referenceInput.value = "";
				refreshTotals();
				return;
			}
		}

		payments.push({
			method: selectedMethod,
			amount: toIntegerAmount(amount),
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
	const saleTotal = toIntegerAmount(saleData.total || 0);

	const paymentRows = paymentForm.querySelectorAll(".payment-row");
	paymentRows.forEach((row) => {
		const methodElement = row.querySelector(".payment-method");
		const amountElement = row.querySelector(".payment-amount");
		const referenceElement = row.querySelector(".payment-reference");

		const method = methodElement?.value;
		const amount = parseAmountInputValue(amountElement?.value || "0");
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
	const roundedChangeAmount = toIntegerAmount(changeAmount);
	const cashPaymentIndex = paymentDetails.findIndex(
		(payment) => payment.method === PaymentMethods.CASH,
	);

	if (cashPaymentIndex >= 0) {
		paymentDetails[cashPaymentIndex].change_amount = roundedChangeAmount;
	}

	const shouldPrintReceipt =
		paymentForm.querySelector("#print-receipt-checkbox")?.checked === true;
	let receiptHtml = null;

	SwalModal.showLoading();

	const saleResult = await processSale(paymentDetails);
	if (saleResult?.success) {
		if (shouldPrintReceipt) {
			receiptHtml = buildReceiptHtml({
				saleResultData: saleResult,
				saleSnapshot: saleData,
				paymentDetails,
				totalTendered,
				changeAmount: roundedChangeAmount,
			});
		}
		SwalModal.close();

		if (shouldPrintReceipt && receiptHtml) {
			await printReceipt(
				buildReceiptHtml({
					saleResultData: saleResult.data,
					saleSnapshot: saleData,
					paymentDetails,
					totalTendered,
					changeAmount: roundedChangeAmount,
				}),
			);
		} else {
			await SwalModal.fire({
				icon: SwalNotificationTypes.SUCCESS,
				title: "Venta exitosa",
				text: saleResult.message || "La venta fue registrada correctamente.",
				confirmButtonText: "Aceptar",
				customClass: {
					confirmButton: "btn btn-success mx-1",
				},
			});
		}
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
