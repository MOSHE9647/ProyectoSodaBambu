import '../config/datatables.js';
import { escapeHtml } from "./utils.js";
import { SwalNotificationTypes, SwalToast } from "./sweetalert.js";

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

	// Link custom search box to DataTable search when it exists.
	if (searchBox.length) {
		searchBox.on('keyup', function () {
			dataTable.search(this.value).draw();
		});
	}

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
 * @param {boolean} [options.showSearchBar=true] - Whether to render DataTables search bar area.
 * @param {'top-start'|'top-end'} [options.customButtonsPosition='top-end'] - Layout section where customButtons are rendered.
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
 * Resolve DataTables layout key for custom buttons position.
 * @private
 */
function resolveButtonsLayoutKey(position = 'top-end') {
	const normalized = String(position || 'top-end').toLowerCase();

	if (normalized === 'top-start' || normalized === 'topstart') {
		return 'topStart';
	}

	if (normalized === 'top-end' || normalized === 'topend') {
		return 'topEnd';
	}

	console.warn(`Invalid customButtonsPosition: ${position}. Using top-end.`);
	return 'topEnd';
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
	let html = '<div class="d-flex align-items-center justify-content-flex-start">';

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
	let html = '';
	customButtons.forEach(button => {
		html += buildCustomElementHtml(button);
	});
	return html;
}

/**
	* Build HTML for a custom control.
	* Supported types: anchor, button, select, input, and input subtypes (date, datetime-local, etc).
	* @param {Object} element - Custom control configuration.
	* @returns {string}
 */
function buildCustomElementHtml(element = {}) {
	const type = normalizeElementType(element.type);

	switch (type) {
		case 'anchor':
			return buildAnchorElementHtml(element);
		case 'button':
			return buildButtonElementHtml(element);
		case 'select':
			return buildSelectElementHtml(element);
		case 'input':
			return buildInputElementHtml(element);
		default:
			console.warn(`Unsupported custom element type: ${type}`, element);
			return '';
	}
}

/**
	* Normalize custom element type.
	* @private
	*/
function normalizeElementType(type = 'anchor') {
	const normalized = String(type || 'anchor').toLowerCase();
	if (normalized === 'datetime') return 'input';

	if (normalized === 'a') return 'anchor';
	if (['anchor', 'button', 'select', 'input'].includes(normalized)) return normalized;

	// Allow direct input types: date, datetime-local, text, etc.
	const inputTypes = [
		'text', 'search', 'email', 'number', 'password', 'tel', 'url',
		'date', 'datetime-local', 'time', 'month', 'week'
	];

	if (inputTypes.includes(normalized)) return 'input';
	return normalized;
}

/**
	* Build anchor HTML. Maintains backward compatibility with spinner-based anchor buttons.
	* @private
	*/
