// ===================== Environment Checks =====================
if (typeof $ === "undefined") {
	throw new Error("This script requires jQuery");
}

// ========================= Constants ==========================
const PURCHASE_DATA = window.purchaseFormData;

// =========================== Helpers ==========================
const getElements = () => ({
	tableBody: $("#purchase-details-table tbody"),
	tableFootTotal: $("#total"),
	emptyRow: $("#empty-row"),
	addedItemsBadge: $("#added-items-badge"),
	itemsCountLabel: $("#items-count-label"),
	addProductBtn: $("#add-product-btn"),
	addSupplyBtn: $("#add-supply-btn"),
});

const formatCurrency = (amount) => {
	return Number(amount).toLocaleString("es-CR", {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	});
};

const calculateTotals = () => {
	const elements = getElements();
	let itemCount = 0;
	let total = 0;

	const rows = elements.tableBody.find("tr:not(#empty-row)");

	rows.each((_, row) => {
		const $row = $(row);
		const quantity = parseFloat($row.find('[name="quantity"]').val()) || 0;
		const unitPrice = parseFloat($row.find('[name="unit-price"]').val()) || 0;
		const subTotalSpan = $row.find(".sub-total");

		if (!isNaN(quantity) && !isNaN(unitPrice) && subTotalSpan.length) {
			const subTotal = quantity * unitPrice;
			subTotalSpan.text(formatCurrency(subTotal));
			total += subTotal;
			itemCount += 1;
		}
	});

	elements.tableFootTotal.text(formatCurrency(total));
	elements.addedItemsBadge.text(itemCount);
	elements.itemsCountLabel.text(
		itemCount === 0
			? "Sin ítems agregados"
			: `${itemCount} ${itemCount === 1 ? "ítem agregado" : "ítems agregados"}`,
	);

	if (elements.emptyRow.length) {
		elements.emptyRow.toggleClass("d-none", itemCount > 0);
	}
};

const createItemRowHtml = (type) => {
	const { products, supplies, purchasableTypes } = PURCHASE_DATA;

	const isProduct = type === "product";
	const typeClass = isProduct ? "info" : "warning";
	const typeLabel = isProduct ? "Producto" : "Insumo";
	const iconClass = isProduct ? "bi-box-seam" : "bi-basket";
	const modelClass = isProduct
		? purchasableTypes.product
		: purchasableTypes.supply;
	const itemsList = isProduct ? products : supplies;

	const optionsHTML = itemsList
		.map((item) => `<option value="${item.id}">${item.name}</option>`)
		.join("");

	return $(`
        <tr data-id="" data-purchasable-type="${modelClass}">
            <td>
                <span class="badge bg-${typeClass} text-${typeClass}-emphasis border border-${typeClass} bg-${typeClass}-subtle rounded-pill px-3 py-2" style="width: 100px;">
                    <i class="${iconClass} me-1"></i> 
                    ${typeLabel}
                </span>
            </td>
            <td>
                <div class="border-secondary w-auto text-start">
            	    <div class="input-group has-validation">
		                <select name="purchasable_id" class="form-select" aria-describedby=" purchasable_id-error">
			                <option value="-1">Seleccione un ${typeLabel.toLowerCase()}</option>
                            ${optionsHTML}
                		</select>

					    <button type="button" class="new-product-btn btn btn-sm btn-outline-${typeClass} rounded-end-2" title="Crear nuevo ${typeLabel.toLowerCase()}">
                            <i class="bi bi-plus-circle mx-1"></i>
                        </button>
		
                        <div id="purchasable_id-error" class="invalid-feedback ps-2" role="alert">
                            <strong></strong>
                        </div>
                	</div>
                </div>
            </td>
            <td>
                <div class="item-quantity d-flex flex-row align-items-center justify-content-center gap-2">
                    <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="decrease" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                        <i class="bi bi-dash fs-6 pointer-events-none"></i>
                    </button>
                    <input name="quantity" type="number" class="quantity-input form-control border-0 text-center fw-semibold text-body px-1 py-0" style="width: 38px; background-color: transparent;" value="1" min="1" step="0.5" required>
                    <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="increase" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                        <i class="bi bi-plus fs-6 pointer-events-none"></i>
                    </button>
                </div>
            </td>
            <td class="text-end">
                <input name="unit-price" type="number" class="form-control border-secondary-subtle text-end fw-semibold text-body px-1 py-1 border-1" style="width: 135px; background-color: transparent;" value="0.00" min="0.01" step="0.01" required>
            </td>
            <td class="fw-bold text-end">
                ₡ <span class="sub-total">0,00</span>
            </td>
            <td>
                <button type="button" class="action-btn btn btn-sm btn-outline-danger" data-action="remove" title="Eliminar Item de la Compra">
                    <i class="bi bi-trash3 pointer-events-none"></i>
                </button>
            </td>
        </tr>
    `);
};

// ======================= Actions =======================
const actions = {
	increase: ($row) => {
		const $quantityInput = $row.find('input[name="quantity"]');
		const currentQty = parseFloat($quantityInput.val()) || 0;
		const step = parseFloat($quantityInput.attr("step")) || 1; // Corregido .attr('step')

		$quantityInput.val(currentQty + step);
		calculateTotals();
	},
	decrease: ($row) => {
		const $quantityInput = $row.find('input[name="quantity"]');
		const currentQty = parseFloat($quantityInput.val()) || 0;
		const step = parseFloat($quantityInput.attr("step")) || 1; // Corregido .attr('step')
		const min = parseFloat($quantityInput.attr("min")) || 1;

		if (currentQty > min) {
			$quantityInput.val(Math.max(min, currentQty - step));
			calculateTotals();
		}
	},
	remove: ($row) => {
		$row.remove();
		calculateTotals();
	},
};

// ======================= Event Handlers =======================
export function bindPurchaseFormEvents() {
	const elements = getElements();

	elements.addProductBtn.on("click", () => {
		elements.tableBody.append(createItemRowHtml("product"));
		calculateTotals();
	});

	elements.addSupplyBtn.on("click", () => {
		elements.tableBody.append(createItemRowHtml("supply"));
		calculateTotals();
	});

	elements.tableBody.on("click", "button[data-action]", function (event) {
		event.preventDefault();
		const $actionButton = $(this);
		const action = $actionButton.data("action");
		const $row = $actionButton.closest("tr");

		if (actions[action]) {
			actions[action]($row);
		}
	});

	elements.tableBody.on("input", 'input[name="quantity"], input[name="unit-price"]', () => {
        calculateTotals();
	});

	calculateTotals();
}