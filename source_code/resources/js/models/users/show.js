import {SwalModal} from "../../utils/sweetalert.js";

async function fetchUser(url) {
	try {
		const response = await fetch(url);
		return await response.text();
	} catch (error) {
		console.error('Error fetching user data:', error);
		throw error;
	}
}

export function showUser(url) {
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
	});
}
