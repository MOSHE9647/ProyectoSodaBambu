import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert";

const STORAGE_KEY = "pos_orders_state";

// Global cart state
const state = {
	activeOrderId: "order-tab-0001",
	orders: {}, // Format: { 'order-tab-0001': [...items], 'order-tab-0002': [...items] }
};

// DOM variable cache (filled during initialization)
let productsGrid,
	saleDetailsContainer,
	saleTax,
	saleSubtotal,
	saleTotal,
	finalizeSaleButton,
	clearSaleButton;

// LocalStorage handling (persistence)
const saveToStorage = () => {
	localStorage.setItem(STORAGE_KEY, JSON.stringify(state.orders));
};

const loadFromStorage = () => {
	const savedOrders = localStorage.getItem(STORAGE_KEY);
	if (savedOrders) {
		state.orders = JSON.parse(savedOrders);
	}
	// Ensure the active tab has an initialized item list
	if (!state.orders[state.activeOrderId]) {
		state.orders[state.activeOrderId] = [];
	}
};

// UI utilities and alerts
const showError = (errorMessage, consoleErrorMessage) => {
	console.error(consoleErrorMessage);
	SwalToast.fire({
		icon: SwalNotificationTypes.ERROR,
		title: errorMessage,
	});
};

const formatCurrency = (amount) => {
	const numericAmount = Number(amount) || 0;
	const roundedAmount = Number(numericAmount.toFixed(2));

	return `₡ ${roundedAmount.toLocaleString("es-CR", {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	})}`;
};

/**
 * Synchronizes the "finalize sale" button state with the active cart content.
 *
 * The button is enabled only when the active order has at least one item.
 *
 * @returns {void}
 */
export const syncFinalizeSaleButtonState = () => {
	if (!finalizeSaleButton) {
		return;
	}

	const hasProductsInActiveCart =
		(state.orders[state.activeOrderId] || []).length > 0;
	finalizeSaleButton.disabled = !hasProductsInActiveCart;
};


/**
 * Renders the active order's cart items into the sales detail container and updates
 * summary totals (subtotal, tax, and total) in the UI.
 *
 * - If there is no target container, the function exits early.
 * - If the active cart is empty, it renders an empty-state message, resets totals to zero,
 *   and disables the finalize-sale button.
 * - If the cart has items, it:
 *   1. Builds the cart item rows with quantity controls (increase/decrease/remove).
 *   2. Calculates subtotal from each item's `sub_total`.
 *   3. Calculates tax as `sub_total * applied_tax` per item.
 *   4. Updates subtotal, tax, and grand total fields using currency formatting.
 *   5. Enables the finalize-sale button.
 *
 * @function renderCartItems
 * @returns {void}
 */
const renderCartItems = () => {
	if (!saleDetailsContainer) return;

	const currentCart = state.orders[state.activeOrderId] || [];

	// Empty state
	if (currentCart.length === 0) {
		saleDetailsContainer.innerHTML = `
            <div class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
                <i class="bi bi-bag fs-1 mb-2"></i>
                <p>Selecciona un producto para agregarlo a la orden</p>
            </div>
        `;
		saleTax.textContent = "₡ 0";
		saleSubtotal.textContent = "₡ 0";
		saleTotal.textContent = "₡ 0";
		syncFinalizeSaleButtonState();
		return;
	}

	// Calculate totals and build HTML
	let subtotal = 0;
	let taxAmount = 0;

	const html = currentCart
		.map((item) => {
			subtotal += item.sub_total;
			taxAmount += item.sub_total * item.applied_tax; // Tax amount based on percentage

			return `
            <div class="d-flex flex-row justify-content-between align-items-center gap-2 w-100" data-cart-item-id="${item.product_id}">
				<div class="d-flex flex-column text-start overflow-hidden flex-grow-1">
					<span class="fw-bold text-truncate text-body" style="font-size: 0.95rem;" title="${item.name}">${item.name}</span>
					<span class="text-body-secondary fw-medium" style="font-size: 0.85rem;">${formatCurrency(item.unit_price)} c/u</span>
				</div>
				<div class="d-flex flex-row align-items-center justify-content-end gap-2 flex-shrink-0">
					<button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="decrease" data-product-id="${item.product_id}" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
						<i class="bi bi-dash fs-6"></i>
					</button>
					<span class="text-center fw-semibold d-inline-block text-body" style="min-width: 18px; font-size: 0.95rem;">${item.quantity}</span>
					<button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="increase" data-product-id="${item.product_id}" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
						<i class="bi bi-plus fs-6"></i>
					</button>
					<button type="button" class="btn btn-sm btn-danger d-flex align-items-center justify-content-center rounded-2 ms-1" data-action="remove" data-product-id="${item.product_id}" style="width: 28px; height: 28px;">
						<i class="bi bi-trash"></i>
					</button>
				</div>
			</div>
        `;
		})
		.join("");

	saleDetailsContainer.innerHTML = html;
	saleSubtotal.textContent = formatCurrency(subtotal);
	saleTax.textContent = formatCurrency(taxAmount);
	saleTotal.textContent = formatCurrency(subtotal + taxAmount);
	syncFinalizeSaleButtonState();
};


