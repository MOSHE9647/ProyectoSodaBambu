import {
    clearAllFieldErrors,
    showFieldError,
    clearFieldError,
    validateAndDisplayField
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';
import { fetchWithErrorHandling } from '../../utils/error-handling.js';
import { SwalNotificationTypes, SwalToast } from '../../utils/sweetalert.js';

let detailIndex = window.detailIndex || 0;
let productsList = window.products || [];
let suppliesList = window.supplies || [];

// EIF-165: Aplica solo al catálogo de productos, no al select de compras
const ALLOWED_PRODUCT_TYPES = [];

/**
 * EIF-165: Filtra la lista de productos para excluir platillos.
 */
function getFilteredProducts() {
    if (!ALLOWED_PRODUCT_TYPES.length) return productsList;
    return productsList.filter(p => {
        if (!p.type) return true;
        return ALLOWED_PRODUCT_TYPES.includes(p.type.toLowerCase());
    });
}

// EIF-172 & EIF-168: Labels en español
const TYPE_LABELS = {
    product: 'Producto',
    supply: 'Insumo',
};

/**
 * EIF-168: Poblar el select de productos/insumos según el tipo seleccionado.
 */
function populateSelect(selectElement, type) {
    const list = type === 'product' ? getFilteredProducts() : suppliesList;
    selectElement.empty().append('<option value="">Seleccionar</option>');
    list.forEach(item => {
        selectElement.append(`<option value="${item.id}" data-price="${item.price}">${item.name}</option>`);
    });
}

function refreshAllSelectsOfType(type) {
    $(`.detail-row`).each(function () {
        const rowType = $(this).find('.purchasable-type').val();
        if (rowType === type) {
            const select = $(this).find('.purchasable-id');
            const currentVal = select.val();
            populateSelect(select, type);
            if (currentVal) select.val(currentVal);
        }
    });
}

function toggleEmptyRow() {
    const hasRows = $('#details-container .detail-row').length > 0;
    $('#empty-details-row').toggle(!hasRows);
}

function recalcTotal() {
    let total = 0;
    $('#details-container .detail-row').each(function () {
        const qty = parseFloat($(this).find('.quantity').val()) || 0;
        const price = parseFloat($(this).find('.unit-price').val()) || 0;
        const subtotal = qty * price;
        $(this).find('.subtotal').text(subtotal.toFixed(2));
        total += subtotal;
    });
    $('#total-display').text(total.toFixed(2));
    $('#total').val(total.toFixed(2));
}

// EIF-172: Construir el select de tipo
function buildTypeSelect(name, selectedValue = 'product') {
    const options = Object.entries(TYPE_LABELS)
        .map(([val, label]) => {
            const selected = val === selectedValue ? 'selected' : '';
            return `<option value="${val}" ${selected}>${label}</option>`;
        })
        .join('');
    return `<select name="${name}" class="form-select form-select-sm purchasable-type" required>${options}</select>`;
}

/**
 * Agrega una fila a la tabla de detalles con un item preseleccionado.
 * @param {string} type  - 'product' o 'supply'
 * @param {object} item  - { id, name, price }
 */
function addDetailRow(type, item) {
    const index = detailIndex++;

    const template = `
        <tr class="detail-row" data-index="${index}">
            <td>
                ${buildTypeSelect(`details[${index}][purchasable_type]`, type)}
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
                <input type="number" name="details[${index}][unit_price]" class="form-control form-control-sm unit-price" value="${item.price || 0}" min="0" step="0.01" required>
            </td>
            <td>
                <span class="subtotal">${parseFloat(item.price || 0).toFixed(2)}</span>
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
    populateSelect(newRow.find('.purchasable-id'), type);
    newRow.find('.purchasable-id').val(item.id);
    toggleEmptyRow();
    recalcTotal();
}

$('#add-detail').on('click', function () {
    const index = detailIndex++;

    const template = `
        <tr class="detail-row" data-index="${index}">
            <td>
                ${buildTypeSelect(`details[${index}][purchasable_type]`)}
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

$(document).on('click', '.remove-detail', function () {
    $(this).closest('tr').remove();
    toggleEmptyRow();
    recalcTotal();
});

$(document).on('change', '.purchasable-type', function () {
    const row = $(this).closest('tr');
    const type = $(this).val();
    const select = row.find('.purchasable-id');
    populateSelect(select, type);
    row.find('.unit-price').val('');
    recalcTotal();
});

$(document).on('change', '.purchasable-id', function () {
    const row = $(this).closest('tr');
    const price = $(this).find('option:selected').data('price');
    if (price !== undefined) {
        row.find('.unit-price').val(price);
    }
    recalcTotal();
});

$(document).on('input', '.quantity, .unit-price', function () {
    recalcTotal();
});

// EIF-169: Calcular precio de venta automáticamente
function calcSalePrice() {
    const cost = parseFloat($('#quick-product-reference-cost').val()) || 0;
    const tax = parseFloat($('#quick-product-tax-percentage').val()) || 0;
    const margin = parseFloat($('#quick-product-margin-percentage').val()) || 0;

    if (cost > 0) {
        const salePrice = cost * (1 + tax) * (1 + margin);
        $('#quick-product-sale-price').val(salePrice.toFixed(2));
    }
}

$('#quick-product-reference-cost, #quick-product-tax-percentage, #quick-product-margin-percentage')
    .on('input change', calcSalePrice);

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
    const today = new Date().toISOString().split('T')[0];

    $('#details-container .detail-row').each(function () {
        const purchasableId = $(this).find('.purchasable-id').val();
        const quantity = $(this).find('.quantity').val();
        const unitPrice = $(this).find('.unit-price').val();
        const expDate = $(this).find('input[name$="[expiration_date]"]').val();

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
        if (unitPrice === '' || unitPrice < 0) {
            $(this).find('.unit-price').addClass('is-invalid');
            valid = false;
        } else {
            $(this).find('.unit-price').removeClass('is-invalid');
        }

        const $expInput = $(this).find('input[name$="[expiration_date]"]');
        $expInput.removeClass('is-invalid');
        $(this).find('.expiration-error').remove();

        if (!expDate) {
            $expInput.addClass('is-invalid');
            $expInput.closest('td').append('<div class="text-danger small expiration-error">La fecha de vencimiento es obligatoria.</div>');
            valid = false;
        } else if (expDate <= today) {
            $expInput.addClass('is-invalid');
            $expInput.closest('td').append('<div class="text-danger small expiration-error">La fecha debe ser mayor al día de hoy.</div>');
            valid = false;
        }
    });

    return valid;
}

$(document).on('submit', 'form[id$="-purchase-form"]', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('[type="submit"]');
    const $spinner = $btn.find('.spinner-border');

    if (!submitPurchaseForm()) return;

    $btn.prop('disabled', true);
    $spinner.removeClass('d-none');
    $form[0].submit();
});


