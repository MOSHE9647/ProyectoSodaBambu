import { SwalModal } from "../../utils/sweetalert.js";
import { toggleLoadingState } from "../../utils/utils.js";
import { fetchWithErrorHandling } from "../../utils/error-handling.js";

/**
 * Displays category information in a modal.
 * @param url - The URL to fetch category data from.
 * @param anchor - The anchor element that triggered the action.
 * @returns {Promise<void>}
 */
export async function showCategory(url, anchor) {
    // Show loading state
    toggleLoadingState(anchor, 'info', true);

    try {
        // Fetch category data
        const response = await fetchWithErrorHandling(url);
        const html = await response.text();

        // Display category information in a modal
        if (html) {
            SwalModal.fire({
                title: 'Información de la Categoría',
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cerrar',
                html: `${html}`,
            });
        } else {
            alert('No se pudo cargar la información de la categoría.');
        }
    } catch (error) {
        console.error('Error loading category data:', error);
        alert('Ocurrió un error al cargar la información de la categoría.');
    } finally {
        // Hide loading state
        toggleLoadingState(anchor, 'info', false);
    }
}

/**
 * Handles category deletion with confirmation.
 * @param e - The event object from the delete form action.
 */
export function deleteCategory(e) {
    // Prevent default form submission
    e.preventDefault();

    // Get the form element and show loading state
    const form = e.currentTarget;
    toggleLoadingState(form, 'delete-form', true);

    // Show confirmation dialog
    SwalModal.fire({
        title: '¿Estás seguro de eliminar esta categoría?',
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