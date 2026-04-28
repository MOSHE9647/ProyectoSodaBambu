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
 * Formats a phone number to Costa Rican format: +506 XXXX XXXX
 * Only allows digits and automatically formats as the user types.
 * @param {string} input - The raw phone input
 * @returns {string} - The formatted phone number
 */
export function formatPhoneNumber(input) {
	// Remove all non-digit and non-plus characters
	let cleaned = input.replace(/[^\d+]/g, '');
	
	// If it starts with +, remove it to process only digits
	if (cleaned.startsWith('+')) {
		cleaned = cleaned.substring(1);
	}
	
	// Remove the country code (506) if it's at the beginning
	if (cleaned.startsWith('506')) {
		cleaned = cleaned.substring(3);
	}
	
	// Limit to 8 digits (Costa Rican phone numbers)
	const limited = cleaned.slice(0, 8);
	
	// Apply format: +506 XXXX XXXX
	if (limited.length === 0) {
		return '+506 ';
	} else if (limited.length <= 4) {
		return `+506 ${limited}`;
	} else {
		return `+506 ${limited.slice(0, 4)} ${limited.slice(4)}`;
	}
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
 * Validates if the provided value is a valid time in HH:MM format.
 * @param {string} value
 * @returns {boolean}
 */
export function validateTime(value) {
	return /^\d{2}:\d{2}$/.test(value);
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
 * Validates if the provided value is an integer multiple of 5.
 * @param {string|number} val - The value to validate.
 * @returns {boolean} - Returns true if the value is an integer and a multiple of 5, false otherwise.
 */
export const validateMultipleOf5 = (val) => {
	return Number.isInteger(Number(val)) && Number(val) % 5 === 0;
};

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