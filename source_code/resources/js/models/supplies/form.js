import { clearAllFieldErrors, clearFieldError, showFieldError, validateMultipleOf5 } from '../../utils/validation.js';
import { calculateAlertDate, setLoadingState } from '../../utils/utils.js';
import { SwalToast } from "../../utils/sweetalert.js";

// ==================== Environment Checks ====================

if (typeof $ === 'undefined') {
    throw new Error('This script requires jQuery');
}

// ======================== Constants =========================

const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-supply-form' : 'create-supply-form';
const getMeasureUnits = () => window.SUPPLY_FORM_DATA?.measureUnits || [];

// ========================= Helpers ==========================

const getFormValues = () => {
    return {
        name: $('#name').val()?.trim(),
        quantity: $('#quantity').val()?.trim(),
        "measure_unit_selector-input": $('#measure_unit_selector-input').val()?.trim(),
        "measure_unit_selector-value": $('#measure_unit_selector-value').val()?.trim(),
        unit_price: $('#unit_price').val()?.trim(),
        expiration_date: $('#expiration_date').val()?.trim(),
        expiration_alert_days: $('#expiration_alert_days').val()?.trim(),
    };
};

// ==================== Validation Helpers ====================

// Converts a "YYYY-MM-DD" string into a local date at 00:00:00
const getLocalMidnight = (dateString) => {
    if (!dateString) return new Date("Invalid");
    // Separate the string to prevent JS from treating it as UTC
    const [year, month, day] = dateString.split('T')[0].split('-');
    return new Date(year, month - 1, day, 0, 0, 0, 0);
};

// Get today's date at local midnight (00:00:00)
const getTodayMidnight = () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return today;
};

const rules = {
	isNum: (v) => v !== "" && !isNaN(v) && v !== null,
	isString: (v) => typeof v === "string" && v.trim().length > 0,
	isNotEmpty: (v) => v !== "" && v !== null,
	isInList: (v, list, key = "value") => list.some((item) => String(item[key]) === String(v)),
	isValidDate: (v) => !isNaN(Date.parse(v)),
	isTodayOrFutureDate: (v) => getLocalMidnight(v) >= getTodayMidnight(),
};

const baseFieldValidators = {
    name: {
        validate: (v) => rules.isString(v) && v.trim().length <= 50,
        message: 'El nombre del insumo es obligatorio y no puede exceder 50 caracteres.',
    },
    quantity: {
        validate: (v) => rules.isNum(v) && parseInt(v) > 0,
        message: 'La cantidad es obligatoria, debe ser un número entero y no puede ser menor a 0.',
    },
    "measure_unit_selector-input": {
        validate: (v) => rules.isNum(v) && parseFloat(v) > 0,
        message: 'La cantidad del insumo es obligatoria, debe ser numérica y mayor a 0.',
    },
    "measure_unit_selector-value": {
        validate: (v) => rules.isInList(v, getMeasureUnits()),
        message: 'Por favor, seleccione una unidad de medida del desplegable.',
    },
    unit_price: {
        validate: (v) => rules.isNum(v) && parseInt(v) >= 5 && validateMultipleOf5(v),
        message: 'El precio unitario es obligatorio y debe ser un número entero múltiplo de 5.',
    },
    expiration_date: {
        validate: (v) => v === "" || (rules.isValidDate(v) && rules.isTodayOrFutureDate(v)),
        message: 'La fecha de vencimiento debe ser una fecha válida y no puede ser una fecha pasada.',
    },
    expiration_alert_days: {
        validate: (v) => v === "" || (rules.isNum(v) && parseInt(v) >= 0),
        message: 'Los días de alerta deben ser un número entero mayor o igual a 0.',
    }
};

function getActiveFieldValidators() {
    const validators = {...baseFieldValidators};
    return validators;
}

