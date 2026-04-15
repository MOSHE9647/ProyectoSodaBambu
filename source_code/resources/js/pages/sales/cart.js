import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert";

function showError(errorMessage, consoleErrorMessage) {
    console.error(consoleErrorMessage);
    SwalToast.fire({
        icon: SwalNotificationTypes.ERROR,
        title: errorMessage,
    });
}

export const initializeSalesCart = () => {
    // 1. Caché de elementos críticos del DOM
    const productsGrid = document.getElementById("products-grid");
    const saleDetailsContainer = document.getElementById("sale-details");
    const saleTax = document.getElementById("sale-tax");
    const saleSubtotal = document.getElementById("sale-subtotal");
    const saleTotal = document.getElementById("sale-total");
    const finalizeSaleButton = document.getElementById("finalize-sale-btn");
    const clearSaleButton = document.getElementById("clear-sale-btn");

    // Cláusula de guarda para abortar inicialización si falta algo esencial
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
            "Error al inicializar carrito. Faltan elementos críticos del DOM."
        );
        return;
    }

    // 2. Estado local del carrito
    const state = {
        taxRate: 0.13,
        items: [],
    };

    // 3. Utilidades
    const formatCurrency = (amount) => {
        return `₡ ${Math.round(amount).toLocaleString("es-CR")}`;
    };

    const parsePrice = (rawPrice) => {
        const digits = String(rawPrice || "").replace(/[^\d]/g, "");
        return Number(digits || 0);
    };

    const getProductFromCard = (card) => {
        const id = card.dataset.productId;
        const name = card.dataset.productName || card.querySelector(".product-name")?.textContent?.trim() || "Producto";
        const price = parsePrice(
            card.dataset.productPrice || card.querySelector(".product-price")?.textContent
        );
        const hasInventory = card.dataset.productHasInventory === "1";
        const availableStock = Number(card.dataset.productStock || 0);

        if (!id || !price) {
            return null;
        }

        return {
            id,
            name,
            price,
            hasInventory,
            availableStock,
        };
    };

    const getItemSubtotal = (item) => item.price * item.quantity;

    const getOrderSubtotal = () => {
        return state.items.reduce((sum, item) => sum + getItemSubtotal(item), 0);
    };

    const getTaxAmount = (subtotal) => {
        return subtotal * state.taxRate;
    };

    const updateTotals = () => {
        const subtotal = getOrderSubtotal();
        const tax = getTaxAmount(subtotal);
        const total = subtotal + tax;

        saleTax.textContent = formatCurrency(tax);
        saleSubtotal.textContent = formatCurrency(subtotal);
        saleTotal.textContent = formatCurrency(total);

        const isCartEmpty = state.items.length === 0;
        finalizeSaleButton.disabled = isCartEmpty;
        clearSaleButton.disabled = isCartEmpty;
    };

    const renderEmptyCart = () => {
        saleDetailsContainer.innerHTML = `
            <div class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
                <i class="bi bi-bag fs-1 mb-2"></i>
                <p>Selecciona un producto para agregarlo a la orden</p>
            </div>
        `;
    };

    const renderCartItems = () => {
        if (state.items.length === 0) {
            renderEmptyCart();
            updateTotals();
            return;
        }

        saleDetailsContainer.innerHTML = state.items
            .map((item) => {
                return `
                    <div class="d-flex flex-row justify-content-between align-items-center gap-2 w-100" data-cart-item-id="${item.id}">
                        <div class="d-flex flex-column text-start overflow-hidden flex-grow-1">
                            <span class="fw-bold text-truncate text-body" style="font-size: 0.95rem;">${item.name}</span>
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
            })
            .join("");

        updateTotals();
    };

    const addToCart = (productId) => {
        const productCard = productsGrid.querySelector(`.product-card[data-product-id="${productId}"]`);
        if (!productCard) {
            showError(
                "No se pudo agregar el producto al carrito.",
                `Error al agregar al carrito. No se encontró la tarjeta para productId: ${productId}`
            );
            return;
        }

        const product = getProductFromCard(productCard);
        if (!product) {
            showError(
                "No se pudo leer la información del producto para agregarlo al carrito.",
                `Error al agregar al carrito. Datos inválidos para productId: ${productId}`
            );
            return;
        }

        const existingItem = state.items.find((item) => item.id === productId);

        if (product.hasInventory) {
            const currentQuantity = existingItem ? existingItem.quantity : 0;
            if (currentQuantity >= product.availableStock) {
                SwalToast.fire({
                    icon: SwalNotificationTypes.WARNING,
                    title: "No hay más unidades disponibles para este producto.",
                });
                return;
            }
        }

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            state.items.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1,
                hasInventory: product.hasInventory,
                availableStock: product.availableStock,
            });
        }

        renderCartItems();
    };

    const decreaseQuantity = (productId) => {
        const item = state.items.find((cartItem) => cartItem.id === productId);
        if (!item) {
            return;
        }

        item.quantity -= 1;
        if (item.quantity <= 0) {
            state.items = state.items.filter((cartItem) => cartItem.id !== productId);
        }

        renderCartItems();
    };

    const increaseQuantity = (productId) => {
        const item = state.items.find((cartItem) => cartItem.id === productId);
        if (!item) {
            return;
        }

        if (item.hasInventory && item.quantity >= item.availableStock) {
            SwalToast.fire({
                icon: SwalNotificationTypes.WARNING,
                title: "No hay más unidades disponibles para este producto.",
            });
            return;
        }

        item.quantity += 1;
        renderCartItems();
    };

    const removeFromCart = (productId) => {
        state.items = state.items.filter((item) => item.id !== productId);
        renderCartItems();
    };

    const clearCart = () => {
        state.items = [];
        renderCartItems();
    };

    // 4. Handlers
    const handleProductClick = (event) => {
        const productCard = event.target.closest(".product-card");
        if (!productCard || !productsGrid.contains(productCard)) {
            return;
        }

        const productId = productCard.dataset.productId;
        if (!productId) {
            showError(
                "No se pudo agregar el producto al carrito. No se encontró la información del producto.",
                "Error al agregar al carrito. No se encontró el ID del producto en la tarjeta."
            );
            return;
        }

        addToCart(productId);
    };

    const handleCartAction = (event) => {
        const actionButton = event.target.closest("button[data-action]");
        if (!actionButton) {
            return;
        }

        const productId = actionButton.dataset.productId;
        if (!productId) {
            return;
        }

        const action = actionButton.dataset.action;

        if (action === "decrease") {
            decreaseQuantity(productId);
            return;
        }

        if (action === "increase") {
            increaseQuantity(productId);
            return;
        }

        if (action === "remove") {
            removeFromCart(productId);
        }
    };

    // 5. Inicialización
    productsGrid.addEventListener("click", handleProductClick);
    saleDetailsContainer.addEventListener("click", handleCartAction);
    clearSaleButton.addEventListener("click", clearCart);

    renderCartItems();
};