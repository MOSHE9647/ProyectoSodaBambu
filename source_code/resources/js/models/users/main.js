import '../../../libs/datatables/datatables.js';

$(document).ready(() => {
	$('#users-table').DataTable({
		processing: true,
		serverSide: true,
		responsive: true,
		autoWidth: true,
		ajax: userRoute, // Defined in the Blade template as a JS variable along with userRoles
		columns: [
			{ data: 'name', name: 'name' },
			{ data: 'email', name: 'email' },
			{
				data: 'roles.0.name',
				name: 'role',
				render: function(data, type, row) {
					// Try to find the role label from userRoles
					const role = userRoles.find(role => role.value === data);
					return role ? role.label : data; // Fallback to raw data if not found
				}
			},
			{
				data: 'created_at',
				name: 'created_at',
				render: function(data) {
					const date = new Date(data);
					const day = String(date.getDate()).padStart(2, '0');
					const month = date.toLocaleDateString('es-ES', { month: 'long' });
					const year = date.getFullYear();
					return `${day} de ${month}, ${year}`;
				}
			},
			{
				data: null,
				name: 'actions',
				orderable: false,
				searchable: false,
				width: '15%',
				render: function(data, type, row) {
					return `
						<div class="d-flex justify-content-center">
							<a href="${userEditRoute.replace(':id', row.id)}" class="btn btn-sm btn-primary me-2">Editar</a>
							<form method="POST" action="${userDeleteRoute.replace(':id', row.id)}" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
								<input type="hidden" name="_token" value="${csrfToken}">
								<input type="hidden" name="_method" value="DELETE">
								<button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
							</form>
						</div>
					`;
				}
			}
		],
		order: [[0, 'asc']],
	});
});
