import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert";
import { formatCurrency, roundToNearestFive } from "../../utils/utils";

const STORAGE_KEY = "pos_orders_state";

const state = {
	activeOrderId: "order-tab-0001",
	orders: {},
};

let elements = {};

// --- STATE MANAGEMENT ---

const saveToStorage = () => {
	localStorage.setItem(STORAGE_KEY, JSON.stringify(state.orders));
};

const loadFromStorage = () => {
	const savedOrders = localStorage.getItem(STORAGE_KEY);
	if (savedOrders) {
		state.orders = JSON.parse(savedOrders);
	}

	state.orders = Object.fromEntries(
		Object.entries(state.orders).map(([orderId, cart]) => [
			orderId,
			Array.isArray(cart) ? cart.map(normalizeCartItemAmounts) : [],
		]),
	);

	if (!state.orders[state.activeOrderId]) {
		state.orders[state.activeOrderId] = [];
	}
	saveToStorage();
};

const getActiveCart = () => state.orders[state.activeOrderId] || [];

// --- UTILITIES ---

const showError = (errorMessage, consoleErrorMessage) => {
	console.error(consoleErrorMessage);
	SwalToast.fire({
		icon: SwalNotificationTypes.ERROR,
		title: errorMessage,
	});
};

const toFixedNumber = (num) => {
    return Number(Number(num).toFixed(2));
};

/**
 * Calcula y normaliza los montos de un item del carrito.
 * Retorna un objeto NUEVO con los cálculos actualizados.
 */
const normalizeCartItemAmounts = (item) => {
	const quantity = parseInt(item.quantity, 10) || 1;
	
	return {
		...item,
		quantity,
		unit_base_price: toFixedNumber(item.unit_base_price),
		unit_tax_amount: toFixedNumber(item.unit_tax_amount),
		unit_sale_price: toFixedNumber(item.unit_sale_price),
		applied_tax: toFixedNumber(item.applied_tax),
		subtotal_base: toFixedNumber(item.unit_base_price * quantity), 
		subtotal_tax: toFixedNumber(item.unit_tax_amount * quantity), 
		subtotal_sale: toFixedNumber(item.unit_sale_price * quantity), 
	};
};

// --- VALIDATIONS ---

export const validateProductStock = ($productCard) => {
	const hasInventory = $productCard.data("productHasInventory") == 1;
	if (!hasInventory) return true;

	const productStock = parseInt($productCard.data("productStock"), 10);
	if (productStock <= 0) {
		SwalToast.fire({
			icon: SwalNotificationTypes.WARNING,
			title: `El producto "${$productCard.data("productName")}" no tiene suficiente stock.`,
		});
		return false;
	}
	return true;
};

const validateStockForQuantity = ({ hasInventory, availableStock, desiredQuantity, productName }) => {
	if (!hasInventory) return true;
	if (availableStock <= 0) {
		SwalToast.fire({ icon: SwalNotificationTypes.WARNING, title: `El producto "${productName}" no tiene stock disponible.` });
		return false;
	}
	if (desiredQuantity > availableStock) {
		SwalToast.fire({ icon: SwalNotificationTypes.WARNING, title: `Stock insuficiente para "${productName}". Disponible: ${availableStock}.` });
		return false;
	}
	return true;
};

// --- UI UPDATES ---

export const syncFinalizeSaleButtonState = () => {
	if (!elements.finalizeSaleButton || !elements.clearSaleButton) return;
	const hasProducts = getActiveCart().length > 0;
	elements.finalizeSaleButton.prop("disabled", !hasProducts);
	elements.clearSaleButton.prop("disabled", !hasProducts);
};

