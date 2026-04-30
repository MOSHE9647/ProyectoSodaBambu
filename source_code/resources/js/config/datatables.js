import '../../libs/datatables/datatables.js';
import languageES from 'datatables.net-plugins/i18n/es-MX.mjs';

/**
 * Custom language settings for DataTables in Spanish with modified translations.
 * @type {{aria: {sortAscending: string, sortDescending: string}, autoFill: {cancel: string, fill: string, fillHorizontal: string, fillVertical: string}, buttons: {collection: string, colvis: string, colvisRestore: string, copy: string, copyKeys: string, copySuccess: {"1": string, _: string}, copyTitle: string, csv: string, excel: string, pageLength: {"-1": string, _: string}, pdf: string, print: string, createState: string, removeAllStates: string, removeState: string, renameState: string, savedStates: string, stateRestore: string, updateState: string}, infoThousands: string, loadingRecords: string, paginate: {first: function(): string, previous: function(): string, next: function(): string, last: function(): string}, processing: string, search: string, searchBuilder: {add: string, button: {"0": string, _: string}, clearAll: string, condition: string, deleteTitle: string, leftTitle: string, logicAnd: string, logicOr: string, rightTitle: string, title: {"0": string, _: string}, value: string, conditions: {date: {after: string, before: string, between: string, empty: string, equals: string, not: string, notBetween: string, notEmpty: string}, number: {between: string, empty: string, equals: string, gt: string, gte: string, lt: string, lte: string, not: string, notBetween: string, notEmpty: string}, string: {contains: string, empty: string, endsWith: string, equals: string, not: string, startsWith: string, notEmpty: string, notContains: string, notEndsWith: string, notStartsWith: string}, array: {equals: string, empty: string, contains: string, not: string, notEmpty: string, without: string}}, data: string}, searchPanes: {clearMessage: string, collapse: {"0": string, _: string}, count: string, emptyPanes: string, loadMessage: string, title: string, countFiltered: string, collapseMessage: string, showMessage: string}, select: {cells: {"1": string, _: string}, columns: {"1": string, _: string}, rows: {"1": string, _: string}}, thousands: string, datetime: {previous: string, hours: string, minutes: string, seconds: string, unknown: string, amPm: string[], next: string, months: {"0": string, "1": string, "10": string, "11": string, "2": string, "3": string, "4": string, "5": string, "6": string, "7": string, "8": string, "9": string}, weekdays}, editor: {close: string, create: {button: string, title: string, submit: string}, edit: {button: string, title: string, submit: string}, remove: {button: string, title: string, submit: string, confirm: {_: string, "1": string}}, multi: {title: string, restore: string, noMulti: string, info: string}, error: {system: string}}, decimal: string, emptyTable: string, zeroRecords: string, info: string, infoFiltered: string, lengthMenu: string, stateRestore: {removeTitle: string, creationModal: {search: string, button: string, columns: {search: string, visible: string}, name: string, order: string, paging: string, scroller: string, searchBuilder: string, select: string, title: string, toggleLabel: string}, duplicateError: string, emptyError: string, emptyStates: string, removeConfirm: string, removeError: string, removeJoiner: string, removeSubmit: string, renameButton: string, renameLabel: string, renameTitle: string}, infoEmpty: string}}
 */
export const CustomLanguage = {
    ...languageES, 			// Spread the existing Spanish language settings
    lengthMenu: '_MENU_', 	// Simplify length menu text
    search: '', 			// Remove search label
    paginate: { 			// Use icons for pagination controls
        first: () => { return '<i class="bi-skip-backward-fill" style="font-size: .69rem"></i>'; },
        previous: () => { return '<i class="bi-caret-left-fill" style="font-size: .69rem"></i>'; },
        next: () => { return '<i class="bi-caret-right-fill" style="font-size: .69rem"></i>'; },
        last: () => { return '<i class="bi-skip-forward-fill" style="font-size: .69rem"></i>'; },
    }
};

// Set global defaults for all DataTables
$.extend($.fn.dataTable.defaults, {
    pageLength: 10, 				// Default number of rows per page
    processing: true, 			// Show processing indicator
    responsive: true, 			// Enable responsive design
    autoWidth: true, 			// Automatically adjust column widths
    language: CustomLanguage, 	// Use custom Spanish language settings
    order: [[0, 'asc']], 		// Default sort by first column ascending
    layout: { 					// Custom layout for table controls
        topStart: function () { // Search box at top left
            return `
                <div class="input-group flex-nowrap mb-2" style="max-width: 300px;">
                      <span class="input-group-text" id="addon-wrapping"><i class="bi-search"></i></span>
                      <input id="customSearchBox" type="text" class="form-control" placeholder="Buscar..." aria-label="Buscar" aria-describedby="addon-wrapping">
                </div>
            `;
        },
        bottomStart: { 		// Info text at bottom left
            pageLength: { 	// Page length selector
                menu: [5, 10, 25, 50, -1]
            },
            info: true 		// Table information
        },
        bottomEnd: 'paging' // Pagination controls at bottom right
    },
    ordering: { 			// Enable ordering but disable on specific columns
        indicators: false, 	// Disable sort indicators
        handler: false 		// Disable click handler
    }
});