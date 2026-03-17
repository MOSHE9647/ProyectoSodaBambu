import {
    clearAllFieldErrors,
    showFieldError,
    clearFieldError,
    validateAndDisplayField
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';
import { fetchWithErrorHandling } from '../../utils/error-handling.js';

let detailIndex = window.detailIndex || 0;
let productsList = window.products || [];
let suppliesList = window.supplies || [];

function populateSelect(selectElement, type) {
    const list = type === 'product' ? productsList : suppliesList;
    selectElement.empty().append('<option value="">Seleccionar</option>');
    list.forEach(item => {
        selectElement.append(`<option value="${item.id}" data-price="${item.price}">${item.name}</option>`);
    });
}

function refreshAllSelectsOfType(type) {
    $(`.detail-row`).each(function() {
        const rowType = $(this).find('.purchasable-type').val();
        if (rowType === type) {
            const select = $(this).find('.purchasable-id');
            populateSelect(select, type);
        }
    });
}

function toggleEmptyRow() {
    const hasRows = $('#details-container .detail-row').length > 0;
    $('#empty-details-row').toggle(!hasRows);
}

function recalcTotal() {
    let total = 0;
    $('#details-container .detail-row').each(function() {
        const qty = $(this).find('.quantity').val() || 0;
        const price = $(this).find('.unit-price').val() || 0;
        const subtotal = qty * price;
        $(this).find('.subtotal').text(subtotal.toFixed(2));
        total += subtotal;
    });
    $('#total-display').text(total.toFixed(2));
    $('#total').val(total.toFixed(2));
}

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
    const newRow = $('#details-container tr').last();
    populateSelect(newRow.find('.purchasable-id'), 'product');
    toggleEmptyRow();
    recalcTotal();
});

$(document).on('click', '.remove-detail', function() {
    $(this).closest('tr').remove();
    toggleEmptyRow();
    recalcTotal();
});

$(document).on('change', '.purchasable-type', function() {
    const row = $(this).closest('tr');
    const type = $(this).val();
    const select = row.find('.purchasable-id');
    populateSelect(select, type);
    row.find('.unit-price').val('');
    recalcTotal();
});

$(document).on('change', '.purchasable-id', function() {
    const row = $(this).closest('tr');
    const price = $(this).find('option:selected').data('price');
    if (price !== undefined) {
        row.find('.unit-price').val(price);
    }
    recalcTotal();
});

$(document).on('input', '.quantity, .unit-price', function() {
    recalcTotal();
});

function submitPurchaseForm() {
    const $invoice = $('#invoice_number');
    const $date = $('#date');
    const $supplier = $('#supplier_id');
    const $payment = $('#payment_status');

    if (!$invoice.val().trim()) {
        SwalToast.fire({ icon: 'error', text: 'El número de factura es obligatorio.' });
        return false;
    }
    if (!$date.val()) {
        SwalToast.fire({ icon: 'error', text: 'La fecha es obligatoria.' });
        return false;
    }
    if (!$supplier.val()) {
        SwalToast.fire({ icon: 'error', text: 'Debe seleccionar un proveedor.' });
        return false;
    }
    if (!$payment.val()) {
        SwalToast.fire({ icon: 'error', text: 'Debe seleccionar un estado de pago.' });
        return false;
    }

    if ($('#details-container .detail-row').length === 0) {
        SwalToast.fire({ icon: 'error', text: 'Debe agregar al menos un producto/insumo.' });
        return false;
    }

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
// Quick supplier creation
// --------------------------------------------------------------

$('#quick-phone').on('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 8);
});

$(document).on('submit', '#quick-supplier-form', async function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $submitBtn = $('#quick-supplier-submit');
    const $spinner = $('#quick-supplier-spinner');
    const url = $form.attr('action');

    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').text('');

    $submitBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

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
            throw new Error('La respuesta no es JSON.');
        }

        const data = await response.json();

        if (data.success) {
            const $select = $('#supplier_id');
            const newOption = new Option(data.supplier.name, data.supplier.id, true, true);
            $select.append(newOption).trigger('change');

            const offcanvasEl = document.getElementById('offcanvasSupplier');
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (offcanvas) offcanvas.hide();
            
            $form[0].reset();

            SwalToast.fire({
                icon: 'success',
                text: 'Proveedor creado correctamente.'
            });
        } else {
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
            text: 'Ocurrió un error inesperado.'
        });
    } finally {
        $submitBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    }
});

// --------------------------------------------------------------
// Quick product creation
// --------------------------------------------------------------

