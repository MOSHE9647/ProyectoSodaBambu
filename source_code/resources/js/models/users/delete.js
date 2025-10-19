import { SwalModal } from "../../utils/sweetalert.js";

export function deleteUser(e) {
	e.preventDefault();
	const form = e.currentTarget;
	setLoadingState(form, 'delete-form', true);

	SwalModal.fire({
		title: '¿Estás seguro de eliminar este usuario?',
		text: "Esta acción no se puede deshacer.",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Sí, eliminar',
		cancelButtonText: 'Cancelar',
	}).then((result) => {
		if (result.isConfirmed) {
			form.submit();
		}
	}).finally(() => {
		setLoadingState(form, 'delete-form', false);
	});
}

function setLoadingState(form, formClass, isLoading) {
	// Accept either a form element or a class name
	if (typeof form === 'string') {
		form = document.querySelector(`.${form}`);
	}

	// If form is not found or invalid, log an error and return
	if (!form || !(form instanceof HTMLElement)) {
		console.error(`Formulario no encontrado o inválido para formClass: ${formClass}`);
		return;
	}

	const spinner = $(form).find(`.${formClass}-spinner`);
	const submitButton = $(form).find(`.${formClass}-button`);
	const submitButtonText = $(form).find(`.${formClass}-button-text`);

	// Check if elements exist
	if (!submitButton.length || !spinner.length || !submitButtonText.length) {
		console.error(`Submit button or spinner not found for form class: ${formClass}`);
		return;
	}

	// Toggle loading state
	if (isLoading) {
		// Disable the button
		submitButton.attr('disabled', 'disabled');
		submitButtonText.removeClass('d-flex').addClass('d-none');

		// Show the spinner
		spinner.removeClass('d-none');
	} else {
		// Enable the button and show the text
		submitButton.removeAttr('disabled');
		submitButtonText.removeClass('d-none').addClass('d-flex');

		// Hide the spinner
		spinner.addClass('d-none');
	}
}
