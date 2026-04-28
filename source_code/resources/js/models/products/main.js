import { showModelInfo, deleteModel } from "../actions.js";
import { CreateNewDataTable } from "../../utils/datatables.js";
import { capitalizeSentence, toggleLoadingState } from "../../utils/utils.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

// ==================== Constants ====================
const MODEL_NAME = "producto";
const BTN_CLASS_PRIMARY = "btn-primary";

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

const CURRENCY_FORMATTER = {
	format(value) {
		// Usar Intl.NumberFormat para obtener el formato base
		const nf = new Intl.NumberFormat("es-CR", {
			style: "currency",
			currency: "CRC",
			maximumFractionDigits: 0,
		});
		// Reemplazar '₡' seguido de número por '₡ ' (con espacio)
		return nf.format(value).replace(/^([₡])(?=\d)/, "$1 ");
	}
};

// ==================== State Management ====================
// Centralized state object to manage filters and DataTable instance.
const urlParams = new URLSearchParams(window.location.search);
const State = {
	filter: urlParams.get("filter") || null, // Can be 'low_stock', 'expiring_soon' or null
	dataTable: null,
	canCreate:
		($("#products-table").data("can-create-products") ?? "").toString() ===
		"1",
	canManage:
		($("#products-table").data("can-manage-products") ?? "").toString() ===
		"1",
};

// ==================== Global Exports ====================
window.SwalToast = SwalToast;
window.SwalNotificationTypes = SwalNotificationTypes;
window.toggleLoadingState = toggleLoadingState;

// ==================== Formatters ====================
const formatProductType = (type) => PRODUCT_TYPE_LABELS[type] || type || "N/A";
const formatStockValue = (value) => value ?? "N/A";
const formatCurrency = (value) => {
	const amount = Number.parseFloat(value);
	return CURRENCY_FORMATTER.format(Number.isNaN(amount) ? 0 : amount);
};

function formatCurrentStock(currentValue, minimumValue) {
	if (currentValue == null || minimumValue == null) return "N/A";

	const currentStock = Number.parseInt(currentValue, 10);
	const minimumStock = Number.parseInt(minimumValue, 10);

	if (Number.isNaN(currentStock) || Number.isNaN(minimumStock)) return "N/A";

	return currentStock <= minimumStock
		? `<span class="badge text-bg-danger">${currentStock}</span>`
		: currentStock;
}

// ==================== Filter Logic ====================
function setFilter(filterType) {
	// If the same filter is clicked again, it toggles off (sets to null), otherwise it sets the new filter
	State.filter = State.filter === filterType ? null : filterType;

	// Update URL
	const params = new URLSearchParams(window.location.search);
	if (State.filter) {
		params.set("filter", State.filter);
	} else {
		params.delete("filter");
	}

	const nextQuery = params.toString();
	const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ""}`;
	window.history.replaceState({}, "", nextUrl);

	// Update Buttons and Table
	updateFilterUI();
	reloadTable();
}

function updateFilterUI() {
	const isLowStock = State.filter === "low_stock";
	const isExpiring = State.filter === "expiring_soon";

	// Using .html() to prevent losing the icons (<tr>) defined by datatables.js
	$(".low-stock-filter-button")
		.toggleClass("btn-warning", isLowStock)
		.toggleClass("btn-outline-warning", !isLowStock);
	$(".low-stock-filter-button-text").html(
		`<i class="bi-exclamation-triangle me-2"></i> ${isLowStock ? "Mostrar todos" : "Solo stock bajo"}`,
	);

	$(".expiring-soon-filter-button")
		.toggleClass("btn-danger", isExpiring)
		.toggleClass("btn-outline-danger", !isExpiring);
	$(".expiring-soon-filter-button-text").html(
		`<i class="bi-hourglass-split me-2"></i> ${isExpiring ? "Mostrar todos" : "Próximos a vencer"}`,
	);
}

function reloadTable() {
	if (State.dataTable) {
		State.dataTable.ajax.reload(null, true);
		setTimeout(() => {
			State.dataTable.columns.adjust();
			if (State.dataTable.responsive) {
				State.dataTable.responsive.recalc();
			}
		}, 0);
	}
}

// Global functions for actions (show, delete) and filters (low stock, expiring soon)
window.toggleLowStockFilter = () => setFilter("low_stock");
window.toggleExpiringSoonFilter = () => setFilter("expiring_soon");
window.showProduct = (url, anchor) => showModelInfo(url, anchor, MODEL_NAME);
window.deleteProduct = (e) => deleteModel(e, MODEL_NAME);

// ==================== DataTable Initialization ====================
$(() => {
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
			render: formatCurrency 
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
		...(State.canManage && {
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

	if (State.canCreate) {
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

	// Initialize filter UI based on URL parameter on page load
	updateFilterUI();

	State.dataTable = CreateNewDataTable(
		"products-table",
		MODEL_ROUTES.index,
		columns,
		actions,
		customButtons,
		{
			ajax: {
				url: MODEL_ROUTES.index,
				data: (d) => {
					d.low_stock = State.filter === "low_stock" ? 1 : 0;
					d.expiring_soon = State.filter === "expiring_soon" ? 1 : 0;
				},
			},
			columnDefs: [{ target: [-1, -2], columnControl: [] }],
		},
	);

	State.dataTable.on("draw.dt", () => {
		State.dataTable.columns.adjust();
		if (State.dataTable.responsive) State.dataTable.responsive.recalc();
	});
});