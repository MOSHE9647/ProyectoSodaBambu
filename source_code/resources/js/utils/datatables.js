import '../../libs/datatables/datatables.js';
import languageES from 'datatables.net-plugins/i18n/es-MX.mjs';
import { SwalToast } from "../utils/sweetalert.js";

/**
 * Custom language settings for DataTables in Spanish with modified translations.
 * @type {{aria: {sortAscending: string, sortDescending: string}, autoFill: {cancel: string, fill: string, fillHorizontal: string, fillVertical: string}, buttons: {collection: string, colvis: string, colvisRestore: string, copy: string, copyKeys: string, copySuccess: {"1": string, _: string}, copyTitle: string, csv: string, excel: string, pageLength: {"-1": string, _: string}, pdf: string, print: string, createState: string, removeAllStates: string, removeState: string, renameState: string, savedStates: string, stateRestore: string, updateState: string}, infoThousands: string, loadingRecords: string, paginate: {first: function(): string, previous: function(): string, next: function(): string, last: function(): string}, processing: string, search: string, searchBuilder: {add: string, button: {"0": string, _: string}, clearAll: string, condition: string, deleteTitle: string, leftTitle: string, logicAnd: string, logicOr: string, rightTitle: string, title: {"0": string, _: string}, value: string, conditions: {date: {after: string, before: string, between: string, empty: string, equals: string, not: string, notBetween: string, notEmpty: string}, number: {between: string, empty: string, equals: string, gt: string, gte: string, lt: string, lte: string, not: string, notBetween: string, notEmpty: string}, string: {contains: string, empty: string, endsWith: string, equals: string, not: string, startsWith: string, notEmpty: string, notContains: string, notEndsWith: string, notStartsWith: string}, array: {equals: string, empty: string, contains: string, not: string, notEmpty: string, without: string}}, data: string}, searchPanes: {clearMessage: string, collapse: {"0": string, _: string}, count: string, emptyPanes: string, loadMessage: string, title: string, countFiltered: string, collapseMessage: string, showMessage: string}, select: {cells: {"1": string, _: string}, columns: {"1": string, _: string}, rows: {"1": string, _: string}}, thousands: string, datetime: {previous: string, hours: string, minutes: string, seconds: string, unknown: string, amPm: string[], next: string, months: {"0": string, "1": string, "10": string, "11": string, "2": string, "3": string, "4": string, "5": string, "6": string, "7": string, "8": string, "9": string}, weekdays}, editor: {close: string, create: {button: string, title: string, submit: string}, edit: {button: string, title: string, submit: string}, remove: {button: string, title: string, submit: string, confirm: {_: string, "1": string}}, multi: {title: string, restore: string, noMulti: string, info: string}, error: {system: string}}, decimal: string, emptyTable: string, zeroRecords: string, info: string, infoFiltered: string, lengthMenu: string, stateRestore: {removeTitle: string, creationModal: {search: string, button: string, columns: {search: string, visible: string}, name: string, order: string, paging: string, scroller: string, searchBuilder: string, select: string, title: string, toggleLabel: string}, duplicateError: string, emptyError: string, emptyStates: string, removeConfirm: string, removeError: string, removeJoiner: string, removeSubmit: string, renameButton: string, renameLabel: string, renameTitle: string}, infoEmpty: string}}
 */
export const CustomLanguage = {
	...languageES, // Spread the existing Spanish language settings
	lengthMenu: '_MENU_', // Simplify length menu text
	search: '', // Remove search label
	paginate: { // Use icons for pagination controls
		first: () => { return '<i class="bi-skip-backward-fill" style="font-size: .69rem"></i>'; },
		previous: () => { return '<i class="bi-caret-left-fill" style="font-size: .69rem"></i>'; },
		next: () => { return '<i class="bi-caret-right-fill" style="font-size: .69rem"></i>'; },
		last: () => { return '<i class="bi-skip-forward-fill" style="font-size: .69rem"></i>'; },
	}
};

