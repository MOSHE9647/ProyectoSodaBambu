/**
 * Validates if the provided email is in a correct format.
 * @param email
 * @returns {boolean}
 */
export function validateEmail(email) {
	const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return emailRegex.test(email);
}

/**
 * Validates if the provided password meets the criteria.
 * @param password
 * @returns {boolean}
 */
export function validatePassword(password) {
	// Password must be at least 8 alphanumerical characters long
	const passwordRegex = /^[A-Za-z0-9]{8,}$/;
	return passwordRegex.test(password);
}

export function validatePasswordConfirmation(password, confirmPassword) {
	return password === confirmPassword;
}

/**
 * Validation configuration for different fields.
 * @param {Object} fieldValidators
 * @param {Object} values
 * @param {Function} showFieldError
 * @param {Function} clearFieldError
 * @returns {boolean}
 */
export function validateAndDisplayField(fieldValidators, values, showFieldError, clearFieldError) {
	let isValid = true;

	Object.keys(fieldValidators).forEach((fieldId) => {
		const {validator, emptyMsg, invalidMsg} = fieldValidators[fieldId];
		const value = (values[fieldId] || '').trim();

		if (!value) {
			showFieldError(fieldId, emptyMsg);
			isValid = false;
		} else if (!validator(value)) {
			showFieldError(fieldId, invalidMsg);
			isValid = false;
		} else {
			clearFieldError(fieldId);
		}
	});

	return isValid;
}
