import { SwalNotificationTypes, SwalToast } from "./sweetalert.js";

export function handleApiError(error, customMessage = null) {
	console.error('API Error:', error);

	SwalToast.fire({
		title: 'Error',
		icon: SwalNotificationTypes.ERROR,
		text: customMessage || 'Ocurrió un error inesperado'
	});
}

export async function fetchWithErrorHandling(url, options = {}, customErrorMessage = null) {
	try {
		const response = await fetch(url, options);
		if (!response.ok) {
			throw new Error(customErrorMessage || `${response.statusText}`, { status: response.status });
		}
		return response;
	} catch (error) {
		handleApiError(error.message);
		throw error; // Re-throw to allow further handling if needed
	}
}
