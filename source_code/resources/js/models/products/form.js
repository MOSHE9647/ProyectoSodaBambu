// Description: JavaScript code for handling product creation form validation, submission, and UI synchronization.
import {
	clearAllFieldErrors,
	clearFieldError,
	showFieldError,
	validateAndDisplayField,
	validateMultipleOf5,
	validateName,
} from "../../utils/validation.js";
import { generateEan13, setLoadingState } from "../../utils/utils.js";
import { openCategoryModal } from "../purchases/offcanvas/modals/category.js";

// ==================== Environment Checks ====================

// Ensure jQuery is loaded
if (typeof $ === "undefined") {
	throw new Error("This script requires jQuery");
}

// ==================== Constants ====================

const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? "edit-product-form" : "create-product-form";

const PRODUCT_TYPE_MERCHANDISE = "merchandise";
const MERCHANDISE_ONLY_FIELDS = [
	"reference_cost",
	"tax_percentage",
	"margin_percentage",
	"expiration_date",
	"expiration_alert_days",
];
const INVENTORY_FIELDS = ["current_stock", "minimum_stock"];

// ==================== Global Functions ====================

/**
 * Basic specific validations for product fields.
 * You can move these to your validation.js if preferred.
 */
const validateOptionalBarcode = (val) => val === "" || validateName(val);
const validateSelect = (val) => val !== "-1" && val !== "";
const validateInteger = (val) =>
	Number.isInteger(Number(val)) && Number(val) >= 0;

export const baseFieldValidators = {
	name: {
		validator: validateName,
		emptyMsg: "El nombre del producto es obligatorio.",
		invalidMsg: "El nombre no puede exceder 255 caracteres.",
	},
	type: {
		validator: validateSelect,
		emptyMsg: "El tipo de producto es obligatorio.",
		invalidMsg: "Seleccione un tipo de producto válido.",
	},
	category_id: {
		validator: validateSelect,
		emptyMsg: "La categoría es obligatoria.",
		invalidMsg: "Seleccione una categoría válida.",
	},
	sale_price: {
		validator: validateMultipleOf5,
		emptyMsg: "El precio de venta es obligatorio.",
		invalidMsg: "Ingrese un precio válido mayor o igual a 0 que sea múltiplo de 5.",
	},
	reference_cost: {
		validator: validateMultipleOf5,
		emptyMsg: "El costo de referencia es obligatorio para mercadería.",
		invalidMsg: "Ingrese un costo válido mayor o igual a 0 que sea múltiplo de 5.",
	},
	tax_percentage: {
		validator: validateInteger,
		emptyMsg: "El porcentaje de impuesto es obligatorio.",
		invalidMsg: "Ingrese un porcentaje válido.",
	},
	margin_percentage: {
		validator: validateInteger,
		emptyMsg: "El margen de ganancia es obligatorio.",
		invalidMsg: "Ingrese un margen válido.",
	},
	has_inventory: {
		validator: validateSelect,
		emptyMsg: "Debe especificar si controla inventario.",
		invalidMsg: "Selección inválida.",
	},
	current_stock: {
		validator: validateInteger,
		emptyMsg: "El stock actual es obligatorio si controla inventario.",
		invalidMsg: "Ingrese un número entero válido.",
	},
	minimum_stock: {
		validator: validateInteger,
		emptyMsg: "El stock mínimo es obligatorio si controla inventario.",
		invalidMsg: "Ingrese un número entero válido.",
	},
};

// ==================== Helper Functions ====================

/**
 * Creates a filtered copy of fieldValidators based on the current product type and inventory state.
 * @returns {Object}
 */
export function getActiveFieldValidators() {
	const type = $("#type").val();
	const hasInventory = $("#has_inventory").val() === "1";
	const validators = { ...baseFieldValidators };

	// Remove merchandise specific fields if type is not merchandise
	if (type !== PRODUCT_TYPE_MERCHANDISE) {
		MERCHANDISE_ONLY_FIELDS.forEach((field) => delete validators[field]);
	} else {
		// Sale price is auto-calculated for merchandise, no strict validation needed on submission
		delete validators.sale_price;
	}

	// Remove inventory fields if it doesn't track inventory
	if (!hasInventory) {
		INVENTORY_FIELDS.forEach((field) => delete validators[field]);
	}

	return validators;
}