const createCartItemHTML = (item) => {
	return `
    <div class="d-flex flex-row justify-content-between align-items-center gap-2 w-100" data-cart-item-id="${item.product_id}">
        <div class="d-flex flex-column text-start overflow-hidden flex-grow-1">
            <span class="fw-bold text-truncate text-body" style="font-size: 0.95rem;" title="${item.name}">${item.name}</span>
            <span class="text-body-secondary fw-medium" style="font-size: 0.85rem;">
                ${formatCurrency(item.unit_base_price)} c/u
            </span>
        </div>
        <div class="d-flex flex-row align-items-center justify-content-end gap-2 flex-shrink-0">
            <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="decrease" data-product-id="${item.product_id}" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                <i class="bi bi-dash fs-6"></i>
            </button>
            <input type="number" class="form-control text-center fw-semibold text-body quantity-input px-1 py-0 border-0" data-product-id="${item.product_id}" value="${item.quantity}" min="1" style="width: 38px; background-color: transparent;">
            <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="increase" data-product-id="${item.product_id}" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                <i class="bi bi-plus fs-6"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger d-flex align-items-center justify-content-center rounded-2 ms-1" data-action="remove" data-product-id="${item.product_id}" style="width: 28px; height: 28px;">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
	`;
};

const renderCartItems = () => {
	if (!elements.saleDetailsContainer || elements.saleDetailsContainer.length === 0) return;

	const currentCart = getActiveCart();

	if (currentCart.length === 0) {
		elements.saleDetailsContainer.html(`
            <div class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
                <i class="bi bi-bag fs-1 mb-2"></i>
                <p>Selecciona un producto para agregarlo a la orden</p>
            </div>
        `);
		elements.saleTax.text("₡ 0");
		elements.saleSubtotal.text("₡ 0");
		elements.saleTotal.text("₡ 0");
		syncFinalizeSaleButtonState();
		return;
	}

	let finalSubtotalBase = 0;
	let finalTotalTax = 0;
	let finalTotalSale = 0;

	const html = currentCart.map((item) => {
		finalSubtotalBase += item.subtotal_base;
		finalTotalTax += item.subtotal_tax;
		finalTotalSale += item.subtotal_sale;

		return createCartItemHTML(item);
	}).join("");

	elements.saleDetailsContainer.html(html);
	
	elements.saleSubtotal.text(formatCurrency(finalSubtotalBase));
	elements.saleTax.text(formatCurrency(finalTotalTax));
	elements.saleTotal.text(formatCurrency(finalTotalSale));

	syncFinalizeSaleButtonState();
};

// --- CART ACTIONS ---

const updateItemInCart = (productId, newQuantity) => {
    const currentCart = getActiveCart();
    const index = currentCart.findIndex(item => item.product_id == productId);
    
    if (index !== -1) {
        // Creamos una copia actualizada y la reemplazamos en el arreglo original
        const updatedItem = { ...currentCart[index], quantity: newQuantity };
        currentCart[index] = normalizeCartItemAmounts(updatedItem);
        
        saveToStorage();
        renderCartItems();
    }
};

const addToCart = (productId, $productCard) => {
	const name = $productCard.data("productName");
	const hasInventory = $productCard.data("productHasInventory") == 1;
	const availableStock = parseInt($productCard.data("productStock"), 10) || 0;
    
    const unitBasePrice = parseFloat($productCard.data("productBasePrice")) || 0;
    const unitTaxAmount = parseFloat($productCard.data("productTaxAmount")) || 0;
    const unitSalePrice = parseFloat($productCard.data("productSalePrice")) || 0;
    const taxPercentage = parseFloat($productCard.data("productTaxPercentage")) || 0;

	const currentCart = getActiveCart();
	let existingItem = currentCart.find((item) => item.product_id == productId);
	const desiredQuantity = existingItem ? existingItem.quantity + 1 : 1;

	if (!validateStockForQuantity({ hasInventory, availableStock, desiredQuantity, productName: name })) return;

	if (existingItem) {
        updateItemInCart(productId, desiredQuantity);
	} else {
		currentCart.push(normalizeCartItemAmounts({
			product_id: productId,
			name,
			quantity: 1,
			unit_base_price: unitBasePrice,
			unit_tax_amount: unitTaxAmount,
			unit_sale_price: unitSalePrice,
			applied_tax: taxPercentage,
			has_inventory: hasInventory,
			available_stock: availableStock,
		}));
        saveToStorage();
        renderCartItems();
	}
};

