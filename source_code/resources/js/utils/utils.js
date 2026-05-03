/**
 * Sets the loading state for the form's submit button.
 * @param {string} formId - Base form ID (without suffixes like -form or -button).
 * @param {boolean} isLoading - Whether to show the loading state.
 * @returns {void}
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
 * @param {string} formId - Base form ID to observe.
 * @returns {void}
 */
export const attachLoadingSubmit = (formId) => {
	$(document).on('submit', `#${formId}-form`, (e) => {
		e.preventDefault();
		setLoadingState(formId, true);
		e.currentTarget.submit();
	});
};

/**
 * Enables or disables the loading state on a generic button.
 * @param {string|HTMLElement|null} element - CSS selector or button reference.
 * @param {string} elementClass - Class prefix used to locate spinner and text elements.
 * @param {boolean} isLoading - Whether to show the loading state.
 * @returns {void}
 */
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
 * @param {*} text - Text to sanitize.
 * @returns {string} Text with escaped HTML characters.
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

/**
 * Formats a date for display in Costa Rican Spanish locale.
 * @param {string} dateString - Date in a format parseable by Date.
 * @returns {string} Formatted date or "Fecha inválida".
 */
export function formatDate(dateString) {
	// Attempt to parse the date string
	const date = new Date(dateString);

	// Check if the date is valid
	if (isNaN(date.getTime())) return "Fecha inválida";

	// Determine timezone based on the presence of 'Z' or offset in the date string
	let timezone = "UTC";
	if (dateString.endsWith("Z")) {
		timezone = "America/Costa_Rica";
	} else {
		// Look for timezone offset in the format ±HH:MM
		const tzMatch = dateString.match(/([+-]\d{2}:\d{2}|Z)$/);
		if (tzMatch) {
			// If it's 'Z', use Costa Rica timezone; otherwise, use the offset as is
			timezone = tzMatch[1] === "Z" ? "UTC" : tzMatch[1];
		}
	}

	// Format the date using Intl.DateTimeFormat with the determined timezone
	const formatter = new Intl.DateTimeFormat("es-CR", {
		timeZone: timezone,
		day: "2-digit",
		month: "long",
		year: "numeric",
	});

	const formattedDate = formatter.format(date);
	return formattedDate;
}

/**
 * Converts a date/time value to 12-hour format (AM/PM).
 * @param {string} timeString - Time in a format parseable by Date.
 * @returns {string} Formatted time or "Hora inválida".
 */
export function formatTime(timeString) {
	const date = new Date(timeString);

	if (isNaN(date.getTime())) {
		console.error('Invalid time:', timeString);
		return 'Hora inválida';
	}

	const hours = date.getUTCHours();
	const minutes = String(date.getUTCMinutes()).padStart(2, '0');
	const hour12 = hours % 12 || 12;
	const period = hours >= 12 ? 'PM' : 'AM';
	
	return `${String(hour12).padStart(2, '0')}:${minutes} ${period}`;
}

/**
 * Capitalizes a sentence in title style while keeping selected short words lowercase.
 * @param {string} sentence - Text to capitalize.
 * @returns {string} Transformed sentence.
 */
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

/**
 * Toggles visibility of a password field and updates its associated icon.
 * @param {string} inputId - Password input ID.
 * @param {string} toggleButtonId - Toggle button ID.
 * @returns {void}
 */
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

/**
 * Utilities for simple DOM element manipulation.
 */