// ==================== UI Manipulation Functions ====================

/**
 * Calculates the alert date for product expiration based on the expiration date and alert days.
 *
 * This function retrieves the expiration date and the number of alert days from the UI,
 * subtracts the alert days from the expiration date, and returns the resulting date formatted
 * in Spanish (es-ES) in a human-readable way.
 *
 * The 'T00:00:00' is appended to the date string to force the local timezone and avoid JavaScript
 * subtracting a day by default due to UTC conversion.
 *
 * @returns {string|null} The formatted alert date in Spanish, or null if no expiration date is set.
 */
const calculateAlertDate = () => {
	const dateString = $("#expiration_date").val();
	const alertDays = parseInt($("#expiration_alert_days").val(), 10) || 0;

	if (dateString) {
		// 'T00:00:00' is added to force the local timezone and avoid JavaScript
		// subtracting a day by default due to UTC conversion
		const expiration = new Date(`${dateString}T00:00:00`);

		// Subtract the alert days
		expiration.setDate(expiration.getDate() - alertDays);

		// Format the date in Spanish and in a human-readable way
		const options = { year: 'numeric', month: 'long', day: 'numeric' };
		const alertDateFormatted = expiration.toLocaleDateString('es-ES', options);

		return alertDateFormatted;
	}

	return null;
}


/**
 * Toggles the 'required' attribute for fields that are only relevant to merchandise products.
 *
 * @param {boolean} isMerchandise - Indicates if the current product type is merchandise.
 * If true, the fields in MERCHANDISE_ONLY_FIELDS will be set as required.
 * If false, the required attribute will be removed and any field errors will be cleared.
 */
const toggleRequiredForMerchandiseFields = (isMerchandise) => {
	MERCHANDISE_ONLY_FIELDS.forEach((field) => {
		const $field = $(`#${field}`);
		$field.prop("required", isMerchandise);
		if (!isMerchandise) clearFieldError(field);
	});
};

/**
 * Toggles the 'required' attribute for fields that are only relevant to inventory products.
 *
 * @param {boolean} hasInventory - Indicates if the current product tracks inventory.
 * If true, the fields in INVENTORY_FIELDS will be set as required.
 * If false, the required attribute will be removed and any field errors will be cleared.
 */
const toggleRequiredForInventoryFields = (hasInventory) => {
    INVENTORY_FIELDS.forEach((field) => {
        const $field = $(`#${field}`);
        $field.prop("required", hasInventory);
        if (!hasInventory) clearFieldError(field);
    });
};

/**
 * Synchronizes the UI to show/hide sections and recalculate values
 * based on selected product type and inventory setting.
 */
export function syncProductUI() {
	const type = $("#type").val();
    const expirationAlertSpan = $("#expiration-alert-date");
	const hasInventory = $("#has_inventory").val() === "1";
	const isMerchandise = type === PRODUCT_TYPE_MERCHANDISE;

	// Toggle Groups (Assuming you have wrappers with these IDs in your Blade form)
	$("#expiration-fields-row, #merchandise-related-row").toggleClass("d-none", !isMerchandise);
	$("#inventory-stock-row").toggleClass("d-none", !hasInventory);

    // Toggle required attributes for merchandise-only and inventory-only fields
    toggleRequiredForMerchandiseFields(isMerchandise);
    toggleRequiredForInventoryFields(hasInventory);

	// Auto-calculate sale price for merchandise
	const $salePrice = $("#sale_price");
    $salePrice.val(""); // Clear the sale price when toggling to avoid confusion
    $salePrice.prop("required", !isMerchandise);
	$salePrice.prop("readonly", isMerchandise);
    $salePrice.prop("disabled", isMerchandise);
    $salePrice.prop("placeholder", isMerchandise ? "Se calcula automáticamente" : "Ej: 4150");

    // If it's merchandise, calculate the sale price based on the cost, tax, and margin
	if (isMerchandise) {
		const cost = parseFloat($("#reference_cost").val()) || 0;
		const tax = parseFloat($("#tax_percentage").val()) || 0;
		const margin = parseFloat($("#margin_percentage").val()) || 0;

		const priceWithTax = cost + cost * (tax / 100);
		const finalPrice = priceWithTax + priceWithTax * (margin / 100);

		// Round to nearest 5 for currency purposes
		const roundedToNearest5 = Math.round(finalPrice / 5) * 5;
		$salePrice.val(roundedToNearest5);
		clearFieldError("sale_price");
	}
}