// --------------------------------------------------------------
// Quick supplier creation
// --------------------------------------------------------------

$('#quick-phone').on('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 8);
});

$(document).on('submit', '#quick-supplier-form', async function (e) {
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
        const formData = new FormData($form[0]);

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('La respuesta del servidor no es JSON. Verifique que la ruta retorne JSON.');
        }

        const data = await response.json();

        if (data.success) {
            const $select = $('#supplier_id');
            const newOption = new Option(data.supplier.name, data.supplier.id, true, true);
            $select.append(newOption).val(data.supplier.id);

            const offcanvasEl = document.getElementById('offcanvasSupplier');
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (offcanvas) offcanvas.hide();

            $form[0].reset();

            SwalToast.fire({ icon: 'success', text: 'Proveedor creado correctamente.' });
        } else {
            if (data.errors) {
                $.each(data.errors, function (field, messages) {
                    const $input = $(`#quick-${field}`);
                    $input.addClass('is-invalid');
                    $(`#quick-${field}-error`).text(messages[0]);
                });
            } else {
                SwalToast.fire({ icon: 'error', text: data.message || 'Error al crear el proveedor.' });
            }
        }
    } catch (error) {
        console.error('Error en la petición:', error);
        SwalToast.fire({ icon: 'error', text: 'Ocurrió un error inesperado: ' + error.message });
    } finally {
        $submitBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    }
});


