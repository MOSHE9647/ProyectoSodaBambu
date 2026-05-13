document.addEventListener('DOMContentLoaded', function () {
    const btnShowHistory = document.getElementById('btn-show-history');
    const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    const tableBody = document.getElementById('history-table-body');
    const loader = document.getElementById('history-loader');

    if (btnShowHistory) {
        btnShowHistory.addEventListener('click', function () {
            // 1. Abrir el modal inmediatamente
            historyModal.show();
            
            // 2. Limpiar la tabla y mostrar el loader
            tableBody.innerHTML = '';
            loader.classList.remove('d-none');

            // 3. Consultar las ventas al servidor
            // Nota: Esta ruta debemos crearla en web.php
            fetch('/sales/history-today')
                .then(response => response.json())
                .then(data => {
                    loader.classList.add('d-none');
                    
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay ventas registradas hoy.</td></tr>';
                        return;
                    }

                    // 4. Llenar la tabla con los datos
                    data.forEach(sale => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="fw-bold">${sale.invoice_number}</td>
                            <td>${sale.formatted_time}</td>
                            <td class="text-success fw-bold">₡${parseFloat(sale.total).toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
                            <td>
                                <span class="badge border text-dark bg-light">
                                    ${sale.payment_method || 'Pendiente'}
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="reprintTicket('${sale.id}')">
                                    <i class="bi bi-printer"></i>
                                </button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    loader.classList.add('d-none');
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar el historial.</td></tr>';
                    console.error('Error:', error);
                });
        });
    }
});

// Función global para reimprimir (puedes dejarla vacía por ahora)
window.reprintTicket = function(saleId) {
    console.log("Reimprimiendo venta ID:", saleId);
    // Aquí irá la lógica de impresión más adelante
};