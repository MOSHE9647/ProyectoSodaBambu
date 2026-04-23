<style>
    /* Custom styles for the payment modal */
    .swal2-popup {
        width: 500px !important;
        max-width: 95vw !important;
    }
</style>

{{-- Aquí puedes agregar campos adicionales para métodos de pago, información del cliente, etc. --}}
<div class="d-flex flex-column text-start">
    <h5 class="mb-3">Detalles del Pago</h5>
    <p class="mb-2">Total a pagar: <span class="fw-bold text-success">₡ {{ number_format($paymentTotal, 2, ',', ' ') }}</span></p>
</div>