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
