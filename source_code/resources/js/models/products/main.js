import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, toggleLoadingState } from '../../utils/utils.js';
import { SwalNotificationTypes, SwalToast } from '../../utils/sweetalert.js';

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'producto';

// String Constants
const BTN_CLASS_PRIMARY = 'btn-primary';

// Routes Configuration
const MODEL_ROUTES = {
	index: route('products.index'),
	create: route('products.create'),
	show: route('products.show', { product: ':id' }),
	edit: route('products.edit', { product: ':id' }),
	delete: route('products.destroy', { product: ':id' }),
};

const PRODUCT_TYPE_LABELS = {
	merchandise: 'Mercaderia',
	dish: 'Platillo',
	drink: 'Bebida',
	packaged: 'Empaquetado',
};

const CURRENCY_FORMATTER = new Intl.NumberFormat('es-CR', {
	style: 'currency',
	currency: 'CRC',
	minimumFractionDigits: 2,
	maximumFractionDigits: 2,
});

// retrieve initial filter state from URL parameters
const urlParams = new URLSearchParams(window.location.search);
let showOnlyLowStock = urlParams.get('filter') === 'low_stock';

let productsDataTable = null;
const canManageProducts = ($('#products-table').data('can-manage-products') ?? '').toString() === '1';

// ==================== Global Functions ====================

// Expose functions globally
window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;

// ==================== Helper Functions ====================

function formatProductType(type) {
	if (!type) return 'N/A';
	return PRODUCT_TYPE_LABELS[type] || type;
}

function formatStockValue(value) {
	if (value === null || typeof value === 'undefined' || value === '') {
		return 'N/A';
	}

	return value;
}

function formatCurrentStock(currentStockValue, minimumStockValue) {
	if (currentStockValue === null || minimumStockValue === null || typeof currentStockValue === 'undefined' || typeof minimumStockValue === 'undefined') {
		return 'N/A';
	}

	const currentStock = Number.parseInt(currentStockValue, 10);
	const minimumStock = Number.parseInt(minimumStockValue, 10);

	if (Number.isNaN(currentStock) || Number.isNaN(minimumStock)) {
		return 'N/A';
	}

	if (currentStock <= minimumStock) {
		return `<span class="badge text-bg-danger">${currentStock}</span>`;
	}

	return currentStock;
}

function formatCurrency(value) {
	const amount = Number.parseFloat(value);
	if (Number.isNaN(amount)) return CURRENCY_FORMATTER.format(0);
	return CURRENCY_FORMATTER.format(amount);
}

/**
 * Shows information for a specific product.
 * @param {string} url - The URL to fetch product information from
 * @param {HTMLElement} anchor - The anchor element for the modal
 * @returns {Promise<void>} A promise resolving when the modal is shown
 */
window.showProduct = function (url, anchor) {
	return showModelInfo(url, anchor, MODEL_NAME);
};

/**
 * Deletes a specific product.
 * @param {Event} e - The event object
 * @returns {Promise<void>} A promise resolving when the product is deleted
 */
window.deleteProduct = function (e) {
	return deleteModel(e, MODEL_NAME);
};

window.toggleLowStockFilter = function () {
	showOnlyLowStock = !showOnlyLowStock;

	const $button = $('.low-stock-filter-button');
	$button.toggleClass('btn-outline-warning btn-warning');
	$('.low-stock-filter-button-text').text(showOnlyLowStock ? 'Mostrar todos' : 'Solo stock bajo');

	if (productsDataTable) {
		productsDataTable.ajax.reload(null, true);

		setTimeout(() => {
			productsDataTable.columns.adjust();
			if (productsDataTable.responsive) {
				productsDataTable.responsive.recalc();
			}
		}, 0);
	}
};

// ==================== DataTable Initialization ====================

// Ensure the DOM is fully loaded before initializing the DataTable
$(() => {
	// Define columns for products table (only for server-side processing)
	const columns = [
		{
			data: 'barcode',
			name: 'barcode',
			render: (data) => data || 'N/A',
		},
		{
			data: 'name',
			name: 'name',
		},
		{
			data: 'category',
			name: 'category_id',
			render: (data) => data?.name || 'Sin categoria',
		},
		{
			data: 'type',
			name: 'type',
			render: (data) => formatProductType(data),
		},
		{
			data: 'current_stock',
			name: 'ps.current_stock',
			render: (data, _type, row) => formatCurrentStock(data, row.minimum_stock),
		},
		{
			data: 'minimum_stock',
			name: 'ps.minimum_stock',
			render: (data) => formatStockValue(data),
		},
		{
			data: 'sale_price',
			name: 'sale_price',
			render: (data) => formatCurrency(data),
		},
	];

	/**
	 * Define actions for each product row in the DataTable.
	 */
	const actions = {
		show: {
			route: MODEL_ROUTES.show,
			func: window.showProduct,
			funcName: 'showProduct',
			tooltip: 'Ver detalles',
		},
	};

	if (canManageProducts) {
		actions.edit = {
			route: MODEL_ROUTES.edit,
			func: toggleLoadingState,
			funcName: 'toggleLoadingState',
			tooltip: `Editar ${MODEL_NAME}`,
		};

		actions.delete = {
			route: MODEL_ROUTES.delete,
			tooltip: `Eliminar ${MODEL_NAME}`,
			func: window.deleteProduct,
			funcName: 'deleteProduct',
		};
	}

	/**
	 * Define custom buttons for the DataTable interface.
	 */
	const customButtons = [
		{
			text: 'Solo stock bajo',
			href: 'javascript:void(0)',
			class: 'low-stock-filter-button btn-outline-warning',
			icon: 'bi-exclamation-triangle',
			func: window.toggleLowStockFilter,
			funcName: 'toggleLowStockFilter',
			params: ['.low-stock-filter-button', 'low-stock-filter'],
		},
	];

	// Set initial state of low stock filter button based on URL parameter
	if (showOnlyLowStock) {
        const $button = $('.low-stock-filter-button');
        $button.removeClass('btn-outline-warning').addClass('btn-warning');
        $('.low-stock-filter-button-text').text('Mostrar todos');
    }	

	if (canManageProducts) {
		customButtons.unshift({
			text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
			href: MODEL_ROUTES.create,
			class: `create-button ${BTN_CLASS_PRIMARY}`,
			icon: 'bi-box-seam',
			func: toggleLoadingState,
			funcName: 'toggleLoadingState',
			params: ['.create-button', 'create', true],
		});
	}

	// Initialize the CRUD DataTable
	productsDataTable = CreateNewDataTable('products-table', MODEL_ROUTES.index, columns, actions, customButtons, {
		ajax: {
			url: MODEL_ROUTES.index,
			data: (d) => {
				d.low_stock = showOnlyLowStock ? 1 : 0;
			}
		},
		columnControl: [],
		columnDefs: [{ target: -1, columnControl: [] }],
		ordering: {
			indicators: false,
			handler: true,
		},
	});

	productsDataTable.on('draw.dt', () => {
		productsDataTable.columns.adjust();
		if (productsDataTable.responsive) {
			productsDataTable.responsive.recalc();
		}
	});

});