function buildAnchorElementHtml(element) {
	if (!element.text || !element.href) {
		console.warn('Anchor element must have text and href properties:', element);
		return '';
	}

	const handlerAttrs = buildHandlerAttribute(element, 'onclick');
	const values = {
		href: escapeHtml(element.href),
		class: escapeHtml(element.class ?? ''),
		text: escapeHtml(element.text),
		icon: escapeHtml(element.icon ?? '')
	};

	const spinnerId = getSpinnerId(element);
	if (spinnerId) {
		return `
        <a href="${values.href}" class="btn ${values.class} align-items-center" ${handlerAttrs}>
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

	return `
        <a href="${values.href}" class="btn ${values.class} align-items-center" ${handlerAttrs}>
            ${buildIconAndLabel(values.icon, values.text)}
        </a>
    `;
}

/**
	* Build button HTML.
	* @private
	*/
function buildButtonElementHtml(element) {
	if (!element.text) {
		console.warn('Button element must have text property:', element);
		return '';
	}

	const values = {
		id: escapeHtml(element.id ?? ''),
		name: escapeHtml(element.name ?? ''),
		class: escapeHtml(element.class ?? ''),
		text: escapeHtml(element.text),
		icon: escapeHtml(element.icon ?? ''),
		type: escapeHtml(element.buttonType ?? 'button')
	};

	const disabledAttr = element.disabled ? 'disabled' : '';
	const handlerAttrs = buildHandlerAttribute(element, 'onclick');

	return `
        <button type="${values.type}" id="${values.id}" name="${values.name}" class="btn ${values.class} align-items-center" ${disabledAttr} ${handlerAttrs}>
            ${buildIconAndLabel(values.icon, values.text)}
        </button>
    `;
}

/**
	* Build select HTML using Bootstrap input-group structure.
	* @private
	*/
function buildSelectElementHtml(element) {
	const selectId = escapeHtml(element.id ?? 'inputGroupSelect01');
	const labelText = escapeHtml(element.label ?? 'Options');
	const labelIcon = escapeHtml(element.labelIcon ?? '');
	const selectClass = escapeHtml(element.class ?? '');
	const selectName = escapeHtml(element.name ?? '');
	const options = Array.isArray(element.options) ? element.options : [];
	const disabledAttr = element.disabled ? 'disabled' : '';
	const handlerAttrs = buildHandlerAttribute(element, 'onchange');
	const firstOptionText = escapeHtml(element.placeholder ?? 'Choose...');

	let optionsHtml = `<option value="" ${element.placeholderSelected === false ? '' : 'selected'}>${firstOptionText}</option>`;

	options.forEach((option) => {
		if (typeof option === 'string' || typeof option === 'number') {
			const value = escapeHtml(String(option));
			optionsHtml += `<option value="${value}">${value}</option>`;
			return;
		}

		const value = escapeHtml(String(option?.value ?? ''));
		const text = escapeHtml(String(option?.text ?? option?.label ?? value));
		const selectedAttr = option?.selected ? 'selected' : '';
		const optionDisabled = option?.disabled ? 'disabled' : '';
		optionsHtml += `<option value="${value}" ${selectedAttr} ${optionDisabled}>${text}</option>`;
	});

	return `
        <div class="input-group ${escapeHtml(element.wrapperClass ?? '')}">
            <label class="input-group-text" for="${selectId}">${labelIcon ? `<i class="${labelIcon}"></i>` : ''}${labelText}</label>
            <select class="form-select ${selectClass}" id="${selectId}" name="${selectName}" ${disabledAttr} ${handlerAttrs}>
                ${optionsHtml}
            </select>
        </div>
    `;
}

/**
	* Build input HTML using Bootstrap input-group structure.
	* @private
	*/
function buildInputElementHtml(element) {
	const rawType = String(element.type || '').toLowerCase();
	const normalizedInputType = rawType === 'datetime' ? 'datetime-local' : rawType;
	const inputType = ['input', 'anchor', 'button', 'select', 'a'].includes(normalizedInputType) ? (element.inputType ?? 'text') : normalizedInputType;
	const inputId = escapeHtml(element.id ?? 'inputGroupInput01');
	const labelText = escapeHtml(element.label ?? 'Input');
	const labelIcon = escapeHtml(element.labelIcon ?? '');
	const inputClass = escapeHtml(element.class ?? '');
	const inputName = escapeHtml(element.name ?? '');
	const inputValue = escapeHtml(element.value ?? '');
	const inputPlaceholder = escapeHtml(element.placeholder ?? '');
	const disabledAttr = element.disabled ? 'disabled' : '';
	const handlerAttrs = buildHandlerAttribute(element, 'onchange');

	return `
        <div class="input-group mb-2 ${escapeHtml(element.wrapperClass ?? '')}">
            <label class="input-group-text" for="${inputId}">${labelIcon ? `<i class="${labelIcon}"></i>` : ''}${labelText}</label>
			<input type="${escapeHtml(inputType)}" class="form-control ${inputClass}" id="${inputId}" name="${inputName}" value="${inputValue}" placeholder="${inputPlaceholder}" ${disabledAttr} ${handlerAttrs}>
        </div>
    `;
}

/**
	* Build icon + text content.
	* @private
	*/
function buildIconAndLabel(icon, text) {
	if (!icon) {
		return `<span>${text}</span>`;
	}

	return `
        <i class="${icon} me-2"></i>
        <span>${text}</span>
    `;
}

/**
	* Resolve spinner id from params for backward compatibility.
	* @private
	*/
function getSpinnerId(element) {
	const params = Array.isArray(element.params) ? Array.from(element.params) : [];
	const spinnerId = params[1];

	if (typeof spinnerId !== 'string' || spinnerId.trim().length === 0) {
		return '';
	}

	return escapeHtml(spinnerId.trim());
}

/**
	* Build inline HTML event attribute.
	* @private
	*/
function buildHandlerAttribute(element, defaultEvent) {
	const handlerName = resolveHandlerName(element.func, element.funcName);
	if (!handlerName) return '';

	const params = Array.isArray(element.params)
		? element.params.map(param => typeof param === 'string' ? `'${escapeHtml(param)}'` : param).join(', ')
		: '';

	const eventName = escapeHtml(element.event ?? defaultEvent);
	return params.length > 0
		? `${eventName}="${handlerName}(${params})"`
		: `${eventName}="${handlerName}()"`;
}

/**
 * Build merged table options with error handling and callbacks.
 * @private
 */
function buildTableOptions(tableId, ajaxUrl, columns, buttonsHtml, userOptions) {
	const {
		showSearchBar = true,
		customButtonsPosition = 'top-end',
		...safeUserOptions
	} = userOptions;

	const defaultOptions = {
		pageLength:10,
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

	const mergedAjax = {
		...defaultOptions.ajax,
		...(safeUserOptions.ajax ?? {})
	};

	const globalLayout = $.fn.dataTable.defaults.layout ?? {};
	const userLayout = safeUserOptions.layout ?? {};
	const buttonsLayoutKey = resolveButtonsLayoutKey(customButtonsPosition);
	const customButtonsRenderer = () => buttonsHtml;
	const mergedLayout = {
		...globalLayout,
		...userLayout,
		[buttonsLayoutKey]: userLayout[buttonsLayoutKey] ?? customButtonsRenderer,
	};

	if (!showSearchBar) {
		if (buttonsLayoutKey !== 'topStart') {
			mergedLayout.topStart = null;
		}
	}

	const userDrawCallback = safeUserOptions.drawCallback;

	return {
		...defaultOptions,
		...safeUserOptions,
		ajax: mergedAjax,
		layout: mergedLayout,
		searching: showSearchBar,
		drawCallback: (...args) => {
			initializeTooltips();
			if (typeof userDrawCallback === 'function') {
				userDrawCallback(...args);
			}
		}
	};
}

/**
 * Handle AJAX errors with user feedback.
 * @private
 */
function handleAjaxError(tableId, textStatus, errorThrown) {
	console.error(`Error de AJAX: ${textStatus}`, errorThrown);
	SwalToast.fire({
		icon: SwalNotificationTypes.ERROR,
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
	const handlerName = resolveHandlerName(action.func, action.funcName);
	const onclick = !disabled && handlerName ? `onclick="${handlerName}('${route}', this);"` : '';
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
	const handlerName = resolveHandlerName(action.func, action.funcName);
	const onclick = !disabled && handlerName ? `onclick="${handlerName}(this, 'edit', true);"` : '';
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
	const handlerName = resolveHandlerName(action.func, action.funcName);
	const onsubmit = !disabled && handlerName ? `onsubmit="${handlerName}(event);"` : '';
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

/**
 * Resolve a globally callable handler name for inline HTML events.
 * Uses explicit names first to avoid relying on Function.name in minified builds.
 * @private
 */
function resolveHandlerName(func, explicitName) {
	const explicit = explicitName?.trim();
	if (explicit) return explicit;
	if (typeof func !== 'function') return '';
	if (func.name) return func.name;

	// Fallback: find the function in the global scope by reference.
	for (const key in window) {
		if (window[key] === func) return key;
	}

	return '';
}