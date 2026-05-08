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

	return resultItems.map((item) => {
		const taxRate = (Number(item.applied_tax) || 0) / 100;
		const itemTotal = Number(item.sub_total || 0); // Assuming API returns total as sub_total
		const itemBasePrice = Math.round(itemTotal / (1 + taxRate));
		const itemTax = itemTotal - itemBasePrice;

		return {
			...item,
			name: item.product?.name || `Producto #${item.product_id || "N/A"}`,
			tax_amount: itemTax,
			total: itemTotal,
			sub_total: itemBasePrice,
		};
	});
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
						Number(item.sub_total || 0) *
							Number(item.applied_tax || 0)),
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
	const clearPaymentAmountButton = popup.querySelector(
		"#clear-payment-amount-button",
	);
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

		const acceptButton = alertBackdrop.querySelector(
			"#payment-inline-alert-accept",
		);
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
					if (
						Number.isInteger(removeIndex) &&
						payments[removeIndex]
					) {
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
			(sum, payment) =>
				toIntegerAmount(sum + Number(payment.amount || 0)),
			0,
		);
		const remainingBeforeCurrent = toIntegerAmount(
			Math.max(0, saleTotal - paidTotal),
		);
		const receivedAmount = parseAmountInputValue(amountInput.value);
		const estimatedChange = toIntegerAmount(
			Math.max(0, receivedAmount - remainingBeforeCurrent),
		);

		paymentChangePreview.textContent = `Vuelto estimado: ${formatCurrency(estimatedChange)}`;
		paymentChangePreview.classList.remove("d-none");
	};

	const refreshTotals = () => {
		const paidTotal = payments.reduce(
			(sum, payment) =>
				toIntegerAmount(sum + Number(payment.amount || 0)),
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
					referenceInput.value =
						`${referenceInput.value}${key}`.replace(/\D/g, "");
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
			(sum, payment) =>
				toIntegerAmount(sum + Number(payment.amount || 0)),
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
				existingCashPayment.amount = toIntegerAmount(
					existingCashPayment.amount + amount,
				);
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
		return { completed: false, reason: "invalid-total" };
    }

    setLoadingState(loadingId, true);

    try {
        // El endpoint ahora podría ser más genérico en el backend
        const url = route("sales.payment-modal", { paymentTotal: total }); 
        const response = await fetch(url);
        const modalHtml = await response.text();

        setLoadingState(loadingId, false);

		if (!modalHtml) {
			return { completed: false, reason: "empty-modal" };
		}

		return await new Promise((resolve) => {
			let isResolved = false;

			const resolveOnce = (result) => {
				if (isResolved) {
					return;
				}

				isResolved = true;
				resolve(result);
			};

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
					if (!paymentForm) {
						resolveOnce({ completed: false, reason: "missing-form" });
						SwalModal.close();
						return;
					}

					paymentForm.addEventListener("submit", async (e) => {
						e.preventDefault();
						const { details, tendered, shouldPrint } = extractPaymentDetails(paymentForm, total);

						if (details.length === 0) {
							return;
						}

						// Capturamos el estado actual del carrito antes de que onComplete lo limpie
						const saleSnapshot = getActiveSaleData();

						try {
							const saleResult = await onComplete(details, tendered);
							if (!saleResult || saleResult.success === false) {
								return;
							}

							if (shouldPrint) {
								const receiptHtml = buildReceiptHtml({
									saleResultData: saleResult.data,
									saleSnapshot: saleSnapshot,
									paymentDetails: details,
									totalTendered: tendered,
									changeAmount: Math.max(0, tendered - total),
								});
								await printReceipt(receiptHtml);
							}

							resolveOnce({ completed: true, details, tendered, printed: shouldPrint });
							SwalModal.close();
						} catch (submissionError) {
							console.error("Error while completing payment modal:", submissionError);
							SwalToast.fire({
								icon: SwalNotificationTypes.ERROR,
								title: "No se pudo completar el pago.",
							});
						}
					});
				},
				willClose: () => {
					resolveOnce({ completed: false, reason: "closed" });
				},
			});
		});
    } catch (error) {
        console.error("Error loading payment modal:", error);
        setLoadingState(loadingId, false);
        SwalToast.fire({ icon: SwalNotificationTypes.ERROR, title: "Error al abrir la pantalla de pago." });
		return { completed: false, reason: "load-error" };
    }
}

// Función auxiliar para extraer datos sin procesar la lógica de negocio
const extractPaymentDetails = (form, saleTotal) => {
    const paymentDetails = [];
    let totalTendered = 0;
	const shouldPrint = form.querySelector("#print-receipt-checkbox")?.checked === true;

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
        return { details: [], tendered: 0, shouldPrint: false };
    }

	const changeAmount = Math.max(0, totalTendered - saleTotal);
	const roundedChangeAmount = toIntegerAmount(changeAmount);
	const cashPaymentIndex = paymentDetails.findIndex(
		(payment) => payment.method === PaymentMethods.CASH,
	);

	if (cashPaymentIndex >= 0) {
		paymentDetails[cashPaymentIndex].change_amount = roundedChangeAmount;
	}

    return { details: paymentDetails, tendered: totalTendered, shouldPrint };
};