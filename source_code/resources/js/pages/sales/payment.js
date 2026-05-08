import { fetchWithErrorHandling } from "../../utils/error-handling.js";
import {
	SwalModal,
	SwalNotificationTypes,
	SwalToast,
} from "../../utils/sweetalert.js";
import { escapeHtml, setLoadingState } from "../../utils/utils.js";
import { PaymentMethods } from "./api.js";
import { getActiveSaleData } from "./cart.js";

// --- CONFIGURACIÓN Y CONSTANTES ---

const METHOD_LABELS = {
	[PaymentMethods.CASH]: "Efectivo",
	[PaymentMethods.CARD]: "Tarjeta",
	[PaymentMethods.SINPE]: "SINPE",
};

const BUSINESS_NAME = document.title?.trim() || "Soda El Bambu";

const CURRENCY_FORMATTER = new Intl.NumberFormat("es-CR", {
	style: "currency",
	currency: "CRC",
	minimumFractionDigits: 0,
	maximumFractionDigits: 0,
});

const RECEIPT_STYLES = `
	<style>
		@page { size: 80mm auto; margin: 0; }
		* { box-sizing: border-box; }
		body { margin: 0; background: #f4f4f4; color: #111; font-family: Consolas, monospace; font-size: 11px; line-height: 1.25; }
		.receipt-shell { width: min(80mm, 100vw); min-height: 100vh; margin: 0 auto; background: #fff; padding: 18px; }
		.receipt-header, .receipt-footer { text-align: center; }
		.business-name { font-size: 18px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
		.receipt-line { border-top: 1px dashed #111; margin: 8px 0; }
		.receipt-row { display: flex; justify-content: space-between; gap: 4px; }
		.receipt-item { margin-bottom: 8px; }
		.receipt-item-name { font-weight: 700; word-break: break-word; }
		.receipt-total { font-size: 16px; font-weight: 700; }
		@media print {
			body { background: #fff; font-size: 11px; }
			.receipt-shell { width: 80mm; min-height: auto; margin: 0; padding: 3mm; }
		}
	</style>
`;

// --- UTILIDADES ---

const formatCurrency = (amt) => CURRENCY_FORMATTER.format(Number(amt) || 0);

/**
 * Normalizador agnóstico: Maneja tanto la salida del ReceiptBuilder (PHP)
 * como los modelos crudos de Eloquent (Sale, Contract) que vienen del Store.
 */
const normalizeReceiptData = (raw) => {
	if (!raw) return { title: "Comprobante", date: new Date(), items: [], subtotal: 0, tax: 0, total: 0 };

	// 1. Extraer ítems de cualquier estructura posible
	const rawItems = raw.data.items || raw.data.details || raw.data.sale_details || raw.data.receipt_details || [];
	
	// 2. Mapear ítems asegurando nombres y totales de línea
	console.log("Raw items for receipt:", rawItems);
	const items = rawItems.map(i => {
		const qty = Number(i.quantity) || Number(raw.data.portions_per_day) || 1;
		const unitPrice = Number(i.unit_price) || Number(i.price) || Number(i.product?.sale_price) || 0;
		const sub = Number(i.sub_total) || Number(i.subtotal) || (qty * unitPrice);
		
		// Calcular impuesto si no viene calculado
		const taxPercent = Number(i.applied_tax) || 0;
		const taxAmt = Number(i.tax_amount) || Math.round(sub * (taxPercent / 100));

		return {
			name: i.name || i.product?.name || (i.product_id ? `Producto #${i.product_id}` : "Producto"),
			quantity: qty,
			price: unitPrice,
			total: i.total || (sub + taxAmt),
			sub_total: sub,
			tax_amount: taxAmt
		};
	});

	// 3. Totales inteligentes (Si el objeto raíz no los trae, los sumamos de los ítems)
	const subtotal = Number(raw.data.subtotal) || Number(raw.data.sub_total) || items.reduce((s, i) => s + i.sub_total, 0) || items.reduce((s, i) => s + (i.product?.sale_price || 0) * raw.portions_per_day, 0);
	const taxTotal = Number(raw.data.tax_total) || Number(raw.data.tax_amount) || items.reduce((s, i) => s + i.tax_amount, 0);
	const total = Number(raw.data.total) || Number(raw.total_value) || (subtotal + taxTotal);

	// 4. Título y fecha
	const title = raw.data.receipt_type || (raw.data.invoice_number ? `Factura: ${raw.data.invoice_number}` : (raw.data.receipt_number ? `Ref: ${raw.data.receipt_number}` : "Comprobante"));
	const date = raw.data.date || raw.data.receiptDate || raw.data.created_at || new Date();

	return { title, date, items, subtotal, tax: taxTotal, total };
};

