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
	validateRole,
	formatPhoneNumber
} from '../../utils/validation.js';
import { setLoadingState, togglePasswordVisibility } from '../../utils/utils.js';

// ==================== Environment Checks ====================

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

// ==================== Constants ====================

const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-user-form' : 'create-user-form';
const EMPLOYEE_ROLE = 'employee';
const EMPLOYEE_FIELDS = ['phone', 'hourly_wage', 'payment_frequency', 'status'];

// ==================== Global Functions ====================

window.togglePasswordVisibility = togglePasswordVisibility;

const baseFieldValidators = {
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
		emptyMsg: IS_EDITING ? '' : 'La contraseña es obligatoria.',
		invalidMsg: 'La contraseña debe contener, al menos, 8 caracteres alfanuméricos.'
	},
	password_confirmation: {
		validator: (value) => {
			const password = $('#password').val().trim();
			return validatePasswordConfirmation(password, value);
		},
		emptyMsg: IS_EDITING ? '' : 'La confirmación de contraseña es obligatoria.',
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

// ==================== Helper Functions ====================

/**
 * Creates a filtered copy of fieldValidators based on form type and state
 * @returns {Object}
 */
function getActiveFieldValidators() {
	const $role = $('#role');
	const role = $role.val();
	const validators = {...baseFieldValidators};

	// Remove employee fields if role is not employee
	if (role !== EMPLOYEE_ROLE) {
		EMPLOYEE_FIELDS.forEach(field => delete validators[field]);
	}

	// Remove password validation if editing and both password fields are empty
	if (IS_EDITING && !$('#password').val() && !$('#password_confirmation').val()) {
		delete validators.password;
		delete validators.password_confirmation;
	}

	return validators;
}
// ==================== Validation Functions ====================

/**
 * Validates the user form fields.
 * @param values
 * @param fieldValidators
 * @returns {boolean}
 */
function validateUserForm(values, fieldValidators) {
	return validateAndDisplayField(
		fieldValidators,
		values,
		showFieldError,
		clearFieldError
	);
}

// ==================== UI Manipulation Functions ====================

/**
 * Form Submission Handler.
 *
 * Handles the user form submission.
 * @returns {boolean}
 */
function submitUserForm() {
	const fieldValidators = getActiveFieldValidators();
	clearAllFieldErrors(fieldValidators);

	// Cache DOM elements
	const $name = $('#name');
	const $role = $('#role');
	const $email = $('#email');
	const $password = $('#password');
	const $passwordConfirmation = $('#password_confirmation');

	// Get form values
	const values = {
		name: $name.val().trim(),
		role: $role.val(),
		email: $email.val().trim(),
		password: $password.val().trim(),
		password_confirmation: $passwordConfirmation.val().trim(),
	};

	// Include employee fields if role is employee
	if (values.role === EMPLOYEE_ROLE) {
		values.hourly_wage = $('#hourly_wage').val().trim();
		values.payment_frequency = $('#payment_frequency').val();
		values.phone = $('#phone').val().trim();
		values.status = $('#status').val();
	}

	// Validate form
	return validateUserForm(values, fieldValidators);
}

// ==================== Event Listeners ====================

/**
 * Real-time validation for input fields.
 * Validates fields on input and shows/hides error messages accordingly.
 */
$(document).on('input change', `#${FORM_ID}`, function(e) {
	const $target = $(e.target);
	const fieldId = $target.attr('id');
	const validators = getActiveFieldValidators();

	// Skip if field is not in validators
	if (!validators.hasOwnProperty(fieldId)) {
		return;
	}

	let value = $target.val().trim();
	const {validator, emptyMsg, invalidMsg} = validators[fieldId];
	const isEmployeeField = EMPLOYEE_FIELDS.includes(fieldId);
	const $role = $('#role');
	const role = $role.val();

	// Format phone number in real-time
	if (fieldId === 'phone') {
		value = formatPhoneNumber(value);
		$target.val(value);
	}

	// For employee fields, only validate if role is employee
	if (isEmployeeField && role !== EMPLOYEE_ROLE) {
		clearFieldError(fieldId);
		return;
	}

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
 * Form submission event listener.
 * Validates the form and manages the loading state.
 */
$(document).on('submit', `#${FORM_ID}`, (e) => {
	// Prevent default form submission
	e.preventDefault();
	setLoadingState(FORM_ID, true);

	// Validate and submit form
	if (submitUserForm()) e.currentTarget.submit();
	else setLoadingState(FORM_ID, false);
});
