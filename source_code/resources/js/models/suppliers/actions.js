import { SwalModal } from "../../utils/sweetalert.js";
import { toggleLoadingState } from "../../utils/utils.js";
import { fetchWithErrorHandling } from "../../utils/error-handling.js";

/**
 * Displays supplier information in a modal.
 * @param url - The URL to fetch supplier data from.
 * @param anchor - The anchor element that triggered the action.
 * @returns {Promise<void>}
 */
export async function showSupplier(url, anchor) {
	// Show loading state
	toggleLoadingState(anchor, 'info', true);

	try {
		// Fetch supplier data
		const response = await fetchWithErrorHandling(url);
		const html = await response.text();

		// Display supplier information in a modal
		if (html) {
			SwalModal.fire({
				title: 'Información del Proveedor',
				showConfirmButton: false,
				showCancelButton: true,
				cancelButtonText: 'Cerrar',
				html: `${html}`,
			});
		} else {
			alert('No se pudo cargar la información del proveedor.');
		}
	} catch (error) {
		console.error('Error loading supplier data:', error);
		alert('Ocurrió un error al cargar la información del proveedor.');
	} finally {
		// Hide loading state
		toggleLoadingState(anchor, 'info', false);
	}
}

/**
 * Handles supplier deletion with confirmation.
 * @param e - The event object from the delete form action.
 */
export function deleteSupplier(e) {
	// Prevent default form submission
	e.preventDefault();

	// Get the form element and show loading state
	const form = e.currentTarget;
	toggleLoadingState(form, 'delete-form', true);

	// Show confirmation dialog
	SwalModal.fire({
		title: '¿Estás seguro de eliminar este proveedor?',
		text: "Esta acción no se puede deshacer.",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Sí, eliminar',
		cancelButtonText: 'Cancelar',
	}).then((result) => {
		// If confirmed, submit the form
		if (result.isConfirmed) {
			form.submit();
		} else {
			// Hide loading state if cancelled
			toggleLoadingState(form, 'delete-form', false);
		}
	});
}