/**
 * Adds a product to the currently active order cart.
 *
 * Reads product metadata from the provided product card element, including
 * product name, unit price, and tax percentage, then:
 * - Increments quantity and subtotal if the product is already in the active cart.
 * - Creates and appends a new cart item if it is not present.
 *
 * After updating the cart state, it persists data to storage and refreshes
 * the cart UI rendering.
 *
 * @param {number|string} productId - Unique identifier of the product to add.
 * @param {HTMLElement} productCard - DOM element containing product metadata in dataset attributes:
 *   `data-product-name`, `data-product-price`, and `data-product-tax-percentage`.
 * @returns {void}
 */
const addToCart = (productId, productCard) => {
	const name = productCard.dataset.productName;
	const price = parseFloat(
		productCard.dataset.productPrice.replace(".", ",").replace(",", "."),
	);
	const tax = parseFloat(
		productCard.dataset.productTaxPercentage.replace(".", ",").replace(",", "."),
	);

	const currentCart = state.orders[state.activeOrderId];
	const existingItem = currentCart.find(
		(item) => item.product_id === productId,
	);

	if (existingItem) {
		// Validate inventory limit if needed (extra logic can be added here)
		existingItem.quantity += 1;
		existingItem.sub_total =
			existingItem.quantity * existingItem.unit_price;
	} else {
		currentCart.push({
			product_id: productId,
			name: name, // Keep name to render it in the UI
			quantity: 1,
			unit_price: price,
			applied_tax: tax,
			sub_total: price,
		});
	}

	saveToStorage();
	renderCartItems();
};

/**
 * Collection of cart mutation handlers for the currently active order.
 *
 * @type {{
 *   decrease: (productId: number|string) => void,
 *   increase: (productId: number|string) => void,
 *   remove: (productId: number|string) => void
 * }}
 *
 * @property {(productId: number|string) => void} decrease
 * Decreases the quantity of a product in the active cart by 1 (minimum quantity is 1).
 * Recalculates the item's `sub_total`, persists changes, and re-renders cart items.
 *
 * @property {(productId: number|string) => void} increase
 * Increases the quantity of a product in the active cart by 1.
 * Recalculates the item's `sub_total`, persists changes, and re-renders cart items.
 *
 * @property {(productId: number|string) => void} remove
 * Removes a product from the active cart by `product_id`,
 * then persists changes and re-renders cart items.
 */
const cartActions = {
	decrease: (productId) => {
		const currentCart = state.orders[state.activeOrderId];
		const item = currentCart.find((i) => i.product_id === productId);
		if (item && item.quantity > 1) {
			item.quantity -= 1;
			item.sub_total = item.quantity * item.unit_price;
			saveToStorage();
			renderCartItems();
		}
	},
	increase: (productId) => {
		const currentCart = state.orders[state.activeOrderId];
		const item = currentCart.find((i) => i.product_id === productId);
		if (item) {
			item.quantity += 1;
			item.sub_total = item.quantity * item.unit_price;
			saveToStorage();
			renderCartItems();
		}
	},
	remove: (productId) => {
		state.orders[state.activeOrderId] = state.orders[
			state.activeOrderId
		].filter((i) => i.product_id !== productId);
		saveToStorage();
		renderCartItems();
	},
};

/**
 * Clears all items from the currently active order in the cart.
 *
 * This function resets the active order's item list to an empty array,
 * persists the updated state to storage, and re-renders the cart UI.
 *
 * @returns {void}
 */
export const clearActiveCart = () => {
	state.orders[state.activeOrderId] = [];
	saveToStorage();
	renderCartItems();
};

