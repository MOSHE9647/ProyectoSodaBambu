import '../config/datatables.js';
import { escapeHtml } from "./utils.js";
import { SwalToast } from "./sweetalert.js";

/**
 * Action button configuration templates
 * @type {Object<string, {tooltip: string, icon: string, buttonClass: string, extraAttrs?: string}>}
 */
const ACTION_CONFIG = {
	show: { tooltip: 'Ver Más', icon: 'bi-info-circle', buttonClass: 'btn-info' },
	edit: { tooltip: 'Editar', icon: 'bi-pencil-square', buttonClass: 'btn-primary' },
	delete: { tooltip: 'Eliminar', icon: 'bi-trash', buttonClass: 'btn-danger', extraAttrs: 'class="delete-form"' }
};
const ACTION_TYPES = Object.keys(ACTION_CONFIG); // ['show', 'edit', 'delete']

/**
 * Initialize a new DataTable on the specified table element with given options.
 * Uses DataTables defaults which can be overridden by the options' parameter.
 *
 * @param tableId - The ID of the table element.
 * @param options - Additional DataTable options to override defaults.
 * @returns {*} The initialized DataTable instance or void if table not found.
 */
function InitNewDataTable(tableId, options = {}) {
	const table = $(`#${tableId}`);
	if (!table.length) return console.log(`No table found with ID: ${tableId}`);

	// Initialize DataTable
	const dataTable = table.DataTable(options);

	// Custom search input
	const searchBox = $('#customSearchBox');
	if (!searchBox.length) console.warn("Custom search box not found: #customSearchBox");

	// Link custom search box to DataTable search
	searchBox.on('keyup', function () {
		dataTable.search(this.value).draw();
	});

	// Return the initialized DataTable instance
	return dataTable;
}

/**
 * Initialize a New DataTable with standard actions (show, edit, delete) and custom buttons.
 * @param {string} tableId - The ID of the table element.
 * @param {string} ajaxUrl - The URL for server-side data.
 * @param {Array} columns - Array of column definitions.
 * @param {Object} actions - Object defining action routes, functions, and disable conditions.
 *   - show: { route: string, func: function, disabledIf?: function(row), disabledIfTooltip?: string, tooltip?: string }
 *   - edit: { route: string, disabledIf?: function(row), disabledIfTooltip?: string, tooltip?: string }
 *   - delete: { route: string, disabledIf?: function(row), disabledIfTooltip?: string, tooltip?: string }
 * @param {Array<Object>} customButtons - Array of custom button objects { text, href, class, icon, func?, params? }.
 * @param {Object} options - Additional DataTable options to override defaults.
 * @returns {*} The initialized DataTable instance.
 */
export function CreateNewDataTable(tableId, ajaxUrl, columns, actions, customButtons = [], options = {}) {
	// Validate required parameters
	if (!tableId || !ajaxUrl || !Array.isArray(columns)) {
		console.error('CreateNewDataTable: Missing required parameters', { tableId, ajaxUrl, columns });
		return null;
	}

	// Add actions column if actions are provided
	if (actions && Object.keys(actions).length > 0) {
		columns.push(buildActionsColumn(actions));
	}

	// Build custom top-right buttons HTML container
	const buttonsHtml = buildCustomButtonsContainer(customButtons);

	// Create DataTable options
	const mergedOptions = buildTableOptions(tableId, ajaxUrl, columns, buttonsHtml, options);

	// Initialize and return the DataTable
	return InitNewDataTable(tableId, mergedOptions);
}

/**
 * Build the actions column definition for DataTable.
 * @private
 */
function buildActionsColumn(actions) {
	return {
		data: null,
		name: 'actions',
		orderable: false,
		searchable: false,
		width: '12%',
		render: (_data, _type, row) => renderActionButtons(row, actions)
	};
}

/**
 * Render action buttons for a single row.
 * @private
 */
function renderActionButtons(row, actions) {
	let html = '<div class="d-flex align-items-center justify-content-center">';

	ACTION_TYPES.forEach(type => {
		if (actions[type]) {
			const config = ACTION_CONFIG[type];
			html += generateActionButton(actions[type], row, type, config.tooltip, config.icon, config.buttonClass, config.extraAttrs || '');
		}
	});

	html += '</div>';
	return html;
}

