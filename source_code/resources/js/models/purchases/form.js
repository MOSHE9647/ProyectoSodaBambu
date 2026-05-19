import { clearAllFieldErrors, clearFieldError, showFieldError } from "../../utils/validation";
import { getLaravelFirstError, setLoadingState, printReceipt } from "../../utils/utils";
import { SwalConfirmation, SwalModal, SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";
import { bindPurchaseFormEvents } from "./items.js";
import { bindOffcanvasEvents } from "../../utils/offcanvas.js";
import { showPaymentDetailsFormModal } from "../payment/main.js";
import { initializeCashRegister } from "../../pages/sales/cash-register.js";

// ==================== Environment Checks ====================

if (typeof $ === 'undefined') throw new Error('This script requires jQuery');

// ======================== Constants =========================

const PURCHASE_DATA = window.purchaseFormData || [];
const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-purchase-form' : 'create-purchase-form';

const PaymentStatus = {
	PAID: PURCHASE_DATA.paymentStatuses?.find(s => s.value === 'paid')?.value || 'paid',
	PENDING: PURCHASE_DATA.paymentStatuses?.find(s => s.value === 'pending')?.value || 'pending',
};

let initialPurchaseState = null;

// =========================== Helpers ==========================

const parseFormattedNumber = (text) => {
	if (!text) return 0;
	return parseInt(text.replace(/[^0-9,-]+/g, "").replace(",", ".")) || 0;
};

const showFieldErrorInAlert = (fieldId, errorMessage) => {
	const $alert = $("#form-error-alert");
	$alert.removeClass("d-none").html(`
        <i class="bi bi-exclamation-triangle me-2"></i>
        <span>${errorMessage}</span>
    `);

	const targetFieldId = fieldId instanceof $ ? fieldId.attr("id") : fieldId;
	if (targetFieldId) $(`#${targetFieldId}`).focus();

	window.scrollTo({ top: $alert.offset()?.top - 100, behavior: "smooth" });
};

const clearFieldErrorInAlert = () => $("#form-error-alert").addClass("d-none").empty();

const getFormFields = () => {
    const purchaseDetails = $("#purchase-details-table tbody tr:not(#empty-row)")
		.map((_, row) => {
			const $row = $(row);
			return {
				id: $row.data("id") || null,
				quantity: parseInt($row.find('[name="quantity"]').val()) || 0,
				unit_price: parseFormattedNumber($row.find('[name="unit-price"]').val()),
				sub_total: parseFormattedNumber($row.find(".sub-total").text()),
				purchasable_id: parseInt($row.find('[name="purchasable_id"]').val()) || null,
				purchasable_type: $row.data("purchasable-type"),
			};
		}).get();

    return {
		invoice_number: $("#invoice_number").val()?.trim() || '',
		supplier_id: parseInt($("#supplier_id").val()) || null,
		payment_status: $("#payment_status").val(),
		date: $("#date").val(),
        total: parseFormattedNumber($("#total").text()),
		notes: $("#notes").val()?.trim() || '',
        purchase_details: purchaseDetails
	};
};

// ===================== Validation Helpers =====================

const rules = {
	isNum: (v) => v !== "" && !isNaN(v) && v !== null,
	isPositiveMultipleOf5: (v) => rules.isNum(v) && parseInt(v) >= 0 && parseInt(v) % 5 === 0,
	isString: (v) => typeof v === "string" && v.trim().length > 0,
	isValidId: (v) => /^\d+$/.test(v) && v !== "-1",
    inList: (v, list, key = 'value') => list.some(item => String(item[key]) === String(v))
};

const baseFieldValidators = {
	supplier_id: { 
		validate: v => rules.isValidId(v), 
		message: "Debe seleccionar un proveedor válido." 
	},
	invoice_number: { 
		validate: v => rules.isString(v) && v.length >= 2 && v.length <= 255, 
		message: "El número de factura es obligatorio (2-255 caracteres)." 
	},
	payment_status: { 
		validate: v => rules.inList(v, PURCHASE_DATA.paymentStatuses || []), 
		message: "Debe seleccionar un estado de pago válido." 
	},
	date: {
		validate: (v) => {
			if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false;
			const formatter = new Intl.DateTimeFormat('en-CA', { 
				timeZone: 'America/Costa_Rica', 
				year: 'numeric', 
				month: '2-digit', 
				day: '2-digit' 
			});
			return v <= formatter.format(new Date());
		},
		message: "La fecha es obligatoria y no puede ser futura.",
	},
	total: { 
		validate: v => rules.isPositiveMultipleOf5(v), 
		message: "El total de la compra no puede ser negativo y debe ser múltiplo de 5." 
	},
	purchase_details: {
		validate: (arr) => Array.isArray(arr) && arr.length > 0 && arr.every(
			i => rules.isValidId(i.purchasable_id) && i.quantity > 0 && 
				rules.isPositiveMultipleOf5(i.unit_price) && 
				rules.isPositiveMultipleOf5(i.sub_total)
		),
		message: "Debe agregar al menos un producto válido (con cantidad mayor a cero y precios múltiplos de 5).",
	},
	payment_details: {
		validate: (v) => !v || (Array.isArray(v) && v.every(
			p => rules.inList(p.method, PURCHASE_DATA.paymentMethods || [], "label") && 
				rules.isPositiveMultipleOf5(p.amount ?? p.change_amount ?? 0))
		),
		message: "Los detalles de pago seleccionados no son válidos o incluyen valores no múltiplos de 5.",
	},
	notes: { 
		validate: v => !v || (typeof v === "string" && v.length <= 1000), 
		message: "Las notas no pueden exceder los 1000 caracteres." },
};

const purchaseDetailValidators = {
	id: v => v === null || rules.isValidId(v),
	quantity: v => rules.isNum(v) && parseInt(v) > 0,
	unit_price: v => rules.isPositiveMultipleOf5(v),
	sub_total: v => rules.isPositiveMultipleOf5(v),
	purchasable_id: v => rules.isValidId(v),
	purchasable_type: v => Object.values(PURCHASE_DATA.purchasableTypes || {}).includes(v),
};

const paymentDetailValidators = {
    method: v => rules.inList(v, PURCHASE_DATA.paymentMethods || [], 'label'),
    change_amount: v => rules.isPositiveMultipleOf5(v),
    reference: v => !v || (typeof v === 'string' && v.length >= 4 && v.length <= 12)
};

// ==================== Validation Functions ====================

const getActiveFieldValidators = () => {
    const validators = { ...baseFieldValidators };
    if (!$('#notes').val().trim()) delete validators.notes;
    if ($('#payment_status').val() !== PaymentStatus.PAID) delete validators.payment_details;
    return validators;
};

const validatePurchaseForm = (values, fieldValidators) => {
	const { purchase_details = [], payment_details = [], ...purchase } = values;
	const errors = [];

	// Exclude certain fields from real-time validation error clearing to preserve their state during submission
	const filteredValidators = Object.fromEntries(
		Object.entries(fieldValidators).filter(([key]) => !['total', 'purchase_details', 'payment_details'].includes(key))
	);

	clearAllFieldErrors(filteredValidators);

	Object.entries(fieldValidators).forEach(([fieldId, config]) => {
		const value = purchase[fieldId] !== undefined ? purchase[fieldId] : values[fieldId];
		if (!["purchase_details", "payment_details"].includes(fieldId)) {
			if (!config.validate(value)) {
				if (fieldId !== 'total') showFieldError(fieldId, config.message);
				errors.push([false, fieldId, config.message]);
			} else if (fieldId !== 'total') {
                clearFieldError(fieldId);
            }
		}
	});

    if (!fieldValidators.purchase_details.validate(purchase_details)) {
        errors.push([false, 'form-error-alert', fieldValidators.purchase_details.message]);
    }

	purchase_details.forEach((detail, index) => {
		Object.entries(purchaseDetailValidators).forEach(([key, validator]) => {
			if (!validator(detail[key])) {
				errors.push([false, null, `Item ${index + 1}: Compruebe que el producto, cantidad y precio sean válidos.`]);
			}
		});
	});

	if (purchase.payment_status === PaymentStatus.PAID && typeof payment_details !== "undefined") {
		payment_details.forEach((payment, index) => {
			Object.entries(paymentDetailValidators).forEach(([key, validator]) => {
				if (!validator(payment[key])) errors.push([false, null, `Pago ${index + 1}: Campo ${key} inválido.`]);
			});
		});
	}

	return errors.length > 0 ? errors[0] : [true, "", ""];
};

const purchaseDetailsHasChanges = () => {
	return false;
};

// ==================== Real-Time Validation Handler ====================

const bindRealTimeValidation = () => {
    ['invoice_number', 'supplier_id', 'payment_status', 'date', 'notes'].forEach(fieldId => {
        const $element = $(`#${fieldId}`);
        if (!$element.length) return;

        const eventType = $element.is('select, input[type="date"]') ? 'change' : 'input focusout';

        $element.on(eventType, function() {
            const config = baseFieldValidators[fieldId];
            const value = $(this).val();

            if (fieldId === 'notes' && !value.trim()) {
                clearFieldError(fieldId);
                return;
            }

            if (config) {
                config.validate(value) ? clearFieldError(fieldId) : showFieldError(fieldId, config.message);
            }
        });
    });

    const $table = $('#purchase-details-table');
    
    $table.on('input focusout', 'input[name="quantity"], input[name="unit-price"]', function() {
        const $input = $(this);
        const key = $input.attr('name') === 'unit-price' ? 'unit_price' : $input.attr('name'); 
        const validator = purchaseDetailValidators[key];

        if (validator) {
            if (!validator($input.val())) {
                $input.addClass('is-invalid');
                showFieldErrorInAlert($(this), `Compruebe que la ${key === "unit_price" ? "cantidad y el precio unitario" : "cantidad"} sean válidos para cada producto agregado.`);
            } else {
                $input.removeClass('is-invalid');
                clearFieldErrorInAlert();
            }
        }
    });

    $table.on('change', 'select[name="purchasable_id"]', function() {
        const $select = $(this);
        if (purchaseDetailValidators.purchasable_id) {
            if (!purchaseDetailValidators.purchasable_id($select.val())) {
                $select.addClass('is-invalid');
                showFieldErrorInAlert($(this), `Debe seleccionar un producto válido para cada detalle de compra.`);
            } else {
                $select.removeClass('is-invalid');
                clearFieldErrorInAlert();
            }
        }
    });
};

// ==================== Form Submission Handler ====================

const submitPurchaseFormHandler = async (url, token, method, values, shouldPrint) => {
	if (method === 'PUT') values.id = url.split('/').pop();

	if (Array.isArray(values.purchase_details)) {
		values.purchase_details = values.purchase_details.map(({ id, ...rest }) => id === null ? rest : { id, ...rest });
	}

	try {
		const response = await fetch(url, {
			method,
			headers: {
				"X-CSRF-TOKEN": token,
				"Accept": "application/json",
				"Content-Type": "application/json",
			},
			body: JSON.stringify(values),
		});

		const data = await response.json();
		if (! response.ok) {
			if (response.status === 422) {
				const { field, message } = getLaravelFirstError(data);
				if (field) showFieldError(field, message);
				showFieldErrorInAlert(null, data.message || "Error al enviar el formulario. Por favor, revise los campos e inténtelo de nuevo.");
			} else {
				throw new Error(data.message || "Error al enviar el formulario. Por favor, inténtelo de nuevo.");
			}
		}

		SwalToast.fire({ icon: SwalNotificationTypes.SUCCESS, title: data.message || "Compra guardada exitosamente" });
		if (shouldPrint && data.data?.id) await printReceipt(route('receipts.show', { model: 'purchases', id: data.data.id }));
		if (data.redirect) window.location.href = data.redirect;
	} catch (error) {
		console.error("Error submitting form:", error);
		SwalToast.fire({ icon: SwalNotificationTypes.ERROR, title: error.message || "Error de conexión con el servidor." });
	} finally {
		setLoadingState(FORM_ID, false);
	}
};

// async function submitPurchaseFormHandlerBackup(url, token, method, values, shouldPrint) {
// 	if (method === 'PUT') values.id = url.split('/').pop();

// 	if (Array.isArray(values.purchase_details)) {
// 		values.purchase_details = values.purchase_details.map(({ id, ...rest }) => id === null ? rest : { id, ...rest });
// 	}

// 	try {
// 		const response = await fetch(url, {
// 			method,
// 			headers: {
// 				'X-CSRF-TOKEN': token,
// 				'Accept': 'application/json',
// 				'Content-Type': 'application/json',
// 			},
// 			body: JSON.stringify(values)
// 		});

// 		if (response.ok) {
// 			const data = await response.json();
// 			if (shouldPrint && data.data?.id) {
// 				await printReceipt(route('receipts.show', { model: 'purchases', id: data.data.id }));
// 			}
// 			window.location.href = data.redirect || route('purchases.index');
// 		} else {
// 			const errorData = await response.json();
// 			const { field, message } = getLaravelFirstError(errorData);
// 			if (field) showFieldError(field, message);
// 			showFieldErrorInAlert(null, errorData.message || 'Error al enviar el formulario. Por favor, inténtelo de nuevo.');
// 		}
// 	} catch (error) {
// 		SwalToast.fire({ icon: SwalNotificationTypes.ERROR, title: "Error al enviar el formulario" });
// 	} finally {
// 		setLoadingState(FORM_ID, false);
// 	}
// }

const bindMainFormSubmission = () => {
	const $form = $(`#${FORM_ID}`);
	if (!$form.length) return;

	$form.off('submit').on('submit', async function(e) {
		e.preventDefault();
		setLoadingState(FORM_ID, true);

		// Validate form before showing payment modal to avoid unnecessary modals if there are basic validation errors
		const fieldValidators = getActiveFieldValidators();
		const values = getFormFields();
		const [isValid, fieldId, message] = validatePurchaseForm(values, fieldValidators);

		if (!isValid) {
			setLoadingState(FORM_ID, false);
			showFieldErrorInAlert(fieldId, message);
			return;
		}

		$("#form-error-alert").addClass("d-none");

		// Extract current amounts and status
        const currentTotal = parseInt($("#total_amount").val()) || 0;
        const currentStatus = $("#payment_status").val() || values.payment_status || PaymentStatus.PENDING;
        const amountPaid = parseFloat(PURCHASE_DATA.amountPaid) || 0; 
        let pendingBalance = currentTotal - amountPaid;

		// Case 1: Creating a new purchase and user selects "Paid" - show payment modal with full amount
		if (!IS_EDITING) {
            if (currentStatus === PaymentStatus.PAID) {
                const paymentInfo = await showPaymentDetailsFormModal(currentTotal);
                
                if (!paymentInfo || Object.keys(paymentInfo).length === 0) {
                    setLoadingState(FORM_ID, false);
                    return;
                }
                values.payment_details = paymentInfo.paymentDetails;
            }
            
            await submitPurchaseFormHandler(url, token, httpMethod, values);
            return;
        }

		// Case 2: Editing an existing purchase - determine if payment modal is needed based on changes
		const original = PURCHASE_DATA.originalPurchase || {};
		const oldTotal = parseInt(original.total) || 0;
		const oldStatus = original.payment_status || PaymentStatus.PENDING;

		const totalChanged = currentTotal !== oldTotal;
		const statusChanged = currentStatus !== oldStatus;
		const detailsChanged = purchaseDetailsHasChanges();

		const difference = currentTotal - oldTotal;
		const amountToReturn = oldTotal - currentTotal;

		// If there'n no changes that would affect payment, submit directly without showing modal
		if (!totalChanged && !statusChanged && !detailsChanged) {
			await submitPurchaseFormHandler(url, token, httpMethod, values);
			return;
		}

		// If payment status changed from pending to paid, or if total increased while already paid, show payment modal to capture additional payment details
		if (oldStatus === PaymentStatus.PENDING && currentStatus === PaymentStatus.PAID) {
            const confirm = await SwalConfirmation.fire({
                title: "Atención: Pago Requerido",
                html: "Para que la compra se pueda procesar correctamente, deberá ingresar los datos relacionados a los métodos de pago utilizados y al monto pagado por cada método de pago.<br><br>¿Desea continuar?",
                confirmButtonText: "Sí, ingresar pagos",
                cancelButtonText: "No, cancelar"
            }).then(r => r.isConfirmed);

            if (confirm) {
                const amountToCharge = pendingBalance > 0 ? pendingBalance : currentTotal;
                const paymentInfo = await showPaymentDetailsFormModal(amountToCharge);
                
                if (!paymentInfo || Object.keys(paymentInfo).length === 0) {
                    setLoadingState(FORM_ID, false);
                    return;
                }
                values.payment_details = paymentInfo.paymentDetails;
                await submitPurchaseFormHandler(url, token, httpMethod, values);
            } else {
                SwalToast.fire({ icon: "info", title: "Operación cancelada." });
                setLoadingState(FORM_ID, false);
            }
            return;
        }

		// Only the total value changed
		if (totalChanged && !statusChanged && !detailsChanged) {
            if (currentTotal > oldTotal) {
                const confirm = await SwalConfirmation.fire({
                    title: "Cambio de valor detectado",
                    html: "El valor de la compra cambió aunque no se han realizado cambios que justifiquen este cambio.<br><br>¿Desea continuar?",
                    confirmButtonText: "Sí, continuar",
                    cancelButtonText: "No, cancelar"
                }).then(r => r.isConfirmed);

                if (confirm) {
                    const paymentInfo = await showPaymentDetailsFormModal(difference);
                    if (!paymentInfo || Object.keys(paymentInfo).length === 0) {
                        setLoadingState(FORM_ID, false);
                        return;
                    }
                    values.payment_details = paymentInfo.paymentDetails;
                    await submitPurchaseFormHandler(url, token, httpMethod, values);
                } else {
                    SwalToast.fire({ icon: "info", title: "Operación cancelada." });
                    setLoadingState(FORM_ID, false);
                }
            } else {
                const confirm = await SwalConfirmation.fire({
                    title: "Devolución requerida",
                    html: `Usted tiene un pago previo que excede el nuevo total de la compra. Se deberá procesar una devolución por parte del proveedor por un monto de <strong>₡${amountToReturn}</strong>.<br><br>¿Desea continuar con el registro?`,
                    confirmButtonText: "Sí, continuar",
                    cancelButtonText: "No, cancelar"
                }).then(r => r.isConfirmed);

                if (confirm) {
                    await submitPurchaseFormHandler(url, token, httpMethod, values);
                } else {
                    SwalToast.fire({ icon: "info", title: "Operación cancelada." });
                    setLoadingState(FORM_ID, false);
                }
            }
            return;
        }

		// Purchase Details were changed
		if (detailsChanged) {
            if (currentTotal > oldTotal && currentStatus === PaymentStatus.PAID) {
                const confirm = await SwalConfirmation.fire({
                    title: "Cambios en la compra",
                    html: "Se han detectado cambios en los detalles de la compra que incrementan el total.<br><br>¿Desea continuar y procesar la diferencia del pago?",
                    confirmButtonText: "Sí, continuar",
                    cancelButtonText: "No, cancelar"
                }).then(r => r.isConfirmed);

                if (confirm) {
                    const paymentInfo = await showPaymentDetailsFormModal(difference);
                    if (!paymentInfo || Object.keys(paymentInfo).length === 0) {
                        setLoadingState(FORM_ID, false);
                        return;
                    }
                    values.payment_details = paymentInfo.paymentDetails;
                    await submitPurchaseFormHandler(url, token, httpMethod, values);
                } else {
                    SwalToast.fire({ icon: "info", title: "Operación cancelada." });
                    setLoadingState(FORM_ID, false);
                }
                return;
            }

            if (currentTotal < oldTotal && currentStatus === PaymentStatus.PAID) {
                const confirm = await SwalConfirmation.fire({
                    title: "Devolución requerida",
                    html: `Usted tiene un pago previo que excede el nuevo total de la compra. Se deberá procesar una devolución por parte del proveedor por un monto de <strong>₡${amountToReturn}</strong>.<br><br>¿Desea continuar con el registro?`,
                    confirmButtonText: "Sí, continuar",
                    cancelButtonText: "No, cancelar"
                }).then(r => r.isConfirmed);

                if (confirm) {
                    await submitPurchaseFormHandler(url, token, httpMethod, values);
                } else {
                    SwalToast.fire({ icon: "info", title: "Operación cancelada." });
                    setLoadingState(FORM_ID, false);
                }
                return;
            }
        }

		// Fallback for any other valid scenario
        await submitPurchaseFormHandler(url, token, httpMethod, values);
	});
};

// $(document).on('submit', `#${FORM_ID}`, async function(e) {
//     e.preventDefault();
//     setLoadingState(FORM_ID, true);

//     const fieldValidators = getActiveFieldValidators();
//     const values = getFormFields();

// 	const filteredValidators = Object.fromEntries(
// 		Object.entries(fieldValidators).filter(([key]) => !['total', 'purchase_details', 'payment_details'].includes(key))
// 	);

// 	clearAllFieldErrors(filteredValidators);
//     const [isValid, fieldId, message] = validatePurchaseForm(values, fieldValidators);

// 	if (!isValid) {
// 		setLoadingState(FORM_ID, false);
// 		showFieldErrorInAlert(fieldId, message);
//         return;
// 	}

//     $("#form-error-alert").addClass("d-none");
    
//     let paymentInfo = null;
//     const status = values.payment_status;
//     const totalAmount = values.total;
    
//     let shouldShowPaymentModal = false;
//     let amountForModal = totalAmount;
//     let isRefund = false;

//     if (!IS_EDITING) {
//         if (status === PaymentStatus.PAID) {
//             shouldShowPaymentModal = true;
//         }
//     } else if (initialPurchaseState) {
//         const wasPending = initialPurchaseState.payment_status !== PaymentStatus.PAID;
//         const isNowPaid = status === PaymentStatus.PAID;
//         const pendingBalance = totalAmount - initialPurchaseState.total;

//         if (wasPending && isNowPaid) {
//             shouldShowPaymentModal = true;
//             amountForModal = totalAmount; 
//         } else if (isNowPaid && pendingBalance !== 0) {
//             shouldShowPaymentModal = true;
//             amountForModal = Math.abs(pendingBalance); // Always extract the absolute value for the modal
//             isRefund = pendingBalance < 0; // If negative, it's a refund; if positive, it's an additional payment
//         }
//     }

//     // Payment Modal Trigger
//     if (shouldShowPaymentModal) {
//         paymentInfo = await showPaymentDetailsFormModal(amountForModal, isRefund);

//         if (!paymentInfo || Object.keys(paymentInfo).length === 0) {
//             setLoadingState(FORM_ID, false);
//             return;
//         }
//         values.payment_details = paymentInfo.paymentDetails;
//     }
    
//     const url = this.action;
//     const token = $(this).find('input[name="_token"]').val();
//     const httpMethod = $(this).find('input[name="_method"]').val()?.toUpperCase() || 'POST';

//     await submitPurchaseFormHandler(url, token, httpMethod, values, paymentInfo?.shouldPrint || false);
// });

// ==================== Initialization ====================
$(() => {
	initializeCashRegister(); 
	bindRealTimeValidation(); 
	bindPurchaseFormEvents(); 
	bindMainFormSubmission();
	bindOffcanvasEvents("create-offcanvas"); 

	if (IS_EDITING) {
		initialPurchaseState = getFormFields();
	}
});