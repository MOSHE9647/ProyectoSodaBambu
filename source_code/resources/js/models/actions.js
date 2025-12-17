import { SwalModal, SwalToast } from "../utils/sweetalert.js";
import { toggleLoadingState } from "../utils/utils.js";
import { capitalizeSentence } from "../utils/utils.js";
import { fetchWithErrorHandling } from "../utils/error-handling.js";

/**
 * Displays model information in a modal.
 * @param url - The URL to fetch a previously rendered Laravel Blade file.
 * @param anchor - The anchor element that triggered the action.
 * @param modelName - The name of the model to be displayed.
 * @returns {Promise<void>}
 */
export async function showModelInfo(url, anchor, modelName) {
    // Show loading state
    toggleLoadingState(anchor, 'info', true);

    try {
        // Fetch model data
        const response = await fetchWithErrorHandling(url);
        const html = await response.text();

        // Display model information in a modal
        if (html) {
            const capitalizedModelName = capitalizeSentence(modelName) || 'Modelo';

            SwalModal.fire({
                title: `Información del ${capitalizedModelName}`,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cerrar',
                html: `${html}`,
            });
        } else {
            SwalToast.fire({
                icon: 'error',
                text: `No se pudo cargar la información del ${modelName}.`,
            });
        }
    } catch (error) {
        console.error(`Error loading ${modelName} data:`, error);
        SwalToast.fire({
            icon: 'error',
            text: `Ocurrió un error al cargar la información del ${modelName}.`,
        });
    } finally {
        // Hide loading state
        toggleLoadingState(anchor, 'info', false);
    }
}

export function deleteModel(e, modelName) {
    // Prevent default form submission
    e.preventDefault();

    // Get the form element and show loading state
    const form = e.currentTarget;
    toggleLoadingState(form, 'delete-form', true);

    // Show confirmation dialog
    SwalModal.fire({
        title: `¿Estás seguro de eliminar este ${modelName}?`,
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
            return false;
        }
    });
}

// Expose functions globally
window.showModelInfo = showModelInfo;
window.deleteModel = deleteModel;