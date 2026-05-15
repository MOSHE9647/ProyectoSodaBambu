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

const CURRENCY_FORMATTER = new Intl.NumberFormat("es-CR", {
	style: "currency",
	currency: "CRC",
	minimumFractionDigits: 0,
	maximumFractionDigits: 0,
});
const formatCurrency = (amt) => CURRENCY_FORMATTER.format(Number(amt) || 0);

// --- CORE: MODAL DE PAGO ---

export async function openPaymentModal({ total, title, onComplete, loadingId, modelType = null, modelId = null }) {
	if (!total || total <= 0) return { completed: false };
	setLoadingState(loadingId, true);

	try {
		// Obtener Modal HTML
		const modalUrl = route("receipts.payment-modal", { paymentTotal: total });
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
					const paymentForm = popup.querySelector("#payment-form");

					paymentForm.addEventListener("submit", async (e) => {
						e.preventDefault();
						const { details, tendered, shouldPrint } = extractPaymentDetails(e.target, total);
						if (details.length === 0) return;

						try {
							// El módulo llamador procesa la lógica de guardado
							const result = await onComplete(details, tendered);
							
							if (result && result.success !== false) {
								if (shouldPrint) {
									// Determinamos el tipo de modelo para la ruta de impresión
									const type = modelType || (result.data?.invoice_number ? 'sales' : 'contracts');
									const id = modelId || result.data?.id;

									printReceipt(route('receipts.show', { 
										model: type, 
										id: id 
									}));
								}

								resolve({ 
									completed: true, 
									printed: shouldPrint, 
									result 
									});
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

export const printReceipt = (url) => {
	const printWindow = window.open(url, "_blank", "width=450,height=600");
	if (!printWindow) {
		SwalToast.fire({ 
			icon: SwalNotificationTypes.WARNING, 
			title: "El bloqueador de ventanas emergentes impidió abrir el tiquete." 
		});
		return;
	}
	printWindow.focus();
};