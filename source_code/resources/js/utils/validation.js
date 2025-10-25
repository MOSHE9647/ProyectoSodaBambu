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

/**
 * Validates if the password and confirmation match.
 * @param password
 * @param confirmPassword
 * @returns {boolean}
 */
export function validatePasswordConfirmation(password, confirmPassword) {
	return password === confirmPassword;
}

/**
 * Validates if the provided name is valid.
 * @param name
 * @returns {boolean}
 */
export function validateName(name) {
	return name.length <= 255;
}

/**
 * Validates if the provided role is selected.
 * @param role
 * @returns {boolean}
 */
export function validateRole(role) {
	return role !== '-1';
}

/**
 * Validates if the provided hourly wage is a valid number >= 0.
 * @param wage
 * @returns {boolean}
 */
export function validateHourlyWage(wage) {
	const num = parseFloat(wage);
	return !isNaN(num) && num >= 0;
}

/**
 * Validates if the provided phone number is in Costa Rican format (optional).
 * @param phone
 * @returns {boolean}
 */
export function validatePhone(phone) {
	const phoneRegex = /^\+506 \d{4} \d{4}$/;
	return phoneRegex.test(phone);
}

/**
 * Validates if the provided payment frequency is selected.
 * @param freq
 * @returns {boolean}
 */
export function validatePaymentFrequency(freq) {
	return freq !== '-1';
}

/**
 * Validates if the provided employee status is selected.
 * @param status
 * @returns {boolean}
 */
export function validateEmployeeStatus(status) {
	return status !== '-1';
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

/**
 * Clears all field errors in the form.
 */
export function clearAllFieldErrors(fieldValidators) {
	Object.keys(fieldValidators).forEach(clearFieldError);
}

// Payment Method Validations
export function validateAmount(value) {
    const amount = parseFloat(value);
    return !isNaN(amount) && amount >= 0;
}

export function validatePaymentType(value) {
    return ['sinpe', 'card', 'cash'].includes(value);
}

export function validateVoucher(value) {
    return value.length >= 1 && value.length <= 255;
}

export function validateReference(value) {
    return value.length >= 1 && value.length <= 255;
}

export function validateChangeAmount(value) {
    const amount = parseFloat(value);
    return !isNaN(amount) && amount >= 0;
}