// Set global defaults for all DataTables
$.extend($.fn.dataTable.defaults, {
	pageLength: 5, // Default number of rows per page
	processing: true, // Show processing indicator
	responsive: true, // Enable responsive design
	autoWidth: true, // Automatically adjust column widths
	language: CustomLanguage, // Use custom Spanish language settings
	order: [[0, 'asc']], // Default sort by first column ascending
	layout: { // Custom layout for table controls
		topStart: function () {
			return `
				<div class="input-group flex-nowrap mb-2" style="max-width: 300px;">
  					<span class="input-group-text" id="addon-wrapping"><i class="bi-search"></i></span>
  					<input id="customSearchBox" type="text" class="form-control" placeholder="Buscar..." aria-label="Buscar" aria-describedby="addon-wrapping">
				</div>
			`;
		}, // Search box at top left
		bottomStart: { // Info text at bottom left
			pageLength: { // Page length selector
				menu: [5, 10, 25, 50, -1]
			},
			info: true // Table information
		},
		bottomEnd: 'paging' // Pagination controls at bottom right
	},
	ordering: { // Enable ordering but disable on specific columns
		indicators: false, // Disable sort indicators
		handler: false // Disable click handler
	}
});

/**
 * Initialize a new DataTable on the specified table element with given options.
 * Uses DataTables defaults which can be overridden by the options' parameter.
 *
 * @param tableId - The ID of the table element.
 * @param options - Additional DataTable options to override defaults.
 * @returns {DataTable|void} The initialized DataTable instance or void if table not found.
 */
