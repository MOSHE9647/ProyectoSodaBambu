import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert";

const STORAGE_KEY = "pos_orders_state";

// Global cart state
const state = {
	activeOrderId: "order-tab-0001",
	orders: {},
};

// DOM variable cache
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
	if (!state.orders[state.activeOrderId]) {
		state.orders[state.activeOrderId] = [];
	}
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

// Optimized Currency Formatter using native Intl API
const currencyFormatter = new Intl.NumberFormat("es-CR", {
	style: "currency",
	currency: "CRC",
	minimumFractionDigits: 2,
	maximumFractionDigits: 2,
});

export const formatCurrency = (amount) =>
	currencyFormatter.format(Number(amount) || 0);

// Helper to safely parse localized string numbers if needed
const parsePrice = (value) => parseFloat(value?.replace(/,/g, ".") || 0);

// --- VALIDATIONS ---

export const validateProductStock = (productCard) => {
	const hasInventory = productCard.dataset.productHasInventory === "1";
	if (!hasInventory) return true;

	const productStock = parseInt(productCard.dataset.productStock, 10);
	if (productStock <= 0) {
		SwalToast.fire({
			icon: SwalNotificationTypes.WARNING,
			title: `El producto "${productCard.dataset.productName}" no tiene suficiente stock.`,
		});
		return false;
	}
	return true;
};

const validateStockForQuantity = ({
	hasInventory,
	availableStock,
	desiredQuantity,
	productName,
}) => {
	if (!hasInventory) return true;

	if (availableStock <= 0) {
		SwalToast.fire({
			icon: SwalNotificationTypes.WARNING,
			title: `El producto "${productName}" no tiene stock disponible.`,
		});
		return false;
	}

	if (desiredQuantity > availableStock) {
		SwalToast.fire({
			icon: SwalNotificationTypes.WARNING,
			title: `Stock insuficiente para "${productName}". Disponible: ${availableStock}.`,
		});
		return false;
	}
	return true;
};

// --- UI UPDATES ---

export const syncFinalizeSaleButtonState = () => {
	if (!elements.finalizeSaleButton || !elements.clearSaleButton) return;

	const hasProducts = getActiveCart().length > 0;
	elements.finalizeSaleButton.disabled = !hasProducts;
	elements.clearSaleButton.disabled = !hasProducts;
};

const createCartItemHTML = (item) => `
    <div class="d-flex flex-row justify-content-between align-items-center gap-2 w-100" data-cart-item-id="${item.product_id}">
        <div class="d-flex flex-column text-start overflow-hidden flex-grow-1">
            <span class="fw-bold text-truncate text-body" style="font-size: 0.95rem;" title="${item.name}">${item.name}</span>
            <span class="text-body-secondary fw-medium" style="font-size: 0.85rem;">${formatCurrency(item.unit_price)} c/u</span>
        </div>
        <div class="d-flex flex-row align-items-center justify-content-end gap-2 flex-shrink-0">
            <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="decrease" data-product-id="${item.product_id}" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                <i class="bi bi-dash fs-6"></i>
            </button>
            
            <input type="number" 
				class="form-control text-center fw-semibold text-body quantity-input px-1 py-0 border-0" 
				data-product-id="${item.product_id}" 
				value="${item.quantity}" 
				min="1"
				style="width: 38px; background-color: transparent;">

            <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="increase" data-product-id="${item.product_id}" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                <i class="bi bi-plus fs-6"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger d-flex align-items-center justify-content-center rounded-2 ms-1" data-action="remove" data-product-id="${item.product_id}" style="width: 28px; height: 28px;">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
`;

const renderCartItems = () => {
	if (!elements.saleDetailsContainer) return;

	const currentCart = getActiveCart();

	if (currentCart.length === 0) {
		elements.saleDetailsContainer.innerHTML = `
            <div class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
                <i class="bi bi-bag fs-1 mb-2"></i>
                <p>Selecciona un producto para agregarlo a la orden</p>
            </div>
        `;
		elements.saleTax.textContent = "₡ 0,00";
		elements.saleSubtotal.textContent = "₡ 0,00";
		elements.saleTotal.textContent = "₡ 0,00";
		syncFinalizeSaleButtonState();
		return;
	}

	let subtotal = 0;
	let taxAmount = 0;

	const html = currentCart
		.map((item) => {
			subtotal += item.sub_total;
			taxAmount += item.sub_total * item.applied_tax;
			return createCartItemHTML(item);
		})
		.join("");

	elements.saleDetailsContainer.innerHTML = html;
	elements.saleSubtotal.textContent = formatCurrency(subtotal);
	elements.saleTax.textContent = formatCurrency(taxAmount);
	elements.saleTotal.textContent = formatCurrency(subtotal + taxAmount);

	syncFinalizeSaleButtonState();
};

// --- CART ACTIONS ---