// --- CORE: GENERACIÓN DE TIQUETE ---

export const buildReceiptHtml = ({ data, paymentDetails, totalTendered, changeAmount }) => {
	const info = normalizeReceiptData(data);
	const dateStr = new Date(info.date).toLocaleString("es-CR");

	const itemsHtml = info.items.map(i => `
		<div class="receipt-item">
			<div class="receipt-item-name">${escapeHtml(i.name)}</div>
			<div class="receipt-row">
				<span>${i.quantity} x ${formatCurrency(i.price)}</span>
				<span>${formatCurrency(i.total)}</span>
			</div>
		</div>
	`).join("");

	const paymentsHtml = paymentDetails.map(p => `
		<div class="receipt-row">
			<span>${escapeHtml(METHOD_LABELS[p.method] || p.method)}</span>
			<span>${formatCurrency(p.amount)}</span>
		</div>
		${p.reference ? `<div class="receipt-row text-muted" style="font-size: 9px;"><span>Ref.</span><span>${escapeHtml(p.reference)}</span></div>` : ""}
	`).join("");

	return `
		<!doctype html>
		<html lang="es">
		<head><meta charset="utf-8">${RECEIPT_STYLES}</head>
		<body>
			<div class="receipt-shell">
				<main class="receipt">
					<header class="receipt-header">
						<div class="business-name">${escapeHtml(BUSINESS_NAME)}</div>
						<div>${escapeHtml(info.title)}</div>
						<div>${dateStr}</div>
					</header>
					<div class="receipt-line"></div>
					${itemsHtml}
					<div class="receipt-line"></div>
					<div class="receipt-row"><span>Subtotal</span><span>${formatCurrency(info.subtotal)}</span></div>
					<div class="receipt-row"><span>Impuestos</span><span>${formatCurrency(info.tax)}</span></div>
					<div class="receipt-row receipt-total"><span>Total</span><span>${formatCurrency(info.total)}</span></div>
					<div class="receipt-line"></div>
					<div class="receipt-item-name">Detalle de Pago</div>
					${paymentsHtml}
					<div class="receipt-row"><span>Recibido</span><span>${formatCurrency(totalTendered)}</span></div>
					<div class="receipt-row"><span>Vuelto</span><span>${formatCurrency(changeAmount)}</span></div>
					<div class="receipt-line"></div>
					<footer class="receipt-footer">¡Gracias por su visita!</footer>
				</main>
			</div>
		</body>
		</html>
	`;
};

// --- CORE: MODAL DE PAGO ---

export async function openPaymentModal({ total, title, onComplete, loadingId, modelType = null, modelId = null }) {
	if (!total || total <= 0) return { completed: false };
	setLoadingState(loadingId, true);

	try {
		let receiptData = null;
		
		// 1. Obtener data base (Agnóstico)
		if (modelType && modelId) {
			const resp = await fetch(route("receipts.show", { model: modelType, id: modelId }));
			receiptData = await resp.json();
		} else {
			receiptData = getActiveSaleData();
		}

		// 2. Obtener Modal HTML
		const modalUrl = modelType 
			? route("receipts.payment-modal", { model: modelType, id: modelId, paymentTotal: total })
			: route("sales.payment-modal", { paymentTotal: total });
		
		const modalResp = await fetch(modalUrl);
		const modalHtml = await modalResp.text();
		setLoadingState(loadingId, false);

		return await new Promise((resolve) => {
			SwalModal.fire({
				title: `${title}: ${formatCurrency(total)}`,
				html: modalHtml,
				showConfirmButton: false,
				didOpen: () => {
					const popup = SwalModal.getPopup();
					initializePaymentModalUI(popup, { total });

					popup.querySelector("#payment-form").addEventListener("submit", async (e) => {
						e.preventDefault();
						const { details, tendered, shouldPrint } = extractPaymentDetails(e.target, total);
						if (details.length === 0) return;

						try {
							// result.data contendrá el modelo guardado (Sale o Contract)
							const result = await onComplete(details, tendered, receiptData);
							if (result && result.success !== false) {
								if (shouldPrint) {
									// Aquí el normalizador ahora maneja tanto result.data (Laravel) como receiptData (Builder)
									const html = buildReceiptHtml({ 
										data: result || receiptData, 
										paymentDetails: details, 
										totalTendered: tendered, 
										changeAmount: Math.max(0, tendered - total) 
									});
									await printReceipt(html);
								}
								resolve({ completed: true, result });
								SwalModal.close();
							}
						} catch (error) {
							console.error(error);
							SwalToast.fire({ icon: SwalNotificationTypes.ERROR, title: "Error al procesar el pago" });
						}
					});
				},
				willClose: () => resolve({ completed: false })
			});
		});
	} catch (error) {
		console.error(error);
		setLoadingState(loadingId, false);
		SwalToast.fire({ icon: SwalNotificationTypes.ERROR, title: "Error en el flujo de pago" });
		return { completed: false };
	}
}

