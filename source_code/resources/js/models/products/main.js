import { showModelInfo, deleteModel } from "../actions.js";
import { CreateNewDataTable } from "../../utils/datatables.js";
import { capitalizeSentence, toggleLoadingState, formatCurrency } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ==================== Constants ====================

const MODEL_NAME = "producto";
const BTN_CLASS_PRIMARY = "btn-primary";

// Filter Configuration
const FILTER_QUERY_PARAM = "filter";
const FILTER_VALUES = new Set(["low_stock", "expiring_soon"]);

const urlParams = new URLSearchParams(window.location.search);
const requestedFilter = urlParams.get(FILTER_QUERY_PARAM);
const initialFilter = FILTER_VALUES.has(requestedFilter) ? requestedFilter : null;

let showLowStockOnly = initialFilter === "low_stock";
let showExpiringOnly = initialFilter === "expiring_soon";
let productsDataTable = null;

// Routes Configuration
const MODEL_ROUTES = {
	index: route("products.index"),
	create: route("products.create"),
	show: route("products.show", { product: ":id" }),
	edit: route("products.edit", { product: ":id" }),
	delete: route("products.destroy", { product: ":id" }),
};

const PRODUCT_TYPE_LABELS = {
	merchandise: "Mercadería",
	dish: "Platillo",
	drink: "Bebida",
	packaged: "Empaquetado",
};

// ==================== Global Exports ====================

window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;

// ==================== Formatters ====================

const formatProductType = (type) => PRODUCT_TYPE_LABELS[type] || type || "N/A";
const formatStockValue = (value) => value ?? "N/A";

function formatCurrentStock(currentValue, minimumValue) {
	if (currentValue == null || minimumValue == null) return "N/A";

	const currentStock = Number.parseInt(currentValue, 10);
	const minimumStock = Number.parseInt(minimumValue, 10);

	if (Number.isNaN(currentStock) || Number.isNaN(minimumStock)) return "N/A";

	return currentStock <= minimumStock
		? `<span class="badge text-bg-danger">${currentStock}</span>`
		: currentStock;
}

// ==================== Helper Functions ====================

window.toggleLowStockFilter = function () {
	showLowStockOnly = !showLowStockOnly;

	const $button = $(".low-stock-filter-button");
	$button.toggleClass("btn-warning btn-outline-warning");
	$(".low-stock-filter-button-text").html(
		`<i class="bi-exclamation-triangle me-2"></i> ${showLowStockOnly ? "Mostrar todos" : "Solo stock bajo"}`,
	);

	if (productsDataTable) {
		productsDataTable.ajax.reload(null, true);
	}
};

window.toggleExpiringSoonFilter = function () {
	showExpiringOnly = !showExpiringOnly;

	const $button = $(".expiring-soon-filter-button");
	$button.toggleClass("btn-danger btn-outline-danger");
	$(".expiring-soon-filter-button-text").html(
		`<i class="bi-hourglass-split me-2"></i> ${showExpiringOnly ? "Mostrar todos" : "Próximos a vencer"}`,
	);

	if (productsDataTable) {
		productsDataTable.ajax.reload(null, true);
	}
};

// ==================== DataTable Initialization ====================

$(() => {
	const $table = $("#products-table");
	const canCreateProducts = ($table.data("can-create-products") ?? "").toString() === "1";
	const canManageProducts = ($table.data("can-manage-products") ?? "").toString() === "1";

	const columns = [
		{
			data: "barcode",
			name: "barcode",
			type: "string",
			className: "dt-left",
			render: (data) => data || "N/A",
		},
		{ data: "name", name: "name" },
		{
			data: "category",
			name: "category_id",
			render: (data) => data?.name || "Sin categoría",
		},
		{ data: "type", name: "type", render: formatProductType },
		{
			data: "current_stock",
			name: "ps.current_stock",
			type: "string",
			className: "dt-left",
			render: (data, _type, row) =>
				formatCurrentStock(data, row.minimum_stock),
		},
		{
			data: "minimum_stock",
			name: "ps.minimum_stock",
			type: "string",
			className: "dt-left",
			render: formatStockValue,
		},
		{ 
            data: "sale_price", 
            name: "sale_price", 
            // Utilizamos la función global que garantiza el redondeo a 5 y el formato "₡ 5 000"
            render: (data) => formatCurrency(data) 
        },
		{
			data: "expiration_days",
			name: "expiration_date",
			orderable: false,
			searchable: false,
		},
	];

	// Conditional actions construction using spread operator
	const actions = {
		show: {
			route: MODEL_ROUTES.show,
			func: window.showProduct,
			funcName: "showProduct",
			tooltip: "Ver detalles",
		},
		...(canManageProducts && {
			edit: {
				route: MODEL_ROUTES.edit,
				func: toggleLoadingState,
				funcName: "toggleLoadingState",
				tooltip: `Editar ${MODEL_NAME}`,
			},
			delete: {
				route: MODEL_ROUTES.delete,
				func: window.deleteProduct,
				funcName: "deleteProduct",
				tooltip: `Eliminar ${MODEL_NAME}`,
			},
		}),
	};

	const customButtons = [
		{
			text: "Solo stock bajo",
			href: "javascript:void(0)",
			class: "low-stock-filter-button btn-outline-warning",
			icon: "bi-exclamation-triangle",
			func: window.toggleLowStockFilter,
			funcName: "toggleLowStockFilter",
			params: [".low-stock-filter-button", "low-stock-filter"],
		},
		{
			text: "Próximos a vencer",
			href: "javascript:void(0)",
			class: "expiring-soon-filter-button btn-outline-danger",
			icon: "bi-hourglass-split",
			func: window.toggleExpiringSoonFilter,
			funcName: "toggleExpiringSoonFilter",
			params: [".expiring-soon-filter-button", "expiring-soon-filter"],
		},
	];

	if (canCreateProducts) {
		customButtons.push({
			text: `Crear ${capitalizeSentence(MODEL_NAME)}`,
			href: MODEL_ROUTES.create,
			class: `create-button ${BTN_CLASS_PRIMARY}`,
			icon: "bi-box-seam",
			func: toggleLoadingState,
			funcName: "toggleLoadingState",
			params: [".create-button", "create", true],
		});
	}

	if (showLowStockOnly) {
		setTimeout(() => {
			const $button = $(".low-stock-filter-button");
			$button.removeClass("btn-outline-warning").addClass("btn-warning");
			$(".low-stock-filter-button-text").html(
				`<i class="bi-exclamation-triangle me-2"></i> Mostrar todos`,
			);
		}, 100);
	}

	if (showExpiringOnly) {
		setTimeout(() => {
			const $button = $(".expiring-soon-filter-button");
			$button.removeClass("btn-outline-danger").addClass("btn-danger");
			$(".expiring-soon-filter-button-text").html(
				`<i class="bi-hourglass-split me-2"></i> Mostrar todos`,
			);
		}, 100);
	}

	productsDataTable = CreateNewDataTable(
		"products-table",
		MODEL_ROUTES.index,
		columns,
		actions,
		customButtons,
		{
			ajax: {
				url: MODEL_ROUTES.index,
				data: (d) => {
					if (showLowStockOnly) {
						d.low_stock = 1;
					}
					if (showExpiringOnly) {
						d.expiring_soon = 1;
					}
				},
			},
			columnDefs: [{ target: [-1, -2], columnControl: [] }],
		},
	);
});