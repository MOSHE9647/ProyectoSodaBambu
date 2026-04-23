import { SwalModal, SwalToast, SwalNotificationTypes } from "../../utils/sweetalert.js";


/**
 * Reusable SweetAlert2 custom class mappings used to keep modal styling
 * consistent across the sales page dialogs.
 */
const SweetAlertModalOptions = {
	popup: 'swal-popup w-auto h-auto',
	title: 'justify-content-center border-bottom pb-3 mb-3',
	closeButton: 'swal-close-btn fs-3',
	htmlContainer: 'w-auto h-auto p-1 overflow-x-hidden',
	confirmButton: 'btn btn-primary mx-1',
	cancelButton: 'btn btn-danger mx-1',
	icon: 'mb-4',
}

/**
 * Persists the opening cash register amount on the server.
 *
 * Flow:
 * - Sends a POST request with the opening balance.
 * - Shows a success toast when the operation completes successfully.
 * - Handles validation and server errors with retry-friendly modal dialogs.
 *
 * @param {number|string} amount - Opening balance entered by the user.
 * @returns {Promise<void>} Resolves when the request/notification flow finishes.
 */
const saveInitialCashRegisterAmount = async (amount) => {
	try {
		// Build endpoint URL and send opening balance to backend.
		const url = route("cash-registers.store");
		const response = await fetch(url, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				"X-Requested-With": "XMLHttpRequest",
				"X-CSRF-TOKEN": typeof csrfToken !== 'undefined' ? csrfToken : '',
			},
			body: JSON.stringify({ opening_balance: amount }),
		});

		// Parse JSON response and validate HTTP status.
		const responseData = await response.json();
		console.log("Response from saving initial cash register amount:", responseData);

		if (!response.ok) throw new Error(
			responseData.message || "Error al guardar el monto.",
			{ cause: responseData.errors ? 'Error de validación' : 'Error de servidor' }
		);
	
		SwalToast.fire({
			icon: SwalNotificationTypes.SUCCESS,
			title: responseData.message || "Monto inicial guardado con éxito.",
		});
	} catch (error) {
		// Show contextual feedback depending on error type.
		console.error("Error saving initial cash register amount:", error);
		
		if (error.cause === 'Error de validación') {
			// Validation errors: let user retry by reopening the modal.
			SwalModal.fire({
				icon: SwalNotificationTypes.ERROR,
				text: `${error.message} \nPor favor, ingresa un monto válido e intenta nuevamente.`,
				customClass: SweetAlertModalOptions,
				title: "Error al guardar el monto inicial en caja",
				confirmButtonText: "Reintentar",
				allowEscapeKey: false,
				allowOutsideClick: false,
			}).then((result) => {
				if (result.isConfirmed) {
					showOpeningCashModal();
				}
			});
		} else {
			// Unexpected/server errors: keep the same retry flow.
			SwalModal.fire({
				icon: SwalNotificationTypes.ERROR,
				title: "Ocurrió un error al guardar el monto inicial en caja.",
				customClass: SweetAlertModalOptions,
				allowEscapeKey: false,
				allowOutsideClick: false,
				text:
					error.message ||
					"Ocurrió un error inesperado. Por favor, intenta nuevamente.",
				confirmButtonText: "Reintentar",
			}).then((result) => {
				if (result.isConfirmed) {
					showOpeningCashModal();
				}
			});
		}
	}
};

/**
 * Displays the opening cash modal used to capture the initial cash register amount.
 *
 * Behavior:
 * - Forces the user to enter a valid non-negative numeric value.
 * - If the user cancels/closes the modal, a warning is shown and the user is redirected
 *   to the dashboard because sales cannot continue with a closed cash register.
 * - If the user confirms, the entered amount is sent to the server.
 *
 * @returns {Promise<void>} Resolves when the modal flow completes.
 */
const showOpeningCashModal = async () => {
	// Request the initial cash amount required to open the register.
	SwalModal.fire({
		title: "Abriendo caja...",
		text: "Por favor, ingresa el monto inicial de dinero en caja para comenzar a registrar ventas.",
		allowEscapeKey: false,
		allowOutsideClick: false,
		input: "number",
		inputAttributes: { min: 0, step: 1 },
		showCancelButton: true,
		confirmButtonText: "Aceptar",
		cancelButtonText: "Cancelar",
		inputValidator: (value) => {
			if (!value) return "Por favor, ingresa un monto válido.";
			if (isNaN(value)) return "El monto debe ser un número.";
			if (Number(value) < 0) return "El monto no puede ser negativo.";
		}
	}).then((result) => {
		// If the modal is dismissed, block sales flow and return to dashboard.
		if (result.isDismissed) {
			SwalModal.fire({
				icon: SwalNotificationTypes.WARNING,
				title: "Caja cerrada",
				text: "No se ha ingresado un monto inicial. La caja permanecerá cerrada y no podrás registrar ventas.",
				customClass: SweetAlertModalOptions,
				confirmButtonText: "Entendido",
				allowEscapeKey: false,
				allowOutsideClick: false,
			}).then(() => {
				window.location.href = route("dashboard"); // Redirect to dashboard if the modal is closed without entering a valid amount
			});

			return;
		} else if (result.isConfirmed) {
			// Persist the opening amount entered by the user.
			const initialAmount = result.value;
			saveInitialCashRegisterAmount(initialAmount);

			const $menuItem = $('#cash-closure-menu-item');
    		$menuItem.removeClass('d-none').addClass('d-block').attr('data-is-active', 'true');
    	}
	});
};

export function initializeCashRegister() {

    const showOpeningCashModalElement = $("#show-opening-cash-modal");
	if (showOpeningCashModalElement.length && showOpeningCashModalElement.data("show-modal")) {
		showOpeningCashModal();
	}
    
}