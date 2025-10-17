async function fetchUser(url) {
	try {
		const response = await fetch(url);
		return await response.json();
	} catch (error) {
		console.error('Error fetching user data:', error);
		throw error;
	}
}

export function showUser (url) {
	fetchUser(url).then(userData => {
		if (userData) {
			// TODO: Implement user detail modal population and display
			/*console.log(userData);
			// Populate modal fields with user data
			$('#userModalLabel').text(`Usuario: ${userData.name}`);
			$('#userName').text(userData.name);
			$('#userEmail').text(userData.email);
			$('#userRoles').text(userData.roles.map(role => role.name).join(', '));
			const createdAt = new Date(userData.created_at);
			const day = String(createdAt.getDate()).padStart(2, '0');
			const month = createdAt.toLocaleDateString('es-ES', { month: 'long' });
			const year = createdAt.getFullYear();
			$('#userCreatedAt').text(`${day} de ${month} del ${year}`);

			// Show the modal
			const userModal = new bootstrap.Modal(document.getElementById('userModal'));

			userModal.show();*/
		} else {
			alert('No se pudo cargar la información del usuario.');
		}
	}).catch(error => {
		console.error('Error loading user data:', error);
		alert('Ocurrió un error al cargar la información del usuario.');
	});
}