// --------------------------------------------------------------
// Quick product creation (offcanvas)
// --------------------------------------------------------------

$('#quick-product-has-inventory').on('change', function () {
    if ($(this).is(':checked')) {
        $('#quick-product-stock-fields').slideDown();
        $('#quick-product-stock-minimo').prop('required', true);
    } else {
        $('#quick-product-stock-fields').slideUp();
        $('#quick-product-stock-minimo').prop('required', false).val('');
    }
});

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
        success: function (response) {
            $categorySelect.empty().append('<option value="">Seleccionar categoría</option>');
            const categories = Array.isArray(response) ? response : (response.data || []);
            if (categories.length === 0) {
                $categorySelect.append('<option value="" disabled>No hay categorías disponibles</option>');
                return;
            }
            $.each(categories, function (i, cat) {
                $categorySelect.append(`<option value="${cat.id}">${cat.name}</option>`);
            });
        },
        error: function (xhr) {
            console.error('Error cargando categorías:', xhr.status, xhr.responseText);
            $categorySelect.empty().append('<option value="">Error al cargar</option>');
            SwalToast.fire({ icon: 'error', text: 'No se pudieron cargar las categorías. Intente de nuevo.' });
        },
        complete: function () {
            $categorySelect.prop('disabled', false);
        }
    });
}

document.addEventListener('show.bs.offcanvas', function (e) {
    if (e.target && e.target.id === 'offcanvasProduct') {
        loadCategories();
    }
});

$('#quick-product-form').on('submit', async function (e) {
    e.preventDefault();

    const $form = $(this);
    const $submitBtn = $('#quick-product-submit');
    const $spinner = $('#quick-product-spinner');
    const url = $form.attr('action');

    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').text('');

    if ($('#quick-product-has-inventory').is(':checked')) {
        const stockMinimo = $('#quick-product-stock-minimo').val();
        if (stockMinimo === '' || parseInt(stockMinimo) < 0) {
            $('#quick-product-stock-minimo').addClass('is-invalid');
            $('#quick-product-stock-minimo-error').text('El stock mínimo es obligatorio y debe ser mayor o igual a 0.');
            return;
        }
    }

    $submitBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

    try {
        const formData = new FormData($form[0]);

        if (!formData.has('has_inventory') || formData.get('has_inventory') !== '1') {
            formData.set('has_inventory', '0');
        }
        if (!formData.get('barcode')) formData.set('barcode', '');
        if (!formData.get('reference_cost')) formData.set('reference_cost', '0');
        if (!formData.get('tax_percentage')) formData.set('tax_percentage', '0');
        if (!formData.get('margin_percentage')) formData.set('margin_percentage', '0');
        if (!formData.get('sale_price')) formData.set('sale_price', '0');
        formData.delete('stock_actual');

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('La respuesta del servidor no es JSON.');
        }

        const data = await response.json();

        if (data.success) {
            const newProduct = {
                id: data.product.id,
                name: data.product.name,
                price: data.product.sale_price || 0,
                type: data.product.type || '',
            };
            productsList.push(newProduct);
            refreshAllSelectsOfType('product');

            // Agregar directamente a la tabla de detalles
            addDetailRow('product', newProduct);

            const offcanvasEl = document.getElementById('offcanvasProduct');
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (offcanvas) offcanvas.hide();

            $form[0].reset();
            $('#quick-product-stock-fields').hide();

            SwalToast.fire({ icon: 'success', text: 'Producto creado y agregado a la compra.' });
        } else {
            if (data.errors) {
                $.each(data.errors, function (field, messages) {
                    const $input = $(`#quick-product-${field.replace(/_/g, '-')}`);
                    $input.addClass('is-invalid');
                    $(`#quick-product-${field.replace(/_/g, '-')}-error`).text(messages[0]);
                });
            } else {
                SwalToast.fire({ icon: 'error', text: data.message || 'Error al crear el producto.' });
            }
        }
    } catch (error) {
        console.error('Error en la petición:', error);
        SwalToast.fire({ icon: 'error', text: 'Ocurrió un error inesperado: ' + error.message });
    } finally {
        $submitBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    }
});

