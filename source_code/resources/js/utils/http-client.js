/**
 * Standardized HTTP client for POST/PUT requests with FormData to Laravel.
 * Automatically handles array serialization, CSRF tokens, and 422 errors.
 */
export const submitFormToApi = async (url, formElement) => {
	const formData = new FormData(formElement);
	const data = {};

	formData.forEach((value, key) => {
		if (Object.prototype.hasOwnProperty.call(data, key)) {
			if (!Array.isArray(data[key])) data[key] = [data[key]];
			data[key].push(value);
		} else {
			data[key] = value;
		}
	});

	const response = await fetch(url, {
		method: formElement.method || "POST",
		headers: {
			"Content-Type": "application/json",
			"X-Requested-With": "XMLHttpRequest",
			"X-CSRF-TOKEN":
				typeof csrfToken !== "undefined"
					? csrfToken
					: document.querySelector('meta[name="csrf-token"]')
							?.content || "",
		},
		body: JSON.stringify(data),
	});

	// Intercept and format Laravel validation errors
	if (response.status === 422) {
		const errorData = await response.json();
		const firstError = Object.values(errorData.errors)[0][0];
		throw new Error(firstError);
	}

	if (!response.ok) {
		throw new Error(
			`Ocurrió un error inesperado en el servidor (${response.status})`,
		);
	}

	return await response.json();
};