const cartActions = {
	decrease: (productId) => {
		const item = getActiveCart().find((i) => i.product_id == productId);
		if (item && item.quantity > 1) {
			updateItemInCart(productId, item.quantity - 1);
		}
	},
	increase: (productId) => {
		const item = getActiveCart().find((i) => i.product_id == productId);
		if (!item) return;
		if (!validateStockForQuantity({
			hasInventory: item.has_inventory, availableStock: item.available_stock, desiredQuantity: item.quantity + 1, productName: item.name,
		})) return;

		updateItemInCart(productId, item.quantity + 1);
	},
	update: (productId, newQuantityStr) => {
		const item = getActiveCart().find((i) => i.product_id == productId);
		if (!item) return;

		let newQuantity = parseInt(newQuantityStr, 10);
		if (isNaN(newQuantity) || newQuantity < 1) {
			SwalToast.fire({ icon: SwalNotificationTypes.WARNING, title: "La cantidad mínima debe ser 1." });
			renderCartItems();
			return;
		}

		if (!validateStockForQuantity({
			hasInventory: item.has_inventory, availableStock: item.available_stock, desiredQuantity: newQuantity, productName: item.name,
		})) {
			renderCartItems();
			return;
		}

		updateItemInCart(productId, newQuantity);
	},
	remove: (productId) => {
		state.orders[state.activeOrderId] = getActiveCart().filter((i) => i.product_id != productId);
		saveToStorage();
		renderCartItems();
	},
};

// --- EXPORTED APIS ---

export const clearActiveCart = () => {
	state.orders[state.activeOrderId] = [];
	saveToStorage();
	renderCartItems();
};

export const switchActiveOrder = (newOrderId) => {
	state.activeOrderId = newOrderId;
	if (!state.orders[state.activeOrderId]) state.orders[state.activeOrderId] = [];
	saveToStorage();
	renderCartItems();
};

export const deleteOrderCart = (orderId) => {
	delete state.orders[orderId];
	saveToStorage();
};

export const getActiveSaleData = () => {
	const currentCart = getActiveCart();
	let exactSaleTotal = 0; 

	const sale_details = currentCart.map((item) => {
		exactSaleTotal += item.subtotal_sale;

		return {
			product_id: parseInt(item.product_id, 10),
			quantity: parseInt(item.quantity, 10),
			unit_price: item.unit_base_price, 
			applied_tax: item.applied_tax,
			sub_total: item.subtotal_base, 
		};
	});

	const receipt_details = currentCart.map((item) => {
		return {
			product_id: parseInt(item.product_id, 10),
			name: item.name,
			quantity: parseInt(item.quantity, 10),
            // REDONDEO CRÍTICO: Usamos Math.round para que la factura sume enteros exactos
			unit_price: Math.round(item.unit_sale_price),
			applied_tax: item.applied_tax,
			sub_total: Math.round(item.subtotal_base),
			tax_amount: Math.round(item.subtotal_tax),
			total: Math.round(item.subtotal_sale),
		};
	});

	return {
		sale_details,
		receipt_details,
		total: roundToNearestFive(exactSaleTotal), 
	};
};

export const initializeSalesCart = () => {
	elements = {
		productsGrid: $("#products-grid"),
		saleDetailsContainer: $("#sale-details"),
		saleTax: $("#sale-tax"),
		saleSubtotal: $("#sale-subtotal"),
		saleTotal: $("#sale-total"),
		finalizeSaleButton: $("#finalize-sale-button"),
		clearSaleButton: $("#clear-sale-btn"),
	};

	if (Object.values(elements).some(($el) => $el.length === 0)) {
		showError(
			"No se encontraron los elementos necesarios para inicializar el carrito.",
			"Error al inicializar carrito. Faltan elementos críticos del DOM.",
		);
		return;
	}

	loadFromStorage();
	renderCartItems();

	// Event Delegation
	elements.productsGrid.on("click", ".product-card", function () {
		const $productCard = $(this);
		const productId = $productCard.data("productId");
		if (!productId) return;
		if (validateProductStock($productCard)) addToCart(productId, $productCard);
	});

	elements.saleDetailsContainer.on("click", "button[data-action]", function () {
		const action = $(this).data("action");
		const productId = $(this).data("productId");
		if (productId && cartActions[action]) cartActions[action](productId);
	});

	elements.saleDetailsContainer.on("change", ".quantity-input", function () {
		const productId = $(this).data("productId");
		const newQuantity = $(this).val();
		if (productId) cartActions.update(productId, newQuantity);
	});

	elements.clearSaleButton.on("click", clearActiveCart);
};