$('#offcanvasProduct').on('hidden.bs.offcanvas', function () {
    $('#quick-product-form')[0].reset();
    $('#quick-product-stock-fields').hide();
    $('#quick-product-stock-minimo').prop('required', false);
    $('#quick-product-form').find('.is-invalid').removeClass('is-invalid');
    $('#quick-product-form').find('.invalid-feedback').text('');
    $('#quick-product-sale-price').val('');
});

// Validación en tiempo real para fecha de vencimiento
$(document).on('change', 'input[name$="[expiration_date]"]', function () {
    const today = new Date().toISOString().split('T')[0];
    const expDate = $(this).val();
    const $input = $(this);

    $input.removeClass('is-invalid');
    $input.closest('td').find('.expiration-error').remove();

    if (!expDate) {
        $input.addClass('is-invalid');
        $input.closest('td').append('<div class="text-danger small expiration-error">La fecha de vencimiento es obligatoria.</div>');
    } else if (expDate <= today) {
        $input.addClass('is-invalid');
        $input.closest('td').append('<div class="text-danger small expiration-error">La fecha debe ser mayor al día de hoy.</div>');
    }
});


// --------------------------------------------------------------
// Quick supply creation
// --------------------------------------------------------------

$('#quick-supply-form').on('submit', async function (e) {
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
        const formData = new FormData($form[0]);

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('El servidor no retornó JSON. Asegúrese que SupplyController retorne JSON cuando la petición lo espere.');
        }

        const data = await response.json();

        if (response.ok && (data.success !== false)) {
            const supply = data.supply || data;
            const newSupply = { id: supply.id, name: supply.name, price: 0 };
            suppliesList.push(newSupply);
            refreshAllSelectsOfType('supply');

            // Agregar directamente a la tabla de detalles
            addDetailRow('supply', newSupply);

            const offcanvasEl = document.getElementById('offcanvasSupply');
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (offcanvas) offcanvas.hide();

            $form[0].reset();

            SwalToast.fire({ icon: 'success', text: 'Insumo creado y agregado a la compra.' });
        } else {
            if (data.errors) {
                $.each(data.errors, function (field, messages) {
                    const fieldId = field.replace(/_/g, '-');
                    const $input = $(`#quick-supply-${fieldId}`);
                    $input.addClass('is-invalid');
                    $(`#quick-supply-${fieldId}-error`).text(messages[0]);
                });
            } else {
                SwalToast.fire({ icon: 'error', text: data.message || 'Error al crear el insumo.' });
            }
        }
    } catch (error) {
        console.error('Error en la petición:', error);
        SwalToast.fire({ icon: 'error', text: 'Ocurrió un error inesperado: ' + error.message });
    } finally {
        $submitBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    }
});

// Limpiar offcanvas al cerrar
$('#offcanvasProduct, #offcanvasSupply, #offcanvasSupplier').on('hidden.bs.offcanvas', function () {
    let formId;
    if (this.id === 'offcanvasProduct') formId = '#quick-product-form';
    else if (this.id === 'offcanvasSupply') formId = '#quick-supply-form';
    else formId = '#quick-supplier-form';

    $(formId)[0].reset();
    $(formId).find('.is-invalid').removeClass('is-invalid');
    $(formId).find('.invalid-feedback').text('');
});