/**
 * Switches the currently active order to the provided order ID.
 * If the target order does not exist in state, it initializes it as an empty cart.
 * Then persists the updated state and re-renders cart items in the UI.
 *
 * @param {string|number} newOrderId - The identifier of the order to activate.
 * @returns {void}
 */
export const switchActiveOrder = (newOrderId) => {
	state.activeOrderId = newOrderId;
	if (!state.orders[state.activeOrderId]) {
		state.orders[state.activeOrderId] = [];
	}
	saveToStorage();
	renderCartItems();
};

/**
 * Removes an order from the cart state by its identifier and persists the updated state to storage.
 *
 * @param {string|number} orderId - Unique identifier of the order to remove from the cart.
 * @returns {void}
 */
export const deleteOrderCart = (orderId) => {
	delete state.orders[orderId];
	saveToStorage();
};

/**
 * Builds the payload for the currently active sale order.
 *
 * Retrieves the active cart from the application state, calculates the total amount
 * including applied tax per item, and returns a database-ready object containing
 * line-item details and a formatted total value.
 *
 * @returns {{
 *   sale_details: Array<{
 *     product_id: number|string,
 *     quantity: number,
 *     unit_price: number,
 *     applied_tax: number,
 *     sub_total: number
 *   }>,
 *   total: string
 * }} Sale data for the active order, where `total` is formatted to two decimal places.
 */
export const getActiveSaleData = () => {
	const currentCart = state.orders[state.activeOrderId] || [];
	const total = currentCart.reduce(
		(sum, item) =>
			sum + item.sub_total + (item.sub_total * item.applied_tax),
		0,
	);

	const numericTotalAmount = Number(total) || 0;
	const formattedTotal = numericTotalAmount.toFixed(2);

	return {
		// Structure ready to be sent to the database
		sale_details: currentCart.map((item) => ({
			product_id: item.product_id,
			quantity: item.quantity,
			unit_price: item.unit_price,
			applied_tax: item.applied_tax,
			sub_total: item.sub_total,
		})),
		total: formattedTotal,
	};
};

/**
 * Initializes the sales cart UI by binding required DOM elements, restoring persisted cart data,
 * rendering current cart items, and registering all cart-related event listeners.
 *
 * This function:
 * - Retrieves and validates critical DOM nodes used by the cart page.
 * - Displays an error and aborts initialization if any required element is missing.
 * - Loads cart state from storage and renders cart items on startup.
 * - Adds delegated click handling on the products grid to add items to the cart.
 * - Adds delegated click handling in the cart details area for quantity and removal actions.
 * - Binds the "clear cart" button to remove all active cart items.
 *
 * @function initializeSalesCart
 * @returns {void} Does not return a value.
 */
export const initializeSalesCart = () => {
	productsGrid = document.getElementById("products-grid");
	saleDetailsContainer = document.getElementById("sale-details");
	saleTax = document.getElementById("sale-tax");
	saleSubtotal = document.getElementById("sale-subtotal");
	saleTotal = document.getElementById("sale-total");
	finalizeSaleButton = document.getElementById("finalize-sale-button");
	clearSaleButton = document.getElementById("clear-sale-btn");

	if (
		!productsGrid ||
		!saleDetailsContainer ||
		!saleTax ||
		!saleSubtotal ||
		!saleTotal ||
		!finalizeSaleButton ||
		!clearSaleButton
	) {
		showError(
			"No se encontraron los elementos necesarios para inicializar el carrito.",
			"Error al inicializar carrito. Faltan elementos críticos del DOM.",
		);
		return;
	}

	// Load LocalStorage history on page initialization
	loadFromStorage();
	renderCartItems();

	// Event delegation to add products
	productsGrid.addEventListener("click", (event) => {
		const productCard = event.target.closest(".product-card");
		if (!productCard || !productsGrid.contains(productCard)) return;

		const productId = productCard.dataset.productId;
		if (!productId) {
			return showError(
				"No se pudo agregar el producto al carrito.",
				"No se encontró el ID del producto.",
			);
		}

		addToCart(productId, productCard);
	});

	// Event delegation for + / - / remove buttons inside the cart
	saleDetailsContainer.addEventListener("click", (event) => {
		const actionButton = event.target.closest("button[data-action]");
		if (!actionButton) return;

		const { action, productId } = actionButton.dataset;
		if (productId && cartActions[action]) {
			cartActions[action](productId);
		}
	});

	// Clear entire cart button
	clearSaleButton.addEventListener("click", clearActiveCart);
};