const addToCart = (productId, productCard) => {
	const name = productCard.dataset.productName;
	const price = parsePrice(productCard.dataset.productPrice);
	const tax = parsePrice(productCard.dataset.productTaxPercentage);
	const hasInventory = productCard.dataset.productHasInventory === "1";
	const availableStock = parseInt(productCard.dataset.productStock, 10) || 0;

	const currentCart = getActiveCart();
	let existingItem = currentCart.find(
		(item) => item.product_id === productId,
	);

	const desiredQuantity = existingItem ? existingItem.quantity + 1 : 1;

	if (
		!validateStockForQuantity({
			hasInventory,
			availableStock,
			desiredQuantity,
			productName: name,
		})
	) {
		return;
	}

	if (existingItem) {
		existingItem.has_inventory = hasInventory;
		existingItem.available_stock = availableStock;
		existingItem.quantity += 1;
		existingItem.sub_total =
			existingItem.quantity * existingItem.unit_price;
	} else {
		currentCart.push({
			product_id: productId,
			name,
			quantity: 1,
			unit_price: price,
			applied_tax: tax,
			has_inventory: hasInventory,
			available_stock: availableStock,
			sub_total: price,
		});
	}

	saveToStorage();
	renderCartItems();
};

const cartActions = {
	decrease: (productId) => {
		const item = getActiveCart().find((i) => i.product_id === productId);
		if (item && item.quantity > 1) {
			item.quantity -= 1;
			item.sub_total = item.quantity * item.unit_price;
			saveToStorage();
			renderCartItems();
		}
	},
	increase: (productId) => {
		const item = getActiveCart().find((i) => i.product_id === productId);
		if (!item) return;

		if (
			!validateStockForQuantity({
				hasInventory: item.has_inventory,
				availableStock: item.available_stock,
				desiredQuantity: item.quantity + 1,
				productName: item.name,
			})
		) {
			return;
		}

		item.quantity += 1;
		item.sub_total = item.quantity * item.unit_price;
		saveToStorage();
		renderCartItems();
	},
	update: (productId, newQuantityStr) => {
		const item = getActiveCart().find((i) => i.product_id === productId);
		if (!item) return;

		let newQuantity = parseInt(newQuantityStr, 10);

		// Avoid NaN and enforce minimum quantity of 1
		if (isNaN(newQuantity) || newQuantity < 1) {
			SwalToast.fire({
				icon: SwalNotificationTypes.WARNING,
				title: "La cantidad mínima debe ser 1.",
			});
			// Si es inválido, re-renderizamos para restaurar el valor anterior en el input
			renderCartItems();
			return;
		}

		// Validate stock for the new desired quantity before updating
		if (
			!validateStockForQuantity({
				hasInventory: item.has_inventory,
				availableStock: item.available_stock,
				desiredQuantity: newQuantity,
				productName: item.name,
			})
		) {
			// If validation fails, re-render to reset the input to the last valid quantity
			renderCartItems();
			return;
		}

		// If validation passes, update the quantity and subtotal
		item.quantity = newQuantity;
		item.sub_total = item.quantity * item.unit_price;
		saveToStorage();
		renderCartItems();
	},
	remove: (productId) => {
		state.orders[state.activeOrderId] = getActiveCart().filter(
			(i) => i.product_id !== productId,
		);
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
	if (!state.orders[state.activeOrderId]) {
		state.orders[state.activeOrderId] = [];
	}
	saveToStorage();
	renderCartItems();
};

export const deleteOrderCart = (orderId) => {
	delete state.orders[orderId];
	saveToStorage();
};

export const getActiveSaleData = () => {
	const currentCart = getActiveCart();
	const total = currentCart.reduce(
		(sum, item) => sum + item.sub_total + item.sub_total * item.applied_tax,
		0,
	);

	return {
		sale_details: currentCart.map(
			({ product_id, quantity, unit_price, applied_tax, sub_total }) => ({
				product_id,
				quantity,
				unit_price,
				applied_tax,
				sub_total,
			}),
		),
		total: (Number(total) || 0).toFixed(2),
	};
};

export const initializeSalesCart = () => {
	elements = {
		productsGrid: document.getElementById("products-grid"),
		saleDetailsContainer: document.getElementById("sale-details"),
		saleTax: document.getElementById("sale-tax"),
		saleSubtotal: document.getElementById("sale-subtotal"),
		saleTotal: document.getElementById("sale-total"),
		finalizeSaleButton: document.getElementById("finalize-sale-button"),
		clearSaleButton: document.getElementById("clear-sale-btn"),
	};

	if (Object.values(elements).some((el) => !el)) {
		showError(
			"No se encontraron los elementos necesarios para inicializar el carrito.",
			"Error al inicializar carrito. Faltan elementos críticos del DOM.",
		);
		return;
	}

	loadFromStorage();
	renderCartItems();

	elements.productsGrid.addEventListener("click", (event) => {
		const productCard = event.target.closest(".product-card");
		if (!productCard || !elements.productsGrid.contains(productCard))
			return;

		const productId = productCard.dataset.productId;
		if (!productId) {
			return showError(
				"No se pudo agregar el producto al carrito.",
				"No se encontró el ID del producto.",
			);
		}

		if (validateProductStock(productCard)) {
			addToCart(productId, productCard);
		}
	});

	elements.saleDetailsContainer.addEventListener("click", (event) => {
		const actionButton = event.target.closest("button[data-action]");
		if (!actionButton) return;

		const { action, productId } = actionButton.dataset;
		if (productId && cartActions[action]) {
			cartActions[action](productId);
		}
	});

	elements.clearSaleButton.addEventListener("click", clearActiveCart);

	elements.saleDetailsContainer.addEventListener("change", (event) => {
		if (event.target.classList.contains("quantity-input")) {
			const productId = event.target.dataset.productId;
			const newQuantity = event.target.value;

			if (productId) {
				cartActions.update(productId, newQuantity);
			}
		}
	});

	elements.clearSaleButton.addEventListener("click", clearActiveCart);
};