/**
 * Generate HTML for an action button (show, edit, delete) with appropriate attributes.
 *
 * @param {Object} action - Action definition object containing route, function, disable conditions, and tooltips.
 * @param {Object} row - The data row for which the action is being generated.
 * @param {string} type - Type of action: 'show', 'edit', or 'delete'.
 * @param {string} defaultTooltip - Default tooltip text if none is provided in action.
 * @param {string} iconClass - CSS class for the icon to display in the button.
 * @param {string} buttonClass - CSS class for the button styling.
 * @param {string} extraAttrs - Additional HTML attributes to include in the button element.
 * @returns {string} - HTML string for the action button.
 */
function generateActionButton(action, row, type, defaultTooltip, iconClass, buttonClass, extraAttrs = '') {
	// Validate inputs
	if (!action?.route || !row?.id) {
		console.warn('generateActionButton: Missing required action or row data', { action, row });
		return '';
	}

	// Calculate button state and styling
	const disabled = action.disabledIf && action.disabledIf(row);
	const tooltip = resolveTooltip(disabled, action, defaultTooltip, row);
	const baseClass = `btn btn-sm ${buttonClass} me-2 ${disabled ? 'disabled' : ''}`;
	const baseAttrs = `data-bs-toggle="tooltip" data-bs-title="${escapeHtml(tooltip)}" ${disabled ? 'disabled' : ''}`;
	const actionRoute = escapeHtml(action.route.replace(':id', row.id));

	switch (type) {
		case 'show':
			return buildShowButton(actionRoute, baseClass, baseAttrs, iconClass, action, disabled, extraAttrs);
		case 'edit':
			return buildEditButton(actionRoute, baseClass, baseAttrs, iconClass, action, disabled, extraAttrs);
		case 'delete':
			return buildDeleteButton(actionRoute, baseAttrs, iconClass, action, buttonClass, disabled, extraAttrs);
		default:
			console.warn(`Unknown action type: ${type}`);
			return '';
	}
}

/**
 * Build custom buttons container HTML.
 * @private
 */
function buildCustomButtonsContainer(customButtons) {
	let html = '<div class="d-flex flex-row align-items-center justify-content-between mb-2 gap-3">';
	customButtons.forEach(button => {
		html += buildButtonHtml(button);
	});
	html += '</div>';
	return html;
}

/**
 * Build HTML for a custom button with loading spinner.
 * @param {Object} button - Button object containing text, href, class, icon, func, and params.
 * @param {string} button.text - Button display text
 * @param {string} button.href - Button link href
 * @param {string} button.class - Button CSS classes
 * @param {string} button.icon - Icon CSS class
 * @param {Function} button.func - Optional function to execute
 * @param {Array} button.params - Optional function parameters
 * @returns {string} - HTML string for the button.
 */
function buildButtonHtml(button) {
	// Validate required properties
	if (!button.text || !button.href) {
		console.warn('Button must have text and href properties:', button);
		return '';
	}

	const buttonFuncParams = button.params ? Array.from(button.params) : [];
	const spinnerId = buttonFuncParams[1];

	if (!spinnerId) {
		console.warn('Button missing spinner ID:', button);
		return '';
	}

	// Build function parameters safely
	const params = buttonFuncParams
		.map(param => typeof param === 'string' ? `'${escapeHtml(param)}'` : param)
		.join(', ');

	const onclick = button.func ? `onclick="${button.func.name}(${params})"` : '';

	// Escape all user inputs
	const values = {
		href: escapeHtml(button.href),
		class: escapeHtml(button.class ?? ''),
		text: escapeHtml(button.text),
		icon: escapeHtml(button.icon ?? '')
	};

	return `
        <a href="${values.href}" class="btn ${values.class} align-items-center" ${onclick}>
            <div class="${spinnerId}-spinner d-none flex-row align-items-center justify-content-center">
                <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                <span class="visually-hidden">${values.text}</span>
            </div>
            <div class="${spinnerId}-button-text d-flex flex-row align-items-center justify-content-center">
                <i class="${values.icon} me-2"></i>
                ${values.text}
            </div>
        </a>
    `;
}

