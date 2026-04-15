import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert";

// Función de error aislada
const showError = (errorMessage, consoleErrorMessage) => {
	console.error(consoleErrorMessage);
	SwalToast.fire({
		icon: SwalNotificationTypes.ERROR,
		title: errorMessage,
	});
};

export const initializeSalesCart = () => {
	// 1. Caché de elementos críticos del DOM
	const productsGrid = document.getElementById("products-grid");
	const saleDetailsContainer = document.getElementById("sale-details");
	const saleTax = document.getElementById("sale-tax");
	const saleSubtotal = document.getElementById("sale-subtotal");
	const saleTotal = document.getElementById("sale-total");
	const finalizeSaleButton = document.getElementById("finalize-sale-btn");
	const clearSaleButton = document.getElementById("clear-sale-btn");

	// Cláusula de guarda
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

	// 2. Estado local
	const state = {
		items: [],
	};

	// 3. Utilidades y Validaciones

	// formatCurrency solo redondea para la VISTA (UI), no altera el estado interno
	const formatCurrency = (amount) =>
		`₡ ${Math.round(amount).toLocaleString("es-CR")}`;

	const escapeHtml = (value) =>
		String(value ?? "")
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#39;");

	const parsePrice = (rawPrice) => {
		// Mantiene el valor original de la BD. Solo cambia comas por puntos por seguridad
		// asumiendo que el backend envía formatos crudos como "1500" o "1500.50"
		const cleanedPrice = String(rawPrice || "")
			.replace(/[^\d,-]/g, "")
			.replace(",", ".");

		const value = Number(cleanedPrice);
		return Number.isFinite(value) ? value : 0;
	};

	const normalizeTaxPercentage = (rawTaxPercentage) => {
		const cleanString = String(rawTaxPercentage || "0")
			.replace(/[^\d,.]/g, "")
			.replace(",", ".");

		const value = Number(cleanString);

		// Confiamos ciegamente en el decimal que viene de la BD (ej. 0.13)
		return Number.isFinite(value) && value > 0 ? value : 0;
	};

	const getProductFromCard = (card) => {
		const id = card.dataset.productId;
		const name =
			card.dataset.productName ||
			card.querySelector(".product-name")?.textContent?.trim() ||
			"Producto";

		const price = parsePrice(
			card.dataset.productPrice ||
				card.querySelector(".product-price")?.textContent,
		);
		
		if (!id || price === 0) return null;

		return {
			id,
			name,
			price,
			taxPercentage: normalizeTaxPercentage(
				card.dataset.productTaxPercentage,
			),
			hasInventory: card.dataset.productHasInventory === "1",
			availableStock: Number(card.dataset.productStock || 0),
		};
	};

	// Alerta unificada de inventario
	const hasEnoughStock = (item, currentQuantity) => {
		if (item.hasInventory && currentQuantity >= item.availableStock) {
			SwalToast.fire({
				icon: SwalNotificationTypes.WARNING,
				title: "No hay más unidades disponibles para este producto.",
			});
			return false;
		}
		return true;
	};

	// 4. Lógica de Renderizado y Componentes HTML
	const updateTotals = () => {
		// Las operaciones se hacen con los decimales crudos del estado para máxima precisión
		const subtotal = state.items.reduce(
			(sum, item) => sum + item.price * item.quantity,
			0,
		);
		const tax = state.items.reduce(
			(sum, item) =>
				sum + item.price * item.quantity * item.taxPercentage,
			0,
		);
		const total = subtotal + tax;

		saleTax.textContent = formatCurrency(tax);
		saleSubtotal.textContent = formatCurrency(subtotal);
		saleTotal.textContent = formatCurrency(total);

		const isCartEmpty = state.items.length === 0;
		finalizeSaleButton.disabled = isCartEmpty;
		clearSaleButton.disabled = isCartEmpty;
	};

	const emptyCartHTML = `
        <div class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
            <i class="bi bi-bag fs-1 mb-2"></i>
            <p>Selecciona un producto para agregarlo a la orden</p>
        </div>
    `;

	const createCartItemHTML = (item) => {
		const safeName = escapeHtml(item.name);

		return `
        <div class="d-flex flex-row justify-content-between align-items-center gap-2 w-100" data-cart-item-id="${item.id}">
            <div class="d-flex flex-column text-start overflow-hidden flex-grow-1">
				<span class="fw-bold text-truncate text-body" style="font-size: 0.95rem;" title="${safeName}">${safeName}</span>
                <span class="text-body-secondary fw-medium" style="font-size: 0.85rem;">${formatCurrency(item.price)} c/u</span>
            </div>
            <div class="d-flex flex-row align-items-center justify-content-end gap-2 flex-shrink-0">
                <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="decrease" data-product-id="${item.id}" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                    <i class="bi bi-dash fs-6"></i>
                </button>
                <span class="text-center fw-semibold d-inline-block text-body" style="min-width: 18px; font-size: 0.95rem;">${item.quantity}</span>
                <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="increase" data-product-id="${item.id}" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                    <i class="bi bi-plus fs-6"></i>
                </button>
                <button type="button" class="btn btn-danger border-0 p-0 d-flex align-items-center justify-content-center rounded-2 ms-1" data-action="remove" data-product-id="${item.id}" style="width: 28px; height: 28px;">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
	    `;
	};

	const renderCartItems = () => {
		saleDetailsContainer.innerHTML =
			state.items.length === 0
				? emptyCartHTML
				: state.items.map(createCartItemHTML).join("");

		updateTotals();
	};

	// 5. Acciones del Carrito (Mutadores de Estado)
	const addToCart = (productId, productCard) => {
		const product = getProductFromCard(productCard);
		if (!product) {
			return showError(
				"No se pudo leer la información del producto.",
				`Datos inválidos para productId: ${productId}`,
			);
		}

		const existingItem = state.items.find((item) => item.id === productId);
		const currentQuantity = existingItem ? existingItem.quantity : 0;

		if (!hasEnoughStock(product, currentQuantity)) return;

		if (existingItem) {
			existingItem.quantity += 1;
		} else {
			state.items.push({ ...product, quantity: 1 });
		}

		renderCartItems();
	};

	const cartActions = {
		decrease: (productId) => {
			const item = state.items.find((i) => i.id === productId);
			if (!item) return;

			item.quantity -= 1;
			if (item.quantity <= 0) {
				state.items = state.items.filter((i) => i.id !== productId);
			}
			renderCartItems();
		},
		increase: (productId) => {
			const item = state.items.find((i) => i.id === productId);
			if (item && hasEnoughStock(item, item.quantity)) {
				item.quantity += 1;
				renderCartItems();
			}
		},
		remove: (productId) => {
			state.items = state.items.filter((i) => i.id !== productId);
			renderCartItems();
		},
	};

	const clearCart = () => {
		state.items = [];
		renderCartItems();
	};

	// 6. Manejadores de Eventos (Delegation)
	const handleProductClick = (event) => {
		const productCard = event.target.closest(".product-card");
		if (!productCard || !productsGrid.contains(productCard)) return;

		const productId = productCard.dataset.productId;
		if (!productId) {
			return showError(
				"No se pudo agregar el producto al carrito.",
				"No se encontró el ID del producto en la tarjeta.",
			);
		}

		addToCart(productId, productCard);
	};

	const handleCartActionClick = (event) => {
		const actionButton = event.target.closest("button[data-action]");
		if (!actionButton) return;

		const { action, productId } = actionButton.dataset;

		// Ejecuta la acción correspondiente del diccionario si existe
		if (productId && cartActions[action]) {
			cartActions[action](productId);
		}
	};

	// 7. Inicialización
	productsGrid.addEventListener("click", handleProductClick);
	saleDetailsContainer.addEventListener("click", handleCartActionClick);
	clearSaleButton.addEventListener("click", clearCart);

	renderCartItems(); // Render inicial
};