export const DOMHelper = {
	/**
	 * Shows or hides an element using the d-none class.
	 * @param {HTMLElement|null} element - Element to show/hide.
	 * @param {boolean} shouldShow - Whether the element should be visible.
	 * @returns {void}
	 */
    toggleVisibility: (element, shouldShow) => {
        if (element) element.classList.toggle("d-none", !shouldShow);
    },
	/**
	 * Sets an input as read-only and disabled based on the provided state.
	 * @param {HTMLInputElement|HTMLTextAreaElement|HTMLElement|null} input - Target field.
	 * @param {boolean} shouldLock - Whether the field should be locked.
	 * @returns {void}
	 */
    setReadonly: (input, shouldLock) => {
        if (input) {
            input.readOnly = shouldLock;
            input.disabled = shouldLock;
        }
    },
	/**
	 * Creates or updates a hidden input to override a form value.
	 * @param {HTMLFormElement|null} form - Target form.
	 * @param {string} fieldName - Hidden field name.
	 * @param {string|null|undefined} value - Value to assign.
	 * @returns {void}
	 */
    setHiddenOverride: (form, fieldName, value) => {
        if (!form) return;
        let hiddenInput = form.querySelector(`input[type="hidden"][name="${fieldName}"]`);
        if (!hiddenInput) {
            hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = fieldName;
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = value ?? "";
    },
	/**
	 * Removes a hidden override input, except when its id is work_date.
	 * @param {HTMLFormElement|null} form - Target form.
	 * @param {string} fieldName - Hidden field name.
	 * @returns {void}
	 */
    removeHiddenOverride: (form, fieldName) => {
        if (!form) return;
        const hiddenInput = form.querySelector(`input[type="hidden"][name="${fieldName}"]`);
        if (hiddenInput && hiddenInput.id !== "work_date") hiddenInput.remove();
    }
};

/**
 * Gets initials from a full name.
 * @param {string} [name=""] - Full name.
 * @returns {string} Uppercase initials or "??" when empty.
 */
export function getInitials(name = "") {
    const nameParts = String(name).trim().split(/\s+/).filter(Boolean);
    if (nameParts.length === 0) return "??";
    return nameParts.slice(0, 2).map(part => part.charAt(0).toUpperCase()).join("");
}

/**
 * Converts an HH:MM time string to minutes since midnight.
 * @param {string} value - Time in HH:MM format.
 * @returns {number|null} Total minutes or null if format is invalid.
 */
export function parseTimeToMinutes(value) {
    const match = String(value || "").match(/(\d{2}):(\d{2})/);
    return match ? (parseInt(match[1], 10) * 60) + parseInt(match[2], 10) : null;
}

/**
 * Formats an HH:MM time string to 12-hour format.
 * @param {string} timeInput - Time in HH:MM format.
 * @returns {string} Time in 12-hour format or "Hora inválida".
 */
export function format12h(timeInput) {
    const mins = parseTimeToMinutes(timeInput);
    if (mins === null) return "Hora inválida";
    const h = Math.floor(mins / 60), m = mins % 60;
    return `${String(h % 12 || 12).padStart(2, "0")}:${String(m).padStart(2, "0")} ${h >= 12 ? "PM" : "AM"}`;
}

/**
 * Calculates worked hours between two HH:MM times.
 * @param {string} startTime - Start time in HH:MM format.
 * @param {string} endTime - End time in HH:MM format.
 * @returns {number} Whole worked hours; 0 when input values are invalid.
 */
export function calcWorkedHours(startTime, endTime) {
    const s = parseTimeToMinutes(startTime);
    const e = parseTimeToMinutes(endTime);
    return (s === null || e === null || e <= s) ? 0 : Math.floor((e - s) / 60);
}

/**
 * Enables Bootstrap tooltips for elements within a container.
 * @param {HTMLElement} container - The container element.
 */
export const enableBootstrapTooltips = (container) => {
	const tooltipTriggerList = container.querySelectorAll(
		'[data-bs-toggle="tooltip"]',
	);
	tooltipTriggerList.forEach((tooltipTriggerEl) => {
		new bootstrap.Tooltip(tooltipTriggerEl);
	});
};

/**
 * Generates a random valid EAN-13 barcode number.
 *
 * @param {number} length - The total length of the EAN code (default is 13).
 * @param {HTMLElement} triggerElement - The element that triggered the barcode generation, used for loading state.
 * @returns {string} The generated EAN-13 code as a string.
 *
 * The function generates a random EAN-13 code by:
 * 1. Generating (length - 1) random digits.
 * 2. Calculating the checksum digit according to the EAN-13 standard.
 * 3. Appending the checksum digit to the end of the code.
 * 4. Toggling the loading state on the trigger element during the process.
 */
export const generateEan13 = (length = 13, triggerElement) => {
    const elementClass = `add-${triggerElement.dataset.type}`;
    toggleLoadingState(triggerElement, elementClass, true);

    let ean = "";
    for (let i = 0; i < length - 1; i++) {
        ean += Math.floor(Math.random() * 10).toString();
    }

    // Calculate checksum digit according to EAN-13 standard
    let sum = 0;
    for (let i = 0; i < ean.length; i++) {
        sum += parseInt(ean[i]) * (i % 2 === 0 ? 1 : 3);
    }
    const checksum = (10 - (sum % 10)) % 10;
    ean += checksum.toString();

    toggleLoadingState(triggerElement, elementClass, false);
    return ean;
};

/**
 * Calculates the alert date for product expiration based on the expiration date and alert days.
 *
 * This function retrieves the expiration date and the number of alert days from the UI,
 * subtracts the alert days from the expiration date, and returns the resulting date formatted
 * in Spanish (es-ES) in a human-readable way.
 *
 * The 'T00:00:00' is appended to the date string to force the local timezone and avoid JavaScript
 * subtracting a day by default due to UTC conversion.
 *
 * @returns {string|null} The formatted alert date in Spanish, or null if no expiration date is set.
 */
export const calculateAlertDate = () => {
	const dateString = $("#expiration_date").val();
	const alertDays = parseInt($("#expiration_alert_days").val(), 10) || 0;

	if (dateString) {
		// 'T00:00:00' is added to force the local timezone and avoid JavaScript
		// subtracting a day by default due to UTC conversion
		const expiration = new Date(`${dateString}T00:00:00`);

		// Subtract the alert days
		expiration.setDate(expiration.getDate() - alertDays);

		// Format the date in Spanish and in a human-readable way
		const options = { year: 'numeric', month: 'long', day: 'numeric' };
		const alertDateFormatted = expiration.toLocaleDateString('es-ES', options);

		return alertDateFormatted;
	}

	return null;
}