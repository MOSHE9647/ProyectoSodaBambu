import { SwalModal } from "../../utils/sweetalert.js";

async function fetchUser(url) {
	try {
		const response = await fetch(url);
		return await response.text();
	} catch (error) {
		console.error('Error fetching user data:', error);
		throw error;
	}
}

export function showUser(url, anchor) {
	setLoadingState(anchor, 'info', true);

	fetchUser(url).then(userInfoHtml => {
		if (userInfoHtml) {
			SwalModal.fire({
				title: 'Información del Usuario',
				showConfirmButton: false,
				showCancelButton: true,
				cancelButtonText: 'Cerrar',
				html: `${userInfoHtml}`,
			});
		} else {
			alert('No se pudo cargar la información del usuario.');
		}
	}).catch(error => {
		console.error('Error loading user data:', error);
		alert('Ocurrió un error al cargar la información del usuario.');
	}).finally(() => {
		setLoadingState(anchor, 'info', false);
	});
}

function setLoadingState(anchor, anchorClass, isLoading) {
	// Accept either an anchor element or a class name
	if (typeof anchor === 'string') {
		anchor = $(`.${anchor}`);
	}

	// If anchor is not found or invalid, log an error and return
	if (!anchor || !(anchor instanceof HTMLElement)) {
		console.error(`Anchor not found or invalid for anchorClass: ${anchorClass}`);
		return;
	}

	// Get necessary elements within the anchor
	const spinner = $(anchor).find(`.${anchorClass}-spinner`);
	const icon = $(anchor).find(`.${anchorClass}-button-text`);

	// Check if elements exist
	if (!icon.length || !spinner.length) {
		console.error(`Icon or spinner not found for anchor class: ${anchorClass}`);
		return;
	}

	// Toggle loading state
	if (isLoading) {
		// Disable the anchor
		$(anchor).attr('disabled', 'disabled');
		icon.removeClass('d-flex').addClass('d-none');

		// Show the spinner
		spinner.removeClass('d-none');
	} else {
		// Enable the anchor and show the icon
		$(anchor).removeAttr('disabled');
		icon.removeClass('d-none').addClass('d-flex');

		// Hide the spinner
		spinner.addClass('d-none');
	}
}
