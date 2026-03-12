import {
    clearAllFieldErrors,
    showFieldError,
    clearFieldError,
    validateAndDisplayField
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';
import { fetchWithErrorHandling } from '../../utils/error-handling.js';

// Variables para detalles (tomada de window definido en la vista)
let detailIndex = window.detailIndex || 0;

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

// Cambiar tipo (producto/insumo) -> recargar opciones
$(document).on('change', '.purchasable-type', function() {
    const row = $(this).closest('tr');
    const type = $(this).val();
    const selectId = row.find('.purchasable-id');
    selectId.empty().append('<option value="">Seleccionar</option>');
    
    // Aquí deberías cargar los datos según el tipo (AJAX o datos precargados)
    let options = [];
    if (type === 'product') {
        // options = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->price]));
    } else {
        // options = @json($supplies->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'price' => $s->price]));
    }
    
    options.forEach(item => {
        selectId.append(`<option value="${item.id}" data-price="${item.price}">${item.name}</option>`);
    });
    row.find('.unit-price').val('');
});

// Al seleccionar un item, auto-completar precio
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

// Validación del formulario principal
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

    // Aquí debes tener tu función de validación para los campos principales
    // if (!validatePurchaseForm(values)) return false;

    // Validar que haya al menos un detalle
    if ($('#details-container .detail-row').length === 0) {
        SwalToast.fire({
            icon: 'error',
            text: 'Debe agregar al menos un producto/insumo.'
        });
        return false;
    }

    // Validar cada detalle
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

// Envío del formulario principal
$(document).on('submit', 'form[id$="-purchase-form"]', (e) => {
    e.preventDefault();
    const formId = e.currentTarget.id;
    setLoadingState(formId, true);

    if (submitPurchaseForm()) {
        e.currentTarget.submit();
    } else {
        setLoadingState(formId, false);
    }
});

// --------------------------------------------------------------
// Quick supplier creation via offcanvas
// --------------------------------------------------------------

// Validación en tiempo real para el teléfono: solo dígitos, máximo 8
$('#quick-phone').on('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 8);
});

$(document).on('submit', '#quick-supplier-form', async function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $submitBtn = $('#quick-supplier-submit');
    const $spinner = $('#quick-supplier-spinner');
    const url = $form.attr('action'); // route('suppliers.store')

    // Limpiar errores previos
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').text('');

    // Mostrar estado de carga
    $submitBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

    // Concatenar el prefijo +506 al teléfono antes de enviar
    const phoneInput = $('#quick-phone');
    const rawPhone = phoneInput.val().replace(/\D/g, ''); // solo dígitos

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: new FormData($form[0]),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('La respuesta no es JSON. Posible redirección.');
        }

        const data = await response.json();

        if (data.success) {
            // Agregar nuevo proveedor al select
            const $select = $('#supplier_id');
            const newOption = new Option(data.supplier.name, data.supplier.id, true, true);
            $select.append(newOption).trigger('change');

            // Cerrar offcanvas y resetear formulario
            const offcanvasEl = document.getElementById('offcanvasSupplier');
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (offcanvas) offcanvas.hide();
            
            $form[0].reset();

            // Mostrar mensaje de éxito
            SwalToast.fire({
                icon: 'success',
                text: 'Proveedor creado correctamente.'
            });
        } else {
            // Mostrar errores de validación
            if (data.errors) {
                $.each(data.errors, function(field, messages) {
                    const $input = $(`#quick-${field}`);
                    $input.addClass('is-invalid');
                    $(`#quick-${field}-error`).text(messages[0]);
                });
            } else {
                SwalToast.fire({
                    icon: 'error',
                    text: data.message || 'Error al crear el proveedor.'
                });
            }
        }
    } catch (error) {
        console.error('Error en la petición:', error);
        SwalToast.fire({
            icon: 'error',
            text: 'Ocurrió un error inesperado. Verifica la consola.'
        });
    } finally {
        $submitBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    }
});

// Resetear formulario al cerrar el offcanvas
$('#offcanvasSupplier').on('hidden.bs.offcanvas', function() {
    $('#quick-supplier-form')[0].reset();
    $('#quick-supplier-form').find('.is-invalid').removeClass('is-invalid');
    $('#quick-supplier-form').find('.invalid-feedback').text('');
});