function validateSupplyField(fieldId, value) {
    const validators = getActiveFieldValidators();
    const validator = validators[fieldId];
    if (!validator) return true; // No validation rules for this field

    const isValid = validator.validate(value);
    if (!isValid) {
        if (fieldId === 'measure_unit_selector-input' || fieldId === 'measure_unit_selector-value') {
            const errorFieldId = `${fieldId.substring(0, fieldId.lastIndexOf('-'))}-error`;
            showFieldError(fieldId, validator.message, errorFieldId);
        } else {
            showFieldError(fieldId, validator.message);
        }
    } else {
        if (fieldId === 'measure_unit_selector-input' || fieldId === 'measure_unit_selector-value') {
            const errorFieldId = `${fieldId.substring(0, fieldId.lastIndexOf('-'))}-error`;
            clearFieldError(fieldId, errorFieldId);
        } else {
            clearFieldError(fieldId);
        }
    }
    return isValid;
}

// ===================== Core Functions =======================

export const validateSupplyForm = () => {
    const values = getFormValues();
    const fieldValidators = getActiveFieldValidators();

    let errors = [];
    for (const [fieldId, validator] of Object.entries(fieldValidators)) {
        const value = values[fieldId] !== undefined ? values[fieldId] : '';
        if (!validator.validate(value)) {
            errors.push([false, fieldId, validator.message]);
            if (fieldId === 'measure_unit_selector-input' || fieldId === 'measure_unit_selector-value') {
                const errorFieldId = `${fieldId.substring(0, fieldId.lastIndexOf('-'))}-error`;
                showFieldError(fieldId, validator.message, errorFieldId);
            } else {
                showFieldError(fieldId, validator.message);
            }
        } else {
            if (fieldId === 'measure_unit_selector-input' || fieldId === 'measure_unit_selector-value') {
                const errorFieldId = `${fieldId.substring(0, fieldId.lastIndexOf('-'))}-error`;
                clearFieldError(fieldId, errorFieldId);
            } else {
                clearFieldError(fieldId);
            }
        }
    }

    const validationResult = errors.length > 0 ? errors[0] : [true, null, null];
    
    return [...validationResult, values];
};

export const bindRealTimeValidation = () => {
    const fields = Object.keys(baseFieldValidators);

    fields.forEach((fieldId) => {
        $(`#${fieldId}`).on('input change focusout', function () {
            const value = $(this).val();
            validateSupplyField(fieldId, value);
        });
    });
};

export const bindEventListeners = () => {
    $('#expiration_date').on('change', function () {
        const alertDate = calculateAlertDate();
        $('#expiration-alert-date').text(alertDate || "");
        $("#expiration-alert-date-container").toggleClass("d-none", !alertDate);
    });

    $('#expiration_alert_days').on('input change', function () {
        const alertDate = calculateAlertDate();
        $('#expiration-alert-date').text(alertDate || "");
        $("#expiration-alert-date-container").toggleClass("d-none", !alertDate);
    });
};

const handleFormSubmit = () => {
    // Si el formulario está dentro de un offcanvas (indicado por la variable),
    // no asignamos aquí el submit nativo para evitar colisiones con supplies.js
    if (window.SUPPLY_FORM_DATA?.isOffcanvas) {
        return;
    }

    $(`#${FORM_ID}`).on("submit", async function (e) {
		e.preventDefault();
		setLoadingState(FORM_ID, true);

		// Llamamos a la función modular (Misma estructura que contract-form.js)
		const [isValid, fieldId, message, values] = validateSupplyForm();
		if (!isValid) {
			SwalToast.fire({
				icon: "error",
				title:
					message ||
					"Por favor, corrija los errores en el formulario antes de enviar.",
			});
			setLoadingState(FORM_ID, false);
			return;
		}

		// Validate and submit form
		e.currentTarget.submit();
	});
};

// ====================== Initialization ======================
$(() => {
    bindRealTimeValidation();
    bindEventListeners();
    handleFormSubmit();
});