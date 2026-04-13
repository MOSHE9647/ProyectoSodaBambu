import {
    clearAllFieldErrors,
    clearFieldError,
    showFieldError,
    validateName
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
    throw new Error('This script requires jQuery');
}

// Constants and Variables
const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-product-form' : 'create-product-form';
const PRODUCT_TYPE_MERCHANDISE = 'merchandise';
const PRODUCT_TYPE_DISH = 'dish';
const PRODUCT_TYPE_DRINK = 'drink';
const PRODUCT_TYPE_PACKAGED = 'packaged';
const MERCHANDISE_ONLY_FIELDS = ['reference_cost', 'tax_percentage', 'margin_percentage', 'expiration_date', 'expiration_alert_days'];
const INVENTORY_FIELDS = ['current_stock', 'minimum_stock'];
const QUICK_CATEGORY_MODAL_ID = 'quick-create-category-modal';
const QUICK_CATEGORY_FORM_ID = 'quick-create-category-form';

function validateOptionalBarcode(value) {
    return value === '' || validateName(value);
}

function validateSelect(value) {
    return value !== '-1' && value !== '';
}

function validateBooleanSelect(value) {
    return value === '1' || value === '0';
}

function validateNonNegativeAmount(value) {
    const amount = parseFloat(value);
    return !isNaN(amount) && amount >= 0;
}

function validateNonNegativeInteger(value) {
    const amount = Number(value);
    return Number.isInteger(amount) && amount >= 0;
}

function validateDateValue(value) {
    if (!value) {
        return false;
    }

    return !Number.isNaN(Date.parse(value));
}

function validateDecimalPercentage(value) {
    const amount = parseFloat(value);
    return !isNaN(amount) && amount >= 0 && amount <= 1;
}

function normalizePercentage(value) {
    const amount = parseFloat(value);

    if (isNaN(amount) || amount < 0) {
        return NaN;
    }

    return amount > 1 ? amount / 100 : amount;
}

function calculateSalePrice(referenceCost, taxPercentage, marginPercentage) {
    const priceWithTax = referenceCost + (referenceCost * taxPercentage);
    return priceWithTax + (priceWithTax * marginPercentage);
}

function isMerchandiseSelected() {
    return ($('#type').val() ?? '').toString().trim() === PRODUCT_TYPE_MERCHANDISE;
}

function requiresManualSalePrice() {
    const currentType = ($('#type').val() ?? '').toString().trim();
    return [PRODUCT_TYPE_DISH, PRODUCT_TYPE_DRINK, PRODUCT_TYPE_PACKAGED].includes(currentType);
}

function hasInventorySelected() {
    return ($('#has_inventory').val() ?? '').toString().trim() === '1';
}

function toggleFieldGroup(groupId, show) {
    const $group = $(`#${groupId}`);

    if (!$group.length) {
        return;
    }

    $group.toggleClass('d-none', !show);
}

function syncPricingFieldBehavior() {
    const isMerchandise = isMerchandiseSelected();
    const manualSalePrice = requiresManualSalePrice();
    const $salePrice = $('#sale_price');
    const $tax = $('#tax_percentage');
    const $referenceCost = $('#reference_cost');
    const $margin = $('#margin_percentage');
    const $expirationDate = $('#expiration_date');
    const $expirationAlertDays = $('#expiration_alert_days');
    let helperMessage = 'Para Platillo, Bebida y Empaquetado el precio de venta es obligatorio.';

    toggleFieldGroup('sale-price-group', true);
    toggleFieldGroup('expiration-fields-row', isMerchandise);
    toggleFieldGroup('tax-percentage-group', isMerchandise);
    toggleFieldGroup('reference-cost-group', isMerchandise);
    toggleFieldGroup('margin-percentage-group', isMerchandise);

    $salePrice.prop('readonly', isMerchandise);
    $salePrice.prop('disabled', false);
    $salePrice.prop('required', manualSalePrice);

    $tax.prop('required', isMerchandise);
    $referenceCost.prop('required', isMerchandise);
    $margin.prop('required', isMerchandise);
    $expirationDate.prop('required', isMerchandise);
    $expirationAlertDays.prop('required', isMerchandise);

    $tax.prop('disabled', !isMerchandise);
    $referenceCost.prop('disabled', !isMerchandise);
    $margin.prop('disabled', !isMerchandise);
    $expirationDate.prop('disabled', !isMerchandise);
    $expirationAlertDays.prop('disabled', !isMerchandise);

    toggleConditionalRequiredMarker('merchandise-tax-required', isMerchandise);
    toggleConditionalRequiredMarker('merchandise-reference-cost-required', isMerchandise);
    toggleConditionalRequiredMarker('merchandise-margin-required', isMerchandise);
    toggleConditionalRequiredMarker('merchandise-expiration-date-required', isMerchandise);
    toggleConditionalRequiredMarker('merchandise-alert-days-required', isMerchandise);
    toggleConditionalRequiredMarker('sale-price-required', manualSalePrice);

    if (isMerchandise) {
        helperMessage = 'Para Mercadería este precio se calcula automáticamente con costo, impuesto y margen.';
    }

    $('#sale-price-help').text(helperMessage);

    const merchandiseHelpClass = isMerchandise ? 'text-muted' : 'text-secondary';
    $('#sale-price-help').removeClass('text-muted text-secondary').addClass(merchandiseHelpClass);

    if (manualSalePrice) {
        $tax.val('');
        $referenceCost.val('');
        $margin.val('');
        $expirationDate.val('');
        $expirationAlertDays.val('7');
        $('#margin-warning').remove();
        return;
    }

    if (!isMerchandise) {
        $expirationDate.val('');
        $expirationAlertDays.val('7');
        $('#margin-warning').remove();
        return;
    }

    const referenceCost = parseFloat($referenceCost.val());
    const taxPercentage = normalizePercentage($tax.val());
    const marginPercentage = normalizePercentage($margin.val());

    if ([referenceCost, taxPercentage, marginPercentage].some((value) => isNaN(value))) {
        $salePrice.val('');
        return;
    }

    const salePrice = calculateSalePrice(referenceCost, taxPercentage, marginPercentage);
    $salePrice.val(salePrice.toFixed(2));
}

function toggleConditionalRequiredMarker(elementId, isVisible) {
    const $element = $(`#${elementId}`);

    if (!$element.length) {
        return;
    }

    $element.toggleClass('d-none', !isVisible);
}

function isFieldRequired(fieldId, values) {
    const isMerchandise = values.type === PRODUCT_TYPE_MERCHANDISE;
    const manualSalePrice = [PRODUCT_TYPE_DISH, PRODUCT_TYPE_DRINK, PRODUCT_TYPE_PACKAGED].includes(values.type);
    const hasInventory = values.has_inventory === '1';

    if (MERCHANDISE_ONLY_FIELDS.includes(fieldId)) {
        return isMerchandise;
    }

    if (fieldId === 'minimum_stock') {
        return hasInventory;
    }

    if (fieldId === 'current_stock') {
        return hasInventory;
    }

    if (fieldId === 'sale_price') {
        return manualSalePrice;
    }

    return true;
}

function validateSalePriceVsCost() {
    const $salePrice = $('#sale_price');
    const $referenceCost = $('#reference_cost');
    const salePrice = parseFloat($salePrice.val());
    const referenceCost = parseFloat($referenceCost.val());

    if (isNaN(salePrice) || isNaN(referenceCost)) {
        return true;
    }

    return salePrice > referenceCost;
}

function validateMarginWarning() {
    if (!isMerchandiseSelected()) {
        $('#margin-warning').remove();
        return;
    }

    const marginPercentage = normalizePercentage($('#margin_percentage').val());

    if (isNaN(marginPercentage)) {
        return;
    }

    const $marginWarning = $('#margin-warning');

    if (marginPercentage < 0.10) {
        if (!$marginWarning.length) {
            $('#margin_percentage').after(
                '<small id="margin-warning" class="text-warning d-block mt-1">Margen bajo menor al 10%. Considere aumentarlo.</small>'
            );
        }
    } else {
        $marginWarning.remove();
    }
}

function syncInventoryFieldBehavior() {
    const hasInventory = hasInventorySelected();

    toggleFieldGroup('inventory-stock-row', hasInventory);

    const $currentStock = $('#current_stock');
    $currentStock.prop('required', hasInventory);
    $currentStock.prop('disabled', !hasInventory);
    $currentStock.prop('readonly', false);

    const $minimumStock = $('#minimum_stock');
    $minimumStock.prop('required', hasInventory);
    $minimumStock.prop('disabled', !hasInventory);

    toggleConditionalRequiredMarker('current-stock-required', hasInventory);
    toggleConditionalRequiredMarker('minimum-stock-required', hasInventory);
}

function getQuickCategoryModal() {
    const modalElement = document.getElementById(QUICK_CATEGORY_MODAL_ID);

    if (!modalElement || !window.bootstrap?.Modal) {
        return null;
    }

    return window.bootstrap.Modal.getOrCreateInstance(modalElement);
}

function showQuickCategoryError(message = '') {
    const $error = $('#quick-category-name-error');
    const $name = $('#quick-category-name');

    if (!message) {
        $error.addClass('d-none').text('');
        $name.removeClass('is-invalid');
        return;
    }

    $error.removeClass('d-none').text(message);
    $name.addClass('is-invalid');
}

function resetQuickCategoryForm() {
    const $form = $(`#${QUICK_CATEGORY_FORM_ID}`);

    if (!$form.length) {
        return;
    }

    $form[0].reset();
    showQuickCategoryError('');
}

function appendOrSelectCategory(category) {
    const $categorySelect = $('#category_id');

    if (!$categorySelect.length || !category || !category.id) {
        return;
    }

    const categoryId = String(category.id);
    const existingOption = $categorySelect.find(`option[value="${categoryId}"]`);

    if (!existingOption.length) {
        $categorySelect.append(`<option value="${categoryId}">${category.name}</option>`);
    }

    $categorySelect.val(categoryId).trigger('change');
    clearFieldError('category_id');
}

async function submitQuickCategoryForm() {
    const $name = $('#quick-category-name');
    const $description = $('#quick-category-description');
    const $submitButton = $('#quick-create-category-submit');
    const name = ($name.val() ?? '').toString().trim();
    const description = ($description.val() ?? '').toString().trim();
    const csrf = typeof csrfToken !== 'undefined' ? csrfToken : '';

    showQuickCategoryError('');

    if (!name) {
        showQuickCategoryError('El nombre de la categoría es obligatorio.');
        return;
    }

    $submitButton.prop('disabled', true);

    try {
        const response = await fetch(route('categories.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                name,
                description: description || null,
            }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const message = data?.errors?.name?.[0] || data?.message || 'No se pudo crear la categoría.';
            showQuickCategoryError(message);
            return;
        }

        appendOrSelectCategory(data.category);
        getQuickCategoryModal()?.hide();
        resetQuickCategoryForm();

        if (window.SwalToast) {
            window.SwalToast.fire({
                icon: window.SwalNotificationTypes.SUCCESS,
                title: data?.message || 'Categoría creada correctamente.',
            });
        }
    } catch (_error) {
        showQuickCategoryError('Ocurrió un error de red al crear la categoría. Inténtelo de nuevo.');
    } finally {
        $submitButton.prop('disabled', false);
    }
}

const fieldValidators = {
    barcode: {
        validator: validateOptionalBarcode,
        emptyMsg: '',
        invalidMsg: 'El código de barras no puede exceder 255 caracteres.'
    },
    name: {
        validator: validateName,
        emptyMsg: 'El nombre del producto es obligatorio.',
        invalidMsg: 'El nombre no puede exceder 255 caracteres.'
    },
    type: {
        validator: validateSelect,
        emptyMsg: 'El tipo de producto es obligatorio.',
        invalidMsg: 'Seleccione un tipo de producto válido.'
    },
    has_inventory: {
        validator: validateBooleanSelect,
        emptyMsg: 'Indique si maneja inventario.',
        invalidMsg: 'Seleccione una opción válida para inventario.'
    },
    expiration_date: {
        validator: validateDateValue,
        emptyMsg: 'La fecha de vencimiento es obligatoria para productos de mercadería.',
        invalidMsg: 'Ingrese una fecha de vencimiento válida.'
    },
    expiration_alert_days: {
        validator: validateNonNegativeInteger,
        emptyMsg: 'Los días de alerta de vencimiento son obligatorios.',
        invalidMsg: 'Ingrese un número entero mayor o igual a 0.'
    },
    sale_price: {
        validator: (value) => {
            if (!validateNonNegativeAmount(value)) return false;
            if (isMerchandiseSelected()) return true;
            return validateSalePriceVsCost();
        },
        emptyMsg: 'El precio de venta es obligatorio para Platillo, Bebida y Empaquetado.',
        invalidMsg: 'Ingrese un precio de venta mayor al costo de referencia.'
    },
    tax_percentage: {
        validator: validateDecimalPercentage,
        emptyMsg: 'El impuesto es obligatorio.',
        invalidMsg: 'Ingrese un impuesto válido entre 0 y 1. Ej: 0.13'
    },
    reference_cost: {
        validator: validateNonNegativeAmount,
        emptyMsg: 'El costo de referencia es obligatorio.',
        invalidMsg: 'Ingrese un costo de referencia válido mayor o igual a 0.'
    },
    margin_percentage: {
        validator: validateDecimalPercentage,
        emptyMsg: 'El margen es obligatorio.',
        invalidMsg: 'Ingrese un margen válido entre 0 y 1. Ej: 0.35'
    },
    current_stock: {
        validator: (value) => Number.isInteger(Number(value)) && Number(value) >= 0,
        emptyMsg: 'El stock actual es obligatorio cuando maneja inventario.',
        invalidMsg: 'Ingrese un stock actual entero mayor o igual a 0.'
    },
    minimum_stock: {
        validator: (value) => Number.isInteger(Number(value)) && Number(value) >= 0,
        emptyMsg: 'El stock mínimo es obligatorio cuando maneja inventario.',
        invalidMsg: 'Ingrese un stock mínimo entero mayor o igual a 0.'
    },
    category_id: {
        validator: validateSelect,
        emptyMsg: 'La categoría es obligatoria.',
        invalidMsg: 'Seleccione una categoría válida.'
    }
};

/**
 * Validates product form fields.
 * @param {Object} values
 * @returns {boolean}
 */
function validateProductForm(values) {
    let isValid = true;

    Object.keys(fieldValidators).forEach((fieldId) => {
        const { validator, emptyMsg, invalidMsg } = fieldValidators[fieldId];
        const value = (values[fieldId] || '').trim();
        const required = isFieldRequired(fieldId, values);

        if (!required && !value) {
            clearFieldError(fieldId);
            return;
        }

        if (!value) {
            if (fieldId === 'barcode') {
                clearFieldError(fieldId);
                return;
            }

            showFieldError(fieldId, emptyMsg);
            isValid = false;
            return;
        }

        if (!validator(value)) {
            showFieldError(fieldId, invalidMsg);
            isValid = false;
            return;
        }

        clearFieldError(fieldId);
    });

    return isValid;
}

/**
 * Handles the form submission process.
 * @returns {boolean}
 */
function submitProductForm() {
    clearAllFieldErrors(fieldValidators);
    syncPricingFieldBehavior();
    syncInventoryFieldBehavior();

    const values = {
        barcode: $('#barcode').val().trim(),
        name: $('#name').val().trim(),
        type: $('#type').val().trim(),
        has_inventory: $('#has_inventory').val().trim(),
        expiration_date: ($('#expiration_date').val() ?? '').toString().trim(),
        expiration_alert_days: $('#expiration_alert_days').val().trim(),
        sale_price: $('#sale_price').val().trim(),
        tax_percentage: $('#tax_percentage').val().trim(),
        reference_cost: $('#reference_cost').val().trim(),
        margin_percentage: $('#margin_percentage').val().trim(),
        current_stock: ($('#current_stock').val() ?? '').toString().trim(),
        minimum_stock: $('#minimum_stock').val().trim(),
        category_id: $('#category_id').val().trim()
    };

    return validateProductForm(values);
}

/**
 * Real-time validation for product form fields.
 */
$(document).on('input change', `#${FORM_ID}`, function (e) {
    syncPricingFieldBehavior();
    syncInventoryFieldBehavior();
    validateMarginWarning();

    const $target = $(e.target);
    const fieldId = $target.attr('id');

    if (!Object.prototype.hasOwnProperty.call(fieldValidators, fieldId)) {
        return;
    }

    if (fieldId === 'sale_price' && isMerchandiseSelected()) {
        clearFieldError(fieldId);
        return;
    }

    const value = ($target.val() ?? '').toString().trim();
    const { validator, emptyMsg, invalidMsg } = fieldValidators[fieldId];

    if (!isMerchandiseSelected() && MERCHANDISE_ONLY_FIELDS.includes(fieldId) && !value) {
        clearFieldError(fieldId);
        return;
    }

    if (!hasInventorySelected() && INVENTORY_FIELDS.includes(fieldId)) {
        clearFieldError(fieldId);
        return;
    }

    if (!value) {
		if (fieldId === 'barcode') {
			clearFieldError(fieldId);
			return;
		}
        showFieldError(fieldId, emptyMsg);
    } else if (!validator(value)) {
        showFieldError(fieldId, invalidMsg);
    } else {
        clearFieldError(fieldId);
    }
});

/**
 * Handles product form submission.
 */
$(document).on('submit', `#${FORM_ID}`, (e) => {
    e.preventDefault();
    setLoadingState(FORM_ID, true);

    if (submitProductForm()) e.currentTarget.submit();
    else setLoadingState(FORM_ID, false);
});

$(document).on('click', '#open-create-category-modal', () => {
    resetQuickCategoryForm();
});

$(document).on('submit', `#${QUICK_CATEGORY_FORM_ID}`, async (e) => {
    e.preventDefault();
    await submitQuickCategoryForm();
});

$(document).ready(() => {
    syncPricingFieldBehavior();
    syncInventoryFieldBehavior();
    validateMarginWarning();
});
