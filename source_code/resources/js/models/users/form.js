// Description: JavaScript code for handling user creation form validation and submission.
import {
	clearAllFieldErrors,
	clearFieldError,
	showFieldError,
	validateAndDisplayField,
	validateEmail,
	validateEmployeeStatus,
	validateHourlyWage,
	validateName,
	validatePassword,
	validatePasswordConfirmation,
	validatePaymentFrequency,
	validatePhone,
	validateRole
} from '../../utils/validation.js';
import {setLoadingState} from '../../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

// Constants and Variables
const isEdit = document.querySelector('form[id^="edit-"]') !== null;
const formId = isEdit ? 'edit-user-form' : 'create-user-form';
const fieldValidators = {
	name: {
		validator: validateName,
		emptyMsg: 'El nombre es obligatorio.',
		invalidMsg: 'El nombre no puede exceder 255 caracteres.'
	},
	role: {
		validator: validateRole,
		emptyMsg: 'El rol es obligatorio.',
		invalidMsg: 'Seleccione un rol válido.'
	},
	email: {
		validator: validateEmail,
		emptyMsg: 'El correo electrónico es obligatorio.',
		invalidMsg: 'Ingrese un correo electrónico válido.'
	},
	password: {
		validator: validatePassword,
		emptyMsg: isEdit ? '' : 'La contraseña es obligatoria.',
		invalidMsg: 'La contraseña debe contener, al menos, 8 caracteres alfanuméricos.'
	},
	password_confirmation: {
		validator: (value) => {
			const password = $('#password').val().trim();
			return validatePasswordConfirmation(password, value);
		},
		emptyMsg: isEdit ? '' : 'La confirmación de contraseña es obligatoria.',
		invalidMsg: 'Las contraseñas no coinciden.'
	},
	hourly_wage: {
		validator: validateHourlyWage,
		emptyMsg: 'El salario por hora es obligatorio para empleados.',
		invalidMsg: 'Ingrese un salario por hora válido (mayor o igual a 0).'
	},
	payment_frequency: {
		validator: validatePaymentFrequency,
		emptyMsg: 'Es obligatoria la modalidad de pago para empleados.',
		invalidMsg: 'Seleccione una modalidad de pago.'
	},
	phone: {
		validator: validatePhone,
		emptyMsg: 'El número de teléfono es obligatorio.',
		invalidMsg: 'Ingrese un teléfono válido en formato +506 XXXX XXXX.'
	},
	status: {
		validator: validateEmployeeStatus,
		emptyMsg: 'El estado del colaborador es obligatorio.',
		invalidMsg: 'Seleccione el estado del colaborador.'
	}
};

// Validation Functions

/**
 * Validates the user form fields.
 * @param values
 * @returns {boolean}
 */
function validateUserForm(values) {
	// If employee fields are not needed, remove them from validation
	if (values.role !== 'employee') {
		delete fieldValidators.hourly_wage;
		delete fieldValidators.payment_frequency;
		delete fieldValidators.phone;
		delete fieldValidators.status;
	}

	// If editing and password fields are empty, remove them from validation
	if (isEdit && !values.password && !values.password_confirmation) {
		delete fieldValidators.password;
		delete fieldValidators.password_confirmation;
	}

	// Validate common fields
	return validateAndDisplayField(
		fieldValidators,
		values,
		showFieldError,
		clearFieldError
	);
}

// UI Manipulation Functions

/**
 * Form Submission Handler.
 *
 * Handles the user form submission.
 * @returns {boolean}
 */
function submitUserForm() {
	clearAllFieldErrors(fieldValidators);

	// Get form values
	const values = {
		name: $('#name').val().trim(),
		role: $('#role').val(),
		email: $('#email').val().trim(),
		password: $('#password').val().trim(),
		password_confirmation: $('#password_confirmation').val().trim(),
	};

	// Include employee fields if role is employee
	if (values.role === 'employee') {
		values.hourly_wage = $('#hourly_wage').val().trim();
		values.payment_frequency = $('#payment_frequency').val();
		values.phone = $('#phone').val().trim();
		values.status = $('#status').val();
	}

	// Validate form
	// If there are validation errors, do not submit the form
	return validateUserForm(values);
}

// Event Listeners
/**
 * Real-time validation for input fields.
 * Validates fields on input and shows/hides error messages accordingly.
 */
Object.keys(fieldValidators).forEach((fieldId) => {
	$(document).on('input change', `#${fieldId}`, function () {
		const value = $(this).val().trim();
		const {validator, emptyMsg, invalidMsg} = fieldValidators[fieldId];
		const role = $('#role').val();

		// For employee fields, only validate if role is employee
		const isEmployeeField = ['phone', 'hourly_wage', 'payment_frequency', 'status'].includes(fieldId);

		if (isEmployeeField && role !== 'employee') {
			// Clear errors for employee fields if not employee
			clearFieldError(fieldId);
			return;
		}

		if (!value) {
			const shouldShowEmployeeValidation = (isEmployeeField && role === 'employee') || !isEmployeeField;
			if (emptyMsg && shouldShowEmployeeValidation) {
				showFieldError(fieldId, emptyMsg);
			} else {
				clearFieldError(fieldId);
			}
		} else if (!validator(value)) {
			showFieldError(fieldId, invalidMsg);
		} else {
			clearFieldError(fieldId);
		}

		// Special case for password confirmation
		if (fieldId === 'password_confirmation') {
			const password = $('#password').val().trim();
			if (value && password !== value) {
				showFieldError(fieldId, invalidMsg);
			} else {
				clearFieldError(fieldId);
			}
		}
	});
});

/**
 * Form submission event listener.
 * Validates the form and manages the loading state.
 */
$(document).on('submit', `#${formId}`, (e) => {
	// Prevent default form submission
	e.preventDefault();
	setLoadingState(formId, true);
	if (submitUserForm()) e.currentTarget.submit();
	else setLoadingState(formId, false);
});