function loadCategories() {
    const $categorySelect = $('#quick-product-category');
    $categorySelect.prop('disabled', true).empty().append('<option value="">Cargando categorías...</option>');

    $.ajax({
        url: window.categoriesIndexUrl,
        method: 'GET',
        data: { simple: 1 },
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(data) {
            $categorySelect.empty().append('<option value="">Seleccionar categoría</option>');
            $.each(data, function(i, cat) {
                $categorySelect.append(`<option value="${cat.id}">${cat.name}</option>`);
            });
        },
        error: function(xhr) {
            console.error('Error cargando categorías:', xhr.status, xhr.responseText);
            $categorySelect.empty().append('<option value="">Error al cargar</option>');
            SwalToast.fire({
                icon: 'error',
                text: 'No se pudieron cargar las categorías. Intente de nuevo.'
            });
        },
        complete: function() {
            $categorySelect.prop('disabled', false);
        }
    });
}

document.addEventListener('show.bs.offcanvas', function(e) {
    if (e.target && e.target.id === 'offcanvasProduct') {
        loadCategories();
    }
});

$('#quick-product-form').on('submit', async function(e) {
    e.preventDefault();

    const $form = $(this);
    const $submitBtn = $('#quick-product-submit');
    const $spinner = $('#quick-product-spinner');
    const url = $form.attr('action');

    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').text('');

    $submitBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

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
            throw new Error('La respuesta no es JSON.');
        }

        const data = await response.json();

        if (data.success) {
            productsList.push({
                id: data.product.id,
                name: data.product.name,
                price: data.product.sale_price || 0
            });
            refreshAllSelectsOfType('product');

            const offcanvasEl = document.getElementById('offcanvasProduct');
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (offcanvas) offcanvas.hide();

            $form[0].reset();

            SwalToast.fire({
                icon: 'success',
                text: 'Producto creado correctamente.'
            });
        } else {
            if (data.errors) {
                $.each(data.errors, function(field, messages) {
                    const $input = $(`#quick-product-${field}`);
                    $input.addClass('is-invalid');
                    $(`#quick-product-${field}-error`).text(messages[0]);
                });
            } else {
                SwalToast.fire({
                    icon: 'error',
                    text: data.message || 'Error al crear el producto.'
                });
            }
        }
    } catch (error) {
        console.error('Error en la petición:', error);
        SwalToast.fire({
            icon: 'error',
            text: 'Ocurrió un error inesperado.'
        });
    } finally {
        $submitBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    }
});

// --------------------------------------------------------------
// Quick supply creation
// --------------------------------------------------------------

$('#quick-supply-form').on('submit', async function(e) {
    e.preventDefault();

    const $form = $(this);
    const $submitBtn = $('#quick-supply-submit');
    const $spinner = $('#quick-supply-spinner');
    const url = $form.attr('action');

    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').text('');

    $submitBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

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
            throw new Error('La respuesta no es JSON.');
        }

        const data = await response.json();

        if (data.success) {
            suppliesList.push({
                id: data.supply.id,
                name: data.supply.name,
                price: 0
            });
            refreshAllSelectsOfType('supply');

            const offcanvasEl = document.getElementById('offcanvasSupply');
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (offcanvas) offcanvas.hide();

            $form[0].reset();

            SwalToast.fire({
                icon: 'success',
                text: 'Insumo creado correctamente.'
            });
        } else {
            if (data.errors) {
                $.each(data.errors, function(field, messages) {
                    const $input = $(`#quick-supply-${field}`);
                    $input.addClass('is-invalid');
                    $(`#quick-supply-${field}-error`).text(messages[0]);
                });
            } else {
                SwalToast.fire({
                    icon: 'error',
                    text: data.message || 'Error al crear el insumo.'
                });
            }
        }
    } catch (error) {
        console.error('Error en la petición:', error);
        SwalToast.fire({
            icon: 'error',
            text: 'Ocurrió un error inesperado.'
        });
    } finally {
        $submitBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    }
});

$('#offcanvasProduct, #offcanvasSupply, #offcanvasSupplier').on('hidden.bs.offcanvas', function() {
    let formId;
    if (this.id === 'offcanvasProduct') formId = '#quick-product-form';
    else if (this.id === 'offcanvasSupply') formId = '#quick-supply-form';
    else formId = '#quick-supplier-form';
    
    $(formId)[0].reset();
    $(formId).find('.is-invalid').removeClass('is-invalid');
    $(formId).find('.invalid-feedback').text('');
});