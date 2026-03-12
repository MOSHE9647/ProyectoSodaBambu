import {
    clearAllFieldErrors,
    showFieldError,
    clearFieldError,
    validateAndDisplayField
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';

// ... (código existente de validación de campos principales)

// Variables para detalles
//let detailIndex = {{ isset($purchase) ? $purchase->details->count() : 0 }};
 
// Función para recalcular total
function recalcTotal() {
    let total = 0;
    $('#details-container .detail-row').each(function() {
        const qty = $(this).find('.quantity').val() || 0;
        const price = $(this).find('.unit-price').val() || 0;
        const subtotal = qty * price;
        $(this).find('.subtotal').text(subtotal.toFixed(2));
        total += subtotal;
    });
    $('#total, #total-display').text(total.toFixed(2));
    $('#total').val(total.toFixed(2));
}

// Agregar fila
$('#add-detail').on('click', function() {
    const index = detailIndex++;
    const template = `
        <tr class="detail-row" data-index="${index}">
            <td>
                <select name="details[${index}][purchasable_type]" class="form-select form-select-sm purchasable-type" required>
                    <option value="product">Producto</option>
                    <option value="supply">Insumo</option>
                </select>
            </td>
            <td>
                <select name="details[${index}][purchasable_id]" class="form-select form-select-sm purchasable-id" required>
                    <option value="">Seleccionar</option>
                </select>
            </td>
            <td>
                <input type="number" name="details[${index}][quantity]" class="form-control form-control-sm quantity" value="1" min="1" step="1" required>
            </td>
            <td>
                <input type="number" name="details[${index}][unit_price]" class="form-control form-control-sm unit-price" value="0" min="0" step="0.01" required>
            </td>
            <td>
                <span class="subtotal">0.00</span>
            </td>
            <td>
                <input type="date" name="details[${index}][expiration_date]" class="form-control form-control-sm">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-detail"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `;
    $('#details-container').append(template);
    recalcTotal();
});

// Eliminar fila
$(document).on('click', '.remove-detail', function() {
    $(this).closest('tr').remove();
    recalcTotal();
});

// Cambiar tipo (producto/insumo) -> recargar opciones vía AJAX o con datos precargados
$(document).on('change', '.purchasable-type', function() {
    const row = $(this).closest('tr');
    const type = $(this).val();
    const selectId = row.find('.purchasable-id');
    selectId.empty().append('<option value="">Seleccionar</option>');
    
    // Aquí deberías cargar los datos según el tipo. Si ya tienes las variables en JS:
    let options = [];
    if (type === 'product') {
       // options = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->price]));
    } else {
       // options = @json($supplies->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'price' => $s->price]));
    }
    
    options.forEach(item => {
        selectId.append(`<option value="${item.id}" data-price="${item.price}">${item.name}</option>`);
    });
    row.find('.unit-price').val(''); // limpiar precio si se cambia
});

// Al seleccionar un item, auto-completar precio si está disponible
$(document).on('change', '.purchasable-id', function() {
    const row = $(this).closest('tr');
    const price = $(this).find('option:selected').data('price');
    if (price !== undefined) {
        row.find('.unit-price').val(price);
    }
    recalcTotal();
});

// Recalcular al cambiar cantidad o precio
$(document).on('input', '.quantity, .unit-price', function() {
    recalcTotal();
});

// Validación del formulario (incluye detalles)
function submitPurchaseForm() {
    clearAllFieldErrors(fieldValidators);

    const $invoice = $('#invoice_number');
    const $date = $('#date');
    const $supplier = $('#supplier_id');
    const $payment = $('#payment_status');

    const values = {
        invoice_number: $invoice.val().trim(),
        date: $date.val(),
        supplier_id: $supplier.val(),
        payment_status: $payment.val()
    };

    if (!validatePurchaseForm(values)) return false;

    // Validar que haya al menos un detalle
    if ($('#details-container .detail-row').length === 0) {
        SwalToast.fire({
            icon: 'error',
            text: 'Debe agregar al menos un producto/insumo.'
        });
        return false;
    }

    // Validar que cada detalle tenga seleccionado un producto y cantidad > 0
    let valid = true;
    $('#details-container .detail-row').each(function() {
        const purchasableId = $(this).find('.purchasable-id').val();
        const quantity = $(this).find('.quantity').val();
        const unitPrice = $(this).find('.unit-price').val();
        if (!purchasableId) {
            $(this).find('.purchasable-id').addClass('is-invalid');
            valid = false;
        } else {
            $(this).find('.purchasable-id').removeClass('is-invalid');
        }
        if (!quantity || quantity <= 0) {
            $(this).find('.quantity').addClass('is-invalid');
            valid = false;
        } else {
            $(this).find('.quantity').removeClass('is-invalid');
        }
        if (!unitPrice || unitPrice < 0) {
            $(this).find('.unit-price').addClass('is-invalid');
            valid = false;
        } else {
            $(this).find('.unit-price').removeClass('is-invalid');
        }
    });

    return valid;
}

// Al enviar el formulario
$(document).on('submit', `#${FORM_ID}`, (e) => {
    e.preventDefault();
    setLoadingState(FORM_ID, true);

    if (submitPurchaseForm()) {
        e.currentTarget.submit();
    } else {
        setLoadingState(FORM_ID, false);
    }
});

// Inicializar total al cargar
$(document).ready(function() {
    recalcTotal();
});