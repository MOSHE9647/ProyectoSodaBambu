/**
 * Sets the loading state for the form's submit button.
 * @param formId
 * @param isLoading
 */
export function setLoadingState(formId, isLoading) {
	// Get necessary elements
	const spinner = $(`#${formId}-spinner`);
	const submitButton = $(`#${formId}-button`);
	const submitButtonText = $(`#${formId}-button-text`);

	// Check if elements exist
	if (!submitButton.length || !spinner.length || !submitButtonText.length) {
		console.error(`Submit button or spinner not found for form ID: ${formId}`);
		return;
	}

	// Toggle loading state
	if (isLoading) {
		// Disable the button and hide the text
		submitButton.attr('disabled', 'disabled');
		submitButtonText.removeClass('d-flex').addClass('d-none');

		// Show the spinner
		spinner.removeClass('d-none');
		spinner.find('span').eq(1).removeClass('visually-hidden');
	} else {
		// Enable the button and show the text
		submitButton.removeAttr('disabled');
		submitButtonText.removeClass('d-none').addClass('d-flex');

		// Hide the spinner
		spinner.addClass('d-none');
		spinner.find('span').eq(1).addClass('visually-hidden');
	}
}

/**
 * Registers a submit event listener for a form to set loading state on submission.
 * @param formId
 */
export const attachLoadingSubmit = (formId) => {
	$(document).on('submit', `#${formId}-form`, (e) => {
		e.preventDefault();
		setLoadingState(formId, true);
		e.currentTarget.submit();
	});
};

export function toggleLoadingState(element, elementClass, isLoading) {
	const btn = typeof element === 'string'
		? document.querySelector(element)
		: element;

	if (!btn) return;

	const spinner = btn.querySelector(`.${elementClass}-spinner`);
	const text = btn.querySelector(`.${elementClass}-button-text`);

	btn.disabled = isLoading;
	spinner?.classList.toggle('d-none', !isLoading);
	spinner?.querySelector('span:nth-child(2)')?.classList.toggle('visually-hidden', !isLoading);
	text?.classList.toggle('d-none', isLoading);
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