/**
 * Build merged table options with error handling and callbacks.
 * @private
 */
function buildTableOptions(tableId, ajaxUrl, columns, buttonsHtml, userOptions) {
	const defaultOptions = {
		serverSide: true,
		ajax: {
			url: ajaxUrl,
			error: (_jqXHR, textStatus, errorThrown) => handleAjaxError(tableId, textStatus, errorThrown)
		},
		columnControl: ['order'],
		columnDefs: [{ target: -1, columnControl: [] }],
		layout: { topEnd: () => buttonsHtml },
		columns: columns
	};

	return {
		...defaultOptions,
		...userOptions,
		drawCallback: () => initializeTooltips()
	};
}

/**
 * Handle AJAX errors with user feedback.
 * @private
 */
function handleAjaxError(tableId, textStatus, errorThrown) {
	console.error(`Error de AJAX: ${textStatus}`, errorThrown);
	SwalToast.fire({
		icon: 'error',
		title: 'No se pudieron cargar los datos de la tabla.',
		timer: 8000
	});
	$(`#${tableId}_processing`).hide();
}

/**
 * Initialize Bootstrap tooltips on rendered elements.
 * @private
 */
function initializeTooltips() {
	$('[data-bs-toggle="tooltip"]').each(function () {
		new bootstrap.Tooltip(this);
	});
}

/**
 * Resolve the tooltip text for a button.
 * @private
 */
function resolveTooltip(disabled, action, defaultTooltip, row) {
	if (disabled) {
		return action.disabledIfTooltip(row) || 'Deshabilitado';
	}
	const userTooltip = action.tooltip || defaultTooltip;
	return typeof userTooltip === 'function' ? userTooltip(row) : userTooltip;
}

/**
 * Build show action button HTML.
 * @private
 */
function buildShowButton(route, baseClass, baseAttrs, iconClass, action, disabled, extraAttrs) {
	const onclick = !disabled && action.func ? `onclick="${action.func.name}('${route}', this);"` : '';
	return `
        <a class="info-button ${baseClass}" ${onclick} ${baseAttrs} ${extraAttrs}>
            <div class="info-spinner d-none flex-row align-items-center justify-content-center">
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            </div>
            <div class="info-button-text d-flex flex-row align-items-center justify-content-center">
                <i class="${iconClass}"></i>
            </div>
        </a>
    `;
}

/**
 * Build edit action button HTML.
 * @private
 */
function buildEditButton(route, baseClass, baseAttrs, iconClass, action, disabled, extraAttrs) {
	const href = disabled ? '#' : route;
	const onclick = !disabled && action.func ? `onclick="${action.func.name}(this, 'edit', true);"` : '';
	return `
        <a href="${href}" class="edit-button ${baseClass}" ${onclick} ${baseAttrs} ${extraAttrs}>
            <div class="edit-spinner d-none flex-row align-items-center justify-content-center">
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            </div>
            <div class="edit-button-text d-flex flex-row align-items-center justify-content-center">
                <i class="${iconClass}"></i>
            </div>
        </a>
    `;
}

/**
 * Build delete action button HTML (as form).
 * @private
 */
function buildDeleteButton(route, baseAttrs, iconClass, action, buttonClass, disabled, extraAttrs) {
	const onsubmit = !disabled && action.func ? `onsubmit="${action.func.name}(event);"` : '';
	return `
        <form method="POST" action="${route}" ${onsubmit} ${baseAttrs} ${extraAttrs}>
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="delete-form-button btn btn-sm ${buttonClass}" ${disabled ? 'disabled' : ''}>
                <div class="delete-form-spinner d-none flex-row align-items-center justify-content-center">
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                </div>
                <div class="delete-form-button-text d-flex flex-row align-items-center justify-content-center">
                    <i class="${iconClass}"></i>
                </div>
            </button>
        </form>
    `;
}