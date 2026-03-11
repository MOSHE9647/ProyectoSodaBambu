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

/**
 * Escape HTML special characters in a string to prevent XSS.
 * @param {*} text 
 * @returns {string}
 */
export function escapeHtml(text) {
	if (typeof text !== 'string') {
		return String(text || '');
	}
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#39;'
	};
	return text.replace(/[&<>"']/g, char => map[char]);
}

export function formatDate(dateString) {
	const date = new Date(dateString);

	if (isNaN(date.getTime())) {
		console.error('Invalid date:', dateString);
		return 'Fecha inválida';
	}

	const day = String(date.getDate()).padStart(2, '0');
	const month = date.toLocaleDateString('es-ES', { month: 'long' });
	const year = date.getFullYear();
	
	return `${day} de ${month} del ${year}`;
}

export function capitalizeSentence(sentence) {
	if (typeof sentence !== 'string' || sentence.length === 0) {
		return '';
	}

	// Capitalize the first letter of each word except for small words, unless it's the first word
	const smallWords = ['de', 'y', 'la', 'las', 'lo', 'los', 'en', 'el', 'a', 'o', 'u'];
	const capitalized = sentence
		.split(' ')
		.map((word, index) => 
			index === 0 || !smallWords.includes(word.toLowerCase())
				? word.charAt(0).toUpperCase() + word.slice(1)
				: word.toLowerCase()
		)
		.join(' ');

	return capitalized;
}

export function togglePasswordVisibility(inputId, toggleButtonId) {
	const input = document.getElementById(inputId);
	const toggleButton = document.getElementById(toggleButtonId);

	if (!input || !toggleButton) {
		console.error('Input or toggle button not found:', inputId, toggleButtonId);
		return;
	}

	const isPassword = input.type === 'password';
	input.type = isPassword ? 'text' : 'password';

	const icon = toggleButton.querySelector('i');
	if (icon) {
		icon.classList.toggle('bi-eye-slash', isPassword);
		icon.classList.toggle('bi-eye', !isPassword);
	}

	toggleButton.setAttribute('aria-pressed', String(!isPassword));
}