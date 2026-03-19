import { showModelInfo, deleteModel } from '../actions.js';
import { CreateNewDataTable } from '../../utils/datatables.js';
import { capitalizeSentence, formatDate, toggleLoadingState } from '../../utils/utils.js';
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
};

const CURRENCY_FORMATTER = new Intl.NumberFormat('es-CR', {
	style: 'currency',
	currency: 'CRC',
	minimumFractionDigits: 2,
	maximumFractionDigits: 2,
});

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

function formatInventory(hasInventory) {
	const normalized = hasInventory === true || hasInventory === 1 || hasInventory === '1';
	return normalized ? 'Si' : 'No';
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
			data: 'has_inventory',
			name: 'has_inventory',
			render: (data) => formatInventory(data),
		},
		{
			data: 'sale_price',
			name: 'sale_price',
			render: (data) => formatCurrency(data),
		},
		{
			data: 'created_at',
			name: 'created_at',
			render: (data) => formatDate(data),
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
		edit: {
			route: MODEL_ROUTES.edit,
			func: toggleLoadingState,
			funcName: 'toggleLoadingState',
			tooltip: `Editar ${MODEL_NAME}`,
		},
		delete: {
			route: MODEL_ROUTES.delete,
			tooltip: `Eliminar ${MODEL_NAME}`,
			func: window.deleteProduct,
			funcName: 'deleteProduct',
		},
	};

	/**
	 * Define custom buttons for the DataTable interface.
	 */
	const customButtons = [
		{
			text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
			href: MODEL_ROUTES.create,
			class: `create-button ${BTN_CLASS_PRIMARY}`,
			icon: 'bi-box-seam',
			func: toggleLoadingState,
			funcName: 'toggleLoadingState',
			params: ['.create-button', 'create', true],
		},
	];

	// Initialize the CRUD DataTable
	CreateNewDataTable('products-table', MODEL_ROUTES.index, columns, actions, customButtons);
});