function NewDataTable(tableId, options = {}) {
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
 * Initialize a CRUD DataTable with standard actions (show, edit, delete) and custom buttons.
 * @param {string} tableId - The ID of the table element.
 * @param {string} ajaxUrl - The URL for server-side data.
 * @param {Array} columns - Array of column definitions.
 * @param {Object} actions - Object defining action routes, functions, and disable conditions.
 *   - show: { route: string, func: function, disabledIf?: function(row), disabledIfTooltip?: string, tooltip?: string } - Route, function, and optional disable condition and tooltips.
 *   - edit: { route: string, disabledIf?: function(row), disabledIfTooltip?: string, tooltip?: string } - Route and optional disable condition and tooltips.
 *   - delete: { route: string, disabledIf?: function(row), disabledIfTooltip?: string, tooltip?: string } - Route and optional disable condition and tooltips.
 * @param {Array} customButtons - Array of custom button objects { text, href, class, icon }.
 * @param {Object} options - Additional DataTable options to override defaults.
 */
export function NewCrudDataTable(tableId, ajaxUrl, columns, actions, customButtons = [], options = {}) {
	if (actions) {
		// Add actions column if actions are provided
		columns.push({
			data: null,	// Use null data source for custom rendering
			name: 'actions', // Name of the column
			orderable: false, // Disable ordering
			searchable: false, // Disable searching
			width: '12%', // Set fixed width
			render: function (data, type, row) {
				// Generate action buttons HTML
				let actionButtons = '<div class="d-flex align-items-center justify-content-center">';

				if (actions.show) {
					actionButtons += generateActionButton(
						actions.show,
						row,
						'show',
						'Ver Más',
						'bi-info-circle',
						'btn-info'
					);
				}

				if (actions.edit) {
					actionButtons += generateActionButton(
						actions.edit,
						row,
						'edit',
						'Editar',
						'bi-pencil-square',
						'btn-primary'
					);
				}

				if (actions.delete) {
					actionButtons += generateActionButton(
						actions.delete,
						row,
						'delete',
						'Eliminar',
						'bi-trash',
						'btn-danger',
						'class="delete-form"'
					);
				}

				actionButtons += '</div>';

				// Return the combined action buttons HTML
				return actionButtons;
			}
		});
	}

	// Build custom buttons HTML. They will be placed at the top right of the table.
	let buttonsHtml = '<div class="d-flex flex-row align-items-center justify-content-between mb-2 gap-3">';
	customButtons.forEach(button => {
		buttonsHtml += `
            <a href="${button.href || '#'}" class="btn ${button.class} align-items-center">
                <i class="${button.icon} me-2"></i>
                ${button.text}
            </a>
        `;
	});
	buttonsHtml += '</div>';

	// Default options for the CRUD DataTable
	const defaultOptions = {
		serverSide: true, // Enable server-side processing
		ajax: {
			url: ajaxUrl,
			error: function (jqXHR, textStatus, errorThrown) {
				// Show error message on AJAX failure
				console.error(`Error de AJAX: ${textStatus}`, errorThrown);
				SwalToast.fire({
					icon: 'error',
					title: 'No se pudieron cargar los datos de la tabla.',
					timer: 8000
				});

				// Update table to show error message
				$(`#${tableId}_processing`).hide();
			}
		},
		columnControl: ['order'], // Enable column control for ordering
		columnDefs: [{ // Disable ordering on action columns
			target: -1,
			columnControl: []
		}],
		layout: { // Custom layout to include buttons
			topEnd: () => buttonsHtml, // Place custom buttons at top right
		},
		columns: columns // Set columns definition defined above (table columns + actions)
	};

	// Merge with provided options
	const mergedOptions = {
		...defaultOptions, ...options, // Spread to allow overrides
		// Re-define drawCallback to include tooltip initialization
		drawCallback: function () {
			// Enable Bootstrap Tooltips if any
			$('[data-bs-toggle="tooltip"]').each(function () {
				new bootstrap.Tooltip(this);
			});
		}
	};

	// Initialize and return the DataTable
	return NewDataTable(tableId, mergedOptions);
}

/**
 * Generate HTML for an action button (show, edit, delete) with appropriate attributes.
 *
 * @param action - Action definition object containing route, function, disable conditions, and tooltips.
 * @param row - The data row for which the action is being generated.
 * @param type - Type of action: 'show', 'edit', or 'delete'.
 * @param defaultTooltip - Default tooltip text if none is provided in action.
 * @param iconClass - CSS class for the icon to display in the button.
 * @param buttonClass - CSS class for the button styling.
 * @param extraAttrs - Additional HTML attributes to include in the button element.
 * @returns {string} - HTML string for the action button.
 */
function generateActionButton(action, row, type, defaultTooltip, iconClass, buttonClass, extraAttrs = '') {
	// Get all necessary attributes for the button
	const disabled = action.disabledIf && action.disabledIf(row);
	const tooltip = disabled ? (action.disabledIfTooltip || 'Deshabilitado') : (action.tooltip || defaultTooltip);
	const disabledAttrs = disabled ? 'disabled' : '';
	const baseClass = `btn btn-sm ${buttonClass} me-2 ${disabled ? 'disabled' : ''}`;
	const baseAttrs = `
		data-bs-toggle="tooltip"
		data-bs-title="${tooltip}" ${disabledAttrs}
	`;

	// Generate HTML based on action type
	// Onclick functions are being made by concatenating strings to avoid issues with DataTables rendering
	const actionRoute = action.route.replace(':id', row.id);

	switch (type) {
		case 'show':
			const actionFunc = `onclick="${action.func.name}(\'${actionRoute}\', this);"`;
			return `
				<a class="info-button ${baseClass}" ${disabled ? '' : actionFunc} ${baseAttrs} ${extraAttrs}>
					<div class="info-spinner d-none flex-row align-items-center justify-content-center">
						<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
					</div>
                    <div class="info-button-text d-flex flex-row align-items-center justify-content-center">
                    	<i class="${iconClass}"></i>
					</div>
				</a>
			`;
		case 'edit':
			return `
				<a href="${disabled ? '#' : actionRoute}" class="edit-button ${baseClass}" ${baseAttrs} ${extraAttrs}>
					<i class="${iconClass}"></i>
				</a>
			`;
		case 'delete':
			return `
				<form
					method="POST"
					action="${actionRoute}"
					onsubmit="${disabled ? 'return false;' : action.func.name + '(event);'}"
					${baseAttrs}
					${extraAttrs}
				>
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
                </form>`;
		default:
			return '';
	}
}
