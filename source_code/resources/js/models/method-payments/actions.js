import { SwalModal } from "../../utils/sweetalert.js";
import { toggleLoadingState } from "../../utils/utils.js";
import { fetchWithErrorHandling } from "../../utils/error-handling.js";

export async function showMethodPayment(url, anchor) {
    toggleLoadingState(anchor, 'info', true);

    try {
        const response = await fetchWithErrorHandling(url);
        const html = await response.text();

        if (html) {
            SwalModal.fire({
                title: 'Información del Método de Pago',
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cerrar',
                html: `${html}`,
            });
        } else {
            alert('No se pudo cargar la información del método de pago.');
        }
    } catch (error) {
        console.error('Error loading payment method data:', error);
        alert('Ocurrió un error al cargar la información del método de pago.');
    } finally {
        toggleLoadingState(anchor, 'info', false);
    }
}

export function deleteMethodPayment(e) {
    e.preventDefault();
    const form = e.currentTarget;
    toggleLoadingState(form, 'delete-form', true);

    SwalModal.fire({
        title: '¿Estás seguro de eliminar este método de pago?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        } else {
            toggleLoadingState(form, 'delete-form', false);
        }
    });
}