// --- GESTIÓN DE UI ---

function initializePaymentModalUI(popup, saleData) {
	const refs = {
		amount: popup.querySelector("#payment-amount-input"),
		reference: popup.querySelector("#payment-reference-input"),
		refGroup: popup.querySelector("#payment-reference-group"),
		summary: popup.querySelector("#payment-summary-list"),
		total: popup.querySelector("#payment-total"),
		paid: popup.querySelector("#payment-paid"),
		remaining: popup.querySelector("#payment-remaining"),
		change: popup.querySelector("#payment-change"),
		changePreview: popup.querySelector("#payment-change-preview"),
		completeBtn: popup.querySelector("#complete-sale-button"),
		addBtn: popup.querySelector("#add-payment-button"),
		rowsContainer: popup.querySelector("#payment-rows-container")
	};

	const saleTotal = Math.round(saleData.total || 0);
	let payments = [];
	let selectedMethod = PaymentMethods.CASH;
	let currentFocus = "amount";

	const refreshUI = () => {
		const paidTotal = payments.reduce((sum, p) => sum + p.amount, 0);
		const remaining = Math.max(0, saleTotal - paidTotal);
		const change = Math.max(0, paidTotal - saleTotal);

		refs.total.textContent = formatCurrency(saleTotal);
		refs.paid.textContent = formatCurrency(paidTotal);
		refs.remaining.textContent = formatCurrency(remaining);
		refs.change.textContent = formatCurrency(change);

		refs.completeBtn.disabled = remaining > 0 || payments.length === 0;
		refs.addBtn.disabled = remaining === 0;
		refs.amount.value = remaining > 0 ? remaining : 0;

		renderSummary();
		renderHiddenInputs();
		updateChangePreview();
	};

	const renderSummary = () => {
		refs.summary.innerHTML = payments.length ? payments.map((p, i) => `
			<div class="payment-summary-item d-flex justify-content-between align-items-center mb-2">
				<div>
					<span class="fw-bold">${METHOD_LABELS[p.method]}</span>
					${p.reference ? `<br><small class="text-muted">Ref: ${p.reference}</small>` : ""}
				</div>
				<div class="d-flex align-items-center gap-2">
					<span class="text-success fw-bold">${formatCurrency(p.amount)}</span>
					<button type="button" class="btn btn-outline-danger btn-sm" data-remove-index="${i}">
						<i class="bi bi-trash"></i>
					</button>
				</div>
			</div>
		`).join("") : '<div class="text-center text-muted py-2">No hay pagos agregados</div>';
	};

	const renderHiddenInputs = () => {
		refs.rowsContainer.innerHTML = payments.map(p => `
			<div class="payment-row">
				<input type="hidden" class="payment-method" value="${p.method}">
				<input type="hidden" class="payment-amount" value="${p.amount}">
				<input type="hidden" class="payment-reference" value="${p.reference || ''}">
			</div>
		`).join("");
	};

	const updateChangePreview = () => {
		if (selectedMethod !== PaymentMethods.CASH) {
			refs.changePreview.classList.add("d-none");
			return;
		}
		const alreadyPaid = payments.reduce((sum, p) => sum + p.amount, 0);
		const currentInput = parseInt(refs.amount.value) || 0;
		const estimatedChange = Math.max(0, currentInput - (saleTotal - alreadyPaid));
		refs.changePreview.textContent = `Vuelto: ${formatCurrency(estimatedChange)}`;
		refs.changePreview.classList.remove("d-none");
	};

	popup.addEventListener("click", (e) => {
		const methodBtn = e.target.closest("[data-payment-method]");
		if (methodBtn) {
			selectedMethod = methodBtn.dataset.paymentMethod;
			popup.querySelectorAll("[data-payment-method]").forEach(b => b.classList.toggle("is-active", b === methodBtn));
			refs.refGroup.classList.toggle("d-none", selectedMethod === PaymentMethods.CASH);
			currentFocus = "amount";
			refreshUI();
			return;
		}

		const keyBtn = e.target.closest("[data-keyboard-key]");
		if (keyBtn) {
			const key = keyBtn.dataset.keyboardKey;
			const target = currentFocus === "reference" ? refs.reference : refs.amount;

			if (key === "delete") {
				target.value = target.value.slice(0, -1) || (currentFocus === "amount" ? "0" : "");
			} else if (["100", "500", "1000"].includes(key) && currentFocus === "amount") {
				target.value = (parseInt(target.value) || 0) + parseInt(key);
			} else {
				target.value = (target.value === "0" && currentFocus === "amount") ? key : target.value + key;
			}
			updateChangePreview();
			return;
		}

		const removeBtn = e.target.closest("[data-remove-index]");
		if (removeBtn) {
			payments.splice(parseInt(removeBtn.dataset.removeIndex), 1);
			refreshUI();
		}
	});

	refs.amount.onfocus = () => currentFocus = "amount";
	refs.reference.onfocus = () => currentFocus = "reference";

	refs.addBtn.onclick = () => {
		const amount = parseInt(refs.amount.value) || 0;
		const reference = refs.reference.value.trim();

		if (amount <= 0) return;
		if (selectedMethod !== PaymentMethods.CASH && (!reference || reference.length < 8)) {
			return SwalToast.fire({ icon: SwalNotificationTypes.WARNING, title: "Referencia requerida (mín. 8 dígitos)" });
		}

		if (selectedMethod === PaymentMethods.CASH) {
			const existing = payments.find(p => p.method === PaymentMethods.CASH);
			if (existing) existing.amount += amount;
			else payments.push({ method: selectedMethod, amount, reference: null });
		} else {
			payments.push({ method: selectedMethod, amount, reference });
		}

		refs.reference.value = "";
		refreshUI();
	};

	refreshUI();
}

// --- UTILIDADES DE SALIDA ---

const extractPaymentDetails = (form, total) => {
	const details = [];
	let totalTendered = 0;

	form.querySelectorAll(".payment-row").forEach(row => {
		const amount = parseInt(row.querySelector(".payment-amount").value);
		const method = row.querySelector(".payment-method").value;
		const reference = row.querySelector(".payment-reference").value || null;
		
		totalTendered += amount;
		details.push({ method, amount, reference });
	});

	if (totalTendered < total) {
		SwalToast.fire({ icon: SwalNotificationTypes.WARNING, title: "El pago está incompleto" });
		return { details: [] };
	}

	const cashPayment = details.find(p => p.method === PaymentMethods.CASH);
	if (cashPayment) {
		cashPayment.change_amount = Math.max(0, totalTendered - total);
	}

	return {
		details,
		tendered: totalTendered,
		shouldPrint: form.querySelector("#print-receipt-checkbox")?.checked || false
	};
};

const printReceipt = async (html) => {
	const printWindow = window.open("", "_blank", "width=450,height=600");
	if (!printWindow) {
		await SwalModal.fire({
			title: "Tiquete",
			html: `<iframe style="width:100%;height:400px;border:none;" srcdoc="${escapeHtml(html)}"></iframe>`,
			confirmButtonText: "Cerrar"
		});
		return;
	}
	printWindow.document.write(html);
	printWindow.document.close();
	
	setTimeout(() => {
		printWindow.focus();
		printWindow.print();
	}, 250);
};