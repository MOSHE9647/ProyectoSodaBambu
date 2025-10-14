/**
 * Retrieves the field and its associated error element based on the field ID.
 * @param fieldId
 * @returns {{field: *|jQuery|HTMLElement, errorElement: *|jQuery|[]}}
 */
export function getFieldElements(fieldId) {
	const field = $(`#${fieldId}`);
	const errorElement = $(`#${fieldId}-error`).children('strong');
	return {field, errorElement};
}

/**
 * Displays an error message for a specific field.
 * @param fieldId
 * @param message
 */
export function showFieldError(fieldId, message) {
	const {field, errorElement} = getFieldElements(fieldId);

	if (field.length && errorElement.length) {
		field.addClass('is-invalid');
		errorElement.text(message);
	} else {
		console.error(`Field or error element not found for ID: ${fieldId}`);
	}
}

/**
 * Clears the error message for a specific field.
 * @param fieldId
 */
export function clearFieldError(fieldId) {
	const {field, errorElement} = getFieldElements(fieldId);

	if (field.length && errorElement.length) {
		field.removeClass('is-invalid');
		errorElement.text('');
	} else {
		console.error(`Field or error element not found for ID: ${fieldId}`);
	}
}
