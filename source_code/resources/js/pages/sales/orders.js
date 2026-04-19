import { switchActiveOrder, deleteOrderCart } from "./cart.js";

// Variable de estado general
const state = {
	orderCounter: 2, // Inicia en 2 porque ya asumes que existe la ORD-0001
};

// Generador de HTML limpio (sin eventos inline)
const createOrderButton = (
	btnID = "",
	active = false,
	showIcon = true,
	icon = null,
	showCloseBtn = true,
	buttonContent = "",
) => {
	let btnClasses =
		"nav-link d-flex align-items-center gap-2 py-1 ps-3 pe-2 border rounded-3 text-nowrap order-tab-btn";
	if (active) btnClasses += " active";

	let leftIcon = "";
	if (showIcon) {
		leftIcon = icon
			? `<i class="tab-btn-icon ${icon} flex-shrink-0"></i>`
			: `<span class="tab-btn-icon rounded-circle flex-shrink-0" style="width: 6px; height: 6px; background-color: currentColor;"></span>`;
	}

	return `
    <li class="nav-item" role="presentation">
        <button ${btnID ? `id="${btnID}"` : ""} class='${btnClasses}' style="font-size: 0.85rem;" type="button" role="tab">
            ${leftIcon}
            <div class="tab-title ${!showCloseBtn ? "pe-2" : ""}">
                ${buttonContent}
            </div>
            ${
				showCloseBtn
					? `<div class="btn-close ms-1 flex-shrink-0 close-tab-btn" style="font-size: 0.60rem;" tabindex="-1"></div>`
					: ""
			}
        </button>
    </li>
    `;
};

export function initializeSalesOrderTabs() {
	const tabsBtnsContainer = $("#order-tabs-container");
	const newOrderBtn = $("#new-order-btn");

	if (!tabsBtnsContainer.length) return;

	// 1. Lógica de los botones de cierre (Previene borrar la última)
	const updateCloseButtons = () => {
		const allTabs = tabsBtnsContainer.find(".nav-item");
		if (allTabs.length === 1) {
			// Ocultar botón de cerrar si solo queda una
			allTabs.find(".close-tab-btn").hide();
			allTabs.find(".tab-title").removeClass("pe-0").addClass("pe-2");
		} else {
			// Mostrar si hay más de una
			allTabs.find(".close-tab-btn").show();
			allTabs.find(".tab-title").removeClass("pe-2");
		}
	};

	// 2. Función auxiliar para desactivar la pestaña actual
	const deactivateAllTabs = () => {
		const activeTabs = tabsBtnsContainer.find(".order-tab-btn.active");
		activeTabs.removeClass("active");
		activeTabs.find(".tab-btn-icon").remove(); // Quita el indicador de la anterior
	};

	const scrollTabsToEnd = () => {
		const container = tabsBtnsContainer.get(0);
		if (!container) return;

		container.scrollTo({
			left: container.scrollWidth,
			behavior: "smooth",
		});
	};

	// 3. Agregar Nueva Orden
	if (newOrderBtn.length) {
		newOrderBtn.on("click", function () {
			const tabBtnId = state.orderCounter.toString().padStart(4, "0");
			const newTabBtnId = `order-tab-${tabBtnId}`;
			const newTabBtnContent = `ORD-${tabBtnId}`;

			const newTabHTML = createOrderButton(
				newTabBtnId,
				true,
				true,
				null,
				true,
				newTabBtnContent,
			);

			deactivateAllTabs();
			tabsBtnsContainer.append(newTabHTML);

			scrollTabsToEnd();
			updateCloseButtons();
			state.orderCounter++;

			switchActiveOrder(newTabBtnId); // Cambia el estado a la nueva orden creada
		});
	}

	// 4. Cambiar entre pestañas (Delegación de Eventos)
	tabsBtnsContainer.on("click", ".order-tab-btn", function (e) {
		// Ignorar si el clic fue en la 'X' de cerrar
		if ($(e.target).hasClass("close-tab-btn")) return;

		// Ignorar si ya está activa
		if ($(this).hasClass("active")) return;

		deactivateAllTabs();
		$(this).addClass("active");

		// Renderizar el indicador circular
		if (!$(this).find(".tab-btn-icon").length) {
			$(this).prepend(
				`<span class="tab-btn-icon rounded-circle flex-shrink-0" style="width: 6px; height: 6px; background-color: currentColor;"></span>`,
			);
		}

		const orderId = $(this).attr("id");
		console.log("Cambiando a la orden:", orderId);
		
        switchActiveOrder(orderId);
	});

	// 5. Cerrar Pestaña (Delegación de Eventos)
	tabsBtnsContainer.on("click", ".close-tab-btn", function (e) {
		e.stopPropagation(); // Evitar que el clic se propague y active la pestaña

		const totalTabs = tabsBtnsContainer.find(".nav-item").length;
		if (totalTabs <= 1) return; // Doble validación de seguridad

		const tabLi = $(this).closest(".nav-item");
		const tabBtn = tabLi.find(".order-tab-btn");
		const wasActive = tabBtn.hasClass("active");

		tabLi.remove();

		// Si borramos la pestaña activa, forzamos la activación de la última que quede
		if (wasActive) {
			const lastTab = tabsBtnsContainer.find(".order-tab-btn").last();
			lastTab.click(); // Simulamos el clic para ejecutar la lógica de activación
		}

		updateCloseButtons();
        deleteOrderCart(tabBtn.attr("id")); // Eliminar datos asociados a esa orden
	});

	// Ejecutar al inicio por si la vista carga con 1 sola pestaña
	updateCloseButtons();
}
