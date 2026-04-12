import {
    clearAllFieldErrors,
    showFieldError,
    clearFieldError,
    validateAndDisplayField
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';
import { fetchWithErrorHandling } from '../../utils/error-handling.js';
import { SwalNotificationTypes, SwalToast } from '../../utils/sweetalert.js';

let detailIndex  = window.detailIndex || 0;
let productsList = window.products    || [];
let suppliesList = window.supplies    || [];

const TYPE_LABELS = {
    product: 'Producto',
    supply:  'Insumo',
};

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

/** Formatea un número como moneda costarricense */
function formatCRC(value) {
    return '₡' + parseFloat(value || 0).toLocaleString('es-CR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

/**
 * Recalcula el subtotal de una fila (cantidad × precio unitario),
 * actualiza su display y el hidden input de subtotal.
 */
function recalcRowSubtotal($row) {
    const qty       = parseFloat($row.find('.quantity-input').val())  || 0;
    const unitPrice = parseFloat($row.find('.unit-price-input').val()) || 0;
    const subtotal  = qty * unitPrice;

    $row.find('.subtotal-display').text(formatCRC(subtotal));
    $row.find('.subtotal-input').val(subtotal.toFixed(2));
}

/**
 * Recalcula el total de la compra sumando los subtotales de todas las filas.
 * Actualiza el hidden #total y el display #total-display.
 */
function recalcTotal() {
    let total = 0;
    $('#details-container .detail-row').each(function () {
        total += parseFloat($(this).find('.subtotal-input').val()) || 0;
    });
    $('#total').val(total.toFixed(2));
    $('#total-display').text(formatCRC(total));
}

/**
 * Poblar el select de producto/insumo según el tipo seleccionado.
 * Devuelve el jQuery del select para encadenamiento.
 */
function populateSelect(selectElement, type) {
    const list = type === 'product' ? productsList : suppliesList;
    // Marcar para suprimir el listener change durante la repoblación
    selectElement.data('populating', true);
    selectElement.empty().append('<option value="">Seleccionar</option>');
    list.forEach(item => {
        selectElement.append(`<option value="${item.id}">${item.name}</option>`);
    });
    selectElement.data('populating', false);
    return selectElement;
}

/**
 * FIX #2: Aplica el unit_price del item seleccionado a la fila.
 * Se llama tanto al cambiar el select de tipo como al cambiar el select de item.
 */
function applyUnitPrice($row) {
    const type = $row.find('.purchasable-type').val();
    const idVal = $row.find('.purchasable-id').val();

    // Si no hay item seleccionado, no hacer nada
    if (!idVal) return;

    const id   = parseInt(idVal, 10);
    const list = type === 'product' ? productsList : suppliesList;
    const item = list.find(i => i.id === id);

    const price = (item && item.unit_price != null) ? item.unit_price : 0;
    $row.find('.unit-price-input').val(price.toFixed(2));
    recalcRowSubtotal($row);
    recalcTotal();
}

/**
 * FIX #1: Refresca todos los selects del tipo dado y conserva la selección actual.
 * Se usa cuando se crea un producto/insumo desde el offcanvas.
 */
function refreshAllSelectsOfType(type) {
    $('.detail-row').each(function () {
        if ($(this).find('.purchasable-type').val() === type) {
            const select  = $(this).find('.purchasable-id');
            const current = select.val();
            populateSelect(select, type);
            select.val(current);
        }
    });
}

function toggleEmptyRow() {
    const hasRows = $('#details-container .detail-row').length > 0;
    $('#empty-details-row').toggle(!hasRows);
}

function buildTypeSelect(name, selectedValue = 'product') {
    const options = Object.entries(TYPE_LABELS)
        .map(([val, label]) => `<option value="${val}" ${val === selectedValue ? 'selected' : ''}>${label}</option>`)
        .join('');
    return `<select name="${name}" class="form-select form-select-sm purchasable-type" required>${options}</select>`;
}

/**
 * FIX #1: Agrega una nueva fila a la tabla preseleccionando el item recién creado.
 * type = 'product' | 'supply'
 * item = { id, name, unit_price }
 */
function addDetailRowForNewItem(type, item) {
    const index = detailIndex++;

    const template = `
        <tr class="detail-row" data-index="${index}">
            <td>${buildTypeSelect(`details[${index}][purchasable_type]`, type)}</td>
            <td>
                <select name="details[${index}][purchasable_id]" class="form-select form-select-sm purchasable-id" required>
                    <option value="">Seleccionar</option>
                </select>
            </td>
            <td>
                <input type="number" name="details[${index}][quantity]"
                       class="form-control form-control-sm quantity-input"
                       value="1" min="0.0001" step="0.0001" required>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">₡</span>
                    <input type="number" name="details[${index}][unit_price]"
                           class="form-control form-control-sm unit-price-input"
                           value="${item.unit_price ? item.unit_price.toFixed(2) : '0.00'}"
                           min="0" step="0.01" required>
                </div>
            </td>
            <td class="align-middle">
                <span class="subtotal-display fw-semibold text-success">₡0.00</span>
                <input type="hidden" name="details[${index}][subtotal]" class="subtotal-input" value="0">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-detail">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#details-container').append(template);
    const $newRow = $('#details-container tr').last();

    // Poblar el select con todos los items del tipo y preseleccionar el recién creado
    populateSelect($newRow.find('.purchasable-id'), type).val(item.id);

    // Calcular subtotal inicial (qty=1 × unit_price)
    recalcRowSubtotal($newRow);
    toggleEmptyRow();
    recalcTotal();
}

// ─────────────────────────────────────────────
//  Tabla de detalles — botón "Agregar"
// ─────────────────────────────────────────────

$('#add-detail').on('click', function () {
    const index = detailIndex++;

    const template = `
        <tr class="detail-row" data-index="${index}">
            <td>${buildTypeSelect(`details[${index}][purchasable_type]`)}</td>
            <td>
                <select name="details[${index}][purchasable_id]" class="form-select form-select-sm purchasable-id" required>
                    <option value="">Seleccionar</option>
                </select>
            </td>
            <td>
                <input type="number" name="details[${index}][quantity]"
                       class="form-control form-control-sm quantity-input"
                       value="1" min="0.0001" step="0.0001" required>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">₡</span>
                    <input type="number" name="details[${index}][unit_price]"
                           class="form-control form-control-sm unit-price-input"
                           value="0.00" min="0" step="0.01" required>
                </div>
            </td>
            <td class="align-middle">
                <span class="subtotal-display fw-semibold text-success">₡0.00</span>
                <input type="hidden" name="details[${index}][subtotal]" class="subtotal-input" value="0">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-detail">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#details-container').append(template);
    const $newRow = $('#details-container tr').last();
    populateSelect($newRow.find('.purchasable-id'), 'product');
    toggleEmptyRow();
    recalcTotal();
});

$(document).on('click', '.remove-detail', function () {
    $(this).closest('tr').remove();
    toggleEmptyRow();
    recalcTotal();
});

// FIX #2: Al cambiar el tipo (producto/insumo), repoblar el select y resetear precio
$(document).on('change', '.purchasable-type', function () {
    const $row = $(this).closest('tr');
    populateSelect($row.find('.purchasable-id'), $(this).val());
    // Resetear precio al cambiar tipo ya que no hay item seleccionado
    $row.find('.unit-price-input').val('0.00');
    recalcRowSubtotal($row);
    recalcTotal();
});

// FIX #2: Al seleccionar un item del select, autocompletar el precio unitario
// Usamos un flag para ignorar el change disparado por populateSelect(.empty())
$(document).on('change', '.purchasable-id', function () {
    if ($(this).data('populating')) return;
    applyUnitPrice($(this).closest('tr'));
});

// Recalcular subtotal de fila y total global al editar cantidad o precio unitario
$(document).on('input change', '.quantity-input, .unit-price-input', function () {
    recalcRowSubtotal($(this).closest('tr'));
    recalcTotal();
});

// ─────────────────────────────────────────────
//  Cálculo de precio de venta (producto rápido)
// ─────────────────────────────────────────────

function calcSalePrice() {
    const cost   = parseFloat($('#quick-product-reference-cost').val()) || 0;
    const tax    = parseFloat($('#quick-product-tax-percentage').val())  || 0;
    const margin = parseFloat($('#quick-product-margin-percentage').val()) || 0;
    if (cost > 0) {
        // tax y margin vienen como decimales (ej: 0.13), no como porcentaje
        $('#quick-product-sale-price').val((cost * (1 + tax) * (1 + margin)).toFixed(2));
    }
}

$('#quick-product-reference-cost, #quick-product-tax-percentage, #quick-product-margin-percentage')
    .on('input change', calcSalePrice);

// ─────────────────────────────────────────────
//  Validación y envío del formulario principal
// ─────────────────────────────────────────────

function submitPurchaseForm() {
    if (!$('#invoice_number').val().trim()) {
        SwalToast.fire({ icon: 'error', text: 'El número de factura es obligatorio.' });
        return false;
    }
    if (!$('#date').val()) {
        SwalToast.fire({ icon: 'error', text: 'La fecha es obligatoria.' });
        return false;
    }
    if (!$('#supplier_id').val()) {
        SwalToast.fire({ icon: 'error', text: 'Debe seleccionar un proveedor.' });
        return false;
    }
    if (!$('#payment_status').val()) {
        SwalToast.fire({ icon: 'error', text: 'Debe seleccionar un estado de pago.' });
        return false;
    }
    if ($('#details-container .detail-row').length === 0) {
        SwalToast.fire({ icon: 'error', text: 'Debe agregar al menos un producto/insumo.' });
        return false;
    }

    let valid = true;
    $('#details-container .detail-row').each(function () {
        const $purchasableId = $(this).find('.purchasable-id');
        const $qty           = $(this).find('.quantity-input');
        const $price         = $(this).find('.unit-price-input');

        if (!$purchasableId.val()) {
            $purchasableId.addClass('is-invalid');
            valid = false;
        } else {
            $purchasableId.removeClass('is-invalid');
        }

        if (!$qty.val() || parseFloat($qty.val()) <= 0) {
            $qty.addClass('is-invalid');
            valid = false;
        } else {
            $qty.removeClass('is-invalid');
        }

        if ($price.val() === '' || parseFloat($price.val()) < 0) {
            $price.addClass('is-invalid');
            valid = false;
        } else {
            $price.removeClass('is-invalid');
        }
    });

    if (valid) recalcTotal();

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

// ─────────────────────────────────────────────
//  Quick supplier
// ─────────────────────────────────────────────

$('#quick-phone').on('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 8);
});

function clearSupplierErrors() {
    ['name', 'phone', 'email'].forEach(field => {
        $(`#quick-${field}`).removeClass('is-invalid');
        $(`#quick-${field}-error`).addClass('d-none').text('');
    });
}

$(document).on('submit', '#quick-supplier-form', async function (e) {
    e.preventDefault();

    const $form      = $(this);
    const $submitBtn = $('#quick-supplier-submit');
    const $spinner   = $('#quick-supplier-spinner');
    const url        = $form.attr('action');

    clearSupplierErrors();
    $submitBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

    try {
        const response = await fetch(url, {
            method:  'POST',
            body:    new FormData($form[0]),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
            },
        });

        const data = await response.json();

        if (response.ok && data.success) {
            const $select = $('#supplier_id');
            $select.append(`<option value="${data.supplier.id}">${data.supplier.name}</option>`);
            $select.val(data.supplier.id);

            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasSupplier'));
            if (offcanvas) offcanvas.hide();
            $form[0].reset();

            SwalToast.fire({ icon: 'success', text: data.message || 'Proveedor creado correctamente.' });
        } else {
            if (data.errors) {
                Object.entries(data.errors).forEach(([field, messages]) => {
                    $(`#quick-${field}`).addClass('is-invalid');
                    $(`#quick-${field}-error`).removeClass('d-none').text(messages[0]);
                });
            } else {
                SwalToast.fire({ icon: 'error', text: data.message || 'Error al crear el proveedor.' });
            }
        }
    } catch (error) {
        console.error('Error creando proveedor:', error);
        SwalToast.fire({ icon: 'error', text: 'Ocurrió un error inesperado.' });
    } finally {
        $submitBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    }
});

// ─────────────────────────────────────────────
//  Quick product
// ─────────────────────────────────────────────

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
    const $cat = $('#quick-product-category');
    $cat.prop('disabled', true).empty().append('<option value="">Cargando categorías...</option>');

    $.ajax({
        url:      window.categoriesIndexUrl,
        method:   'GET',
        data:     { simple: 1 },
        dataType: 'json',
        headers:  { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        success:  (data) => {
            $cat.empty().append('<option value="">Seleccionar categoría</option>');
            data.forEach(cat => $cat.append(`<option value="${cat.id}">${cat.name}</option>`));
        },
        error: (xhr) => {
            console.error('Error cargando categorías:', xhr.status, xhr.responseText);
            $cat.empty().append('<option value="">Error al cargar</option>');
            SwalToast.fire({ icon: 'error', text: 'No se pudieron cargar las categorías.' });
        },
        complete: () => $cat.prop('disabled', false),
    });
}

document.addEventListener('show.bs.offcanvas', (e) => {
    if (e.target?.id === 'offcanvasProduct') loadCategories();
});

$('#quick-product-form').on('submit', async function (e) {
    e.preventDefault();

    const $form      = $(this);
    const $submitBtn = $('#quick-product-submit');
    const $spinner   = $('#quick-product-spinner');

    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').text('');

    if ($('#quick-product-has-inventory').is(':checked')) {
        const stockMin = $('#quick-product-stock-minimo').val();
        if (stockMin === '' || stockMin < 0) {
            $('#quick-product-stock-minimo').addClass('is-invalid');
            $('#quick-product-stock-minimo-error').text('El stock mínimo es obligatorio y debe ser ≥ 0.');
            return;
        }
    }

    $submitBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

    try {
        const formData = new FormData($form[0]);
        if (!formData.has('has_inventory'))    formData.set('has_inventory', '0');
        if (!formData.has('barcode'))           formData.set('barcode', '');
        if (!formData.get('reference_cost'))    formData.set('reference_cost', '0');
        if (!formData.get('tax_percentage'))    formData.set('tax_percentage', '0');
        if (!formData.get('margin_percentage')) formData.set('margin_percentage', '0');
        if (!formData.get('sale_price'))        formData.set('sale_price', '0');
        formData.delete('stock_actual');

        const response = await fetch($form.attr('action'), {
            method:  'POST',
            body:    formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });

        const data = await response.json();

        if (data.success) {
            // FIX #1 + #2: incluir unit_price (reference_cost) para el autocomplete
            const newItem = {
                id:         data.product.id,
                name:       data.product.name,
                type:       data.product.type || '',
                unit_price: parseFloat(data.product.reference_cost ?? 0),
            };
            productsList.push(newItem);
            refreshAllSelectsOfType('product');

            // FIX #1: agregar automáticamente a la tabla de detalles
            addDetailRowForNewItem('product', newItem);

            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasProduct'));
            if (offcanvas) offcanvas.hide();

            $form[0].reset();
            $('#quick-product-stock-fields').hide();
            SwalToast.fire({ icon: 'success', text: 'Producto creado correctamente.' });
        } else {
            if (data.errors) {
                Object.entries(data.errors).forEach(([field, messages]) => {
                    const key = field.replace(/_/g, '-');
                    $(`#quick-product-${key}`).addClass('is-invalid');
                    $(`#quick-product-${key}-error`).text(messages[0]);
                });
            } else {
                SwalToast.fire({ icon: 'error', text: data.message || 'Error al crear el producto.' });
            }
        }
    } catch (err) {
        console.error(err);
        SwalToast.fire({ icon: 'error', text: 'Ocurrió un error inesperado.' });
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

// ─────────────────────────────────────────────
//  Quick supply
// ─────────────────────────────────────────────

$('#quick-supply-form').on('submit', async function (e) {
    e.preventDefault();

    const $form      = $(this);
    const $submitBtn = $('#quick-supply-submit');
    const $spinner   = $('#quick-supply-spinner');

    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').text('');

    $submitBtn.prop('disabled', true);
    $spinner.removeClass('d-none');

    try {
        const formData = new FormData($form[0]);
        if (!formData.get('quantity'))              formData.set('quantity', '0');
        if (!formData.get('unit_price'))            formData.set('unit_price', '0');
        if (!formData.get('expiration_alert_days')) formData.set('expiration_alert_days', '7');

        const response = await fetch($form.attr('action'), {
            method:  'POST',
            body:    formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });

        const data = await response.json();

        if (data.success) {
            // FIX #1 + #2: incluir unit_price para el autocomplete
            const newItem = {
                id:         data.supply.id,
                name:       data.supply.name,
                unit_price: parseFloat(data.supply.unit_price ?? 0),
            };
            suppliesList.push(newItem);
            refreshAllSelectsOfType('supply');

            // FIX #1: agregar automáticamente a la tabla de detalles
            addDetailRowForNewItem('supply', newItem);

            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasSupply'));
            if (offcanvas) offcanvas.hide();

            $form[0].reset();
            SwalToast.fire({ icon: 'success', text: 'Insumo creado correctamente.' });
        } else {
            if (data.errors) {
                Object.entries(data.errors).forEach(([field, messages]) => {
                    const key = field.replace(/_/g, '-');
                    $(`#quick-supply-${key}`).addClass('is-invalid');
                    $(`#quick-supply-${key}-error`).text(messages[0]);
                });
            } else {
                SwalToast.fire({ icon: 'error', text: data.message || 'Error al crear el insumo.' });
            }
        }
    } catch (err) {
        console.error(err);
        SwalToast.fire({ icon: 'error', text: 'Ocurrió un error inesperado.' });
    } finally {
        $submitBtn.prop('disabled', false);
        $spinner.addClass('d-none');
    }
});

// Limpiar offcanvas al cerrar
$('#offcanvasProduct, #offcanvasSupply, #offcanvasSupplier').on('hidden.bs.offcanvas', function () {
    let formId;
    if      (this.id === 'offcanvasProduct')  formId = '#quick-product-form';
    else if (this.id === 'offcanvasSupply')   formId = '#quick-supply-form';
    else                                       formId = '#quick-supplier-form';

    $(formId)[0].reset();
    $(formId).find('.is-invalid').removeClass('is-invalid');
    $(formId).find('.invalid-feedback').text('');
});

// ─────────────────────────────────────────────
//  Init: calcular total en modo edición
// ─────────────────────────────────────────────
$(function () {
    $('#details-container .detail-row').each(function () {
        recalcRowSubtotal($(this));
    });
    recalcTotal();
});