// ==================== Validation Functions ====================

/**
 * Validates the product form fields.
 * @param {Object} values
 * @param {Object} fieldValidators
 * @returns {boolean}
 */
export function validateProductForm(values, fieldValidators) {
	return validateAndDisplayField(
		fieldValidators,
		values,
		showFieldError,
		clearFieldError,
	);
}

/**
 * Form Submission Handler.
 * Collects values dynamically and validates them.
 * @returns {boolean}
 */
export function submitProductForm(formElement) {
	const fieldValidators = getActiveFieldValidators();
	clearAllFieldErrors(fieldValidators);

	const values = {};

	// Dynamically extract values based on active validators to keep it clean
	Object.keys(fieldValidators).forEach((key) => {
		const $el = $(`#${key}`, formElement);
		values[key] = $el.length ? $el.val().trim() : "";
	});

	// Validate form
	return validateProductForm(values, fieldValidators);
}

// ==================== Event Listeners ====================

/**
 * Initialization function for event binding.
 * Exported so it can be re-called when injecting HTML in an offcanvas.
 */
export function initProductFormEvents(
	targetFormSelector = `form[id$="-product-form"]`,
) {
	// Initial UI sync
	syncProductUI();

	/**
	 * Real-time validation for input fields and UI sync trigger.
	 */
	$(document)
		.off("input change", targetFormSelector)
		.on("input change", targetFormSelector, function (e) {
			const $target = $(e.target);
			const fieldId = $target.attr("id");

			// Sync UI if structural fields or pricing fields change
			if (
				[
					"type",
					"has_inventory",
					"reference_cost",
					"tax_percentage",
					"margin_percentage",
				].includes(fieldId)
			) {
				syncProductUI();
			}

			// For expiration date or alert days, update the alert date display
			if (
				["expiration_date", "expiration_alert_days"].includes(fieldId)
			) {
				const alertDate = calculateAlertDate();
				$("#expiration-alert-date").text(alertDate || "");

				const alertContainer = $("#expiration-alert-date-container");
				alertContainer.toggleClass("d-none", !alertDate);
			}

			const validators = getActiveFieldValidators();

			// Skip if field is not in active validators
			if (!validators.hasOwnProperty(fieldId)) {
				return;
			}

			let value = $target.val().trim();
			const { validator, emptyMsg, invalidMsg } = validators[fieldId];

			if (!value) {
				if (emptyMsg) {
					showFieldError(fieldId, emptyMsg);
				} else {
					clearFieldError(fieldId);
				}
			} else if (!validator(value)) {
				showFieldError(fieldId, invalidMsg);
			} else {
				clearFieldError(fieldId);
			}
		});

	/**
	 * Click event for generating barcode.
	 */
	$(document)
		.off("click", "#generate-barcode-btn")
		.on("click", "#generate-barcode-btn", function (e) {
			const $barcodeField = $("#barcode");
			const generatedBarcode = generateEan13(13, this);
			$barcodeField.val(generatedBarcode);
		});

    // Handle click on "Add Category" button
    $(document)
        .off("click", "#add-category-btn")
        .on("click", "#add-category-btn", function (e) {
            // Open a SweetAlert modal to add a new category
            openCategoryModal(this);
        });
}

/**
 * Form submission event listener.
 */
$(document).on("submit", `form[id$="-product-form"]`, (e) => {
    e.preventDefault();
    
    const formId = $(e.currentTarget).attr("id");
    setLoadingState(formId, true);
    
    if (submitProductForm(e.currentTarget)) {
        if ($(e.currentTarget).closest(".offcanvas").length > 0) return;
        else e.currentTarget.submit();
    }
    else setLoadingState(formId, false);
});

// Auto-initialize if script is loaded traditionally on a page
$(() => {
	if ($(`form[id$="-product-form"]`).length) {
		initProductFormEvents();
	}
});