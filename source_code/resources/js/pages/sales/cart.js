import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert";

const STORAGE_KEY = "pos_orders_state";

// 1. Estado Global del Carrito
const state = {
	activeOrderId: "order-tab-0001",
	orders: {}, // Formato: { 'order-tab-0001': [...items], 'order-tab-0002': [...items] }
};

// 2. Caché de variables del DOM (Se llenan en la inicialización)
let productsGrid,
	saleDetailsContainer,
	saleTax,
	saleSubtotal,
	saleTotal,
	finalizeSaleButton,
	clearSaleButton;

// 3. Manejo de LocalStorage (Persistencia)
const saveToStorage = () => {
	localStorage.setItem(STORAGE_KEY, JSON.stringify(state.orders));
};

const loadFromStorage = () => {
	const savedOrders = localStorage.getItem(STORAGE_KEY);
	if (savedOrders) {
		state.orders = JSON.parse(savedOrders);
	}
	// Garantizar que la pestaña activa tenga su lista inicializada
	if (!state.orders[state.activeOrderId]) {
		state.orders[state.activeOrderId] = [];
	}
};

// 4. Utilidades UI y Alertas
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

// 5. Renderizado Visual del Carrito
const renderCartItems = () => {
	if (!saleDetailsContainer) return;

	const currentCart = state.orders[state.activeOrderId] || [];

	// Estado vacío
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
		finalizeSaleButton.disabled = true;
		return;
	}

	// Calcular totales y generar HTML
	let subtotal = 0;
	let taxAmount = 0;

	const html = currentCart
		.map((item) => {
			subtotal += item.sub_total;
			taxAmount += item.sub_total * item.applied_tax; // Impuesto según porcentaje

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
	finalizeSaleButton.disabled = false;
};

// 6. Lógica de Carrito (Acciones)
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
		// Validar límite de inventario si aplica (puedes agregar lógica extra aquí)
		existingItem.quantity += 1;
		existingItem.sub_total =
			existingItem.quantity * existingItem.unit_price;
	} else {
		currentCart.push({
			product_id: productId,
			name: name, // Guardamos el nombre para poder dibujarlo en el UI
			quantity: 1,
			unit_price: price,
			applied_tax: tax,
			sub_total: price,
		});
	}

	saveToStorage();
	renderCartItems();
};

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

// 7. Funciones Exportables (Para usar en otros archivos JS)
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

// Obtiene los datos en formato de API para el endpoint /sales
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
		// Estructura lista para enviar a la base de datos
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

// 8. Inicialización Principal
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

	// Cargar historial de LocalStorage al iniciar la página
	loadFromStorage();
	renderCartItems();

	// Event Delegation para agregar productos
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

	// Event Delegation para botones de + / - / eliminar dentro del carrito
	saleDetailsContainer.addEventListener("click", (event) => {
		const actionButton = event.target.closest("button[data-action]");
		if (!actionButton) return;

		const { action, productId } = actionButton.dataset;
		if (productId && cartActions[action]) {
			cartActions[action](productId);
		}
	});

	// Botón de limpiar carrito entero
	clearSaleButton.addEventListener("click", clearActiveCart);
};