import { fetchWithErrorHandling } from "../../utils/error-handling";
import { SwalToast } from "../../utils/sweetalert";
import { toggleLoadingState } from "../../utils/utils";
import { handleSupplierFormEvents } from "./api";

// ===================== Environment Checks =====================

if (typeof $ === "undefined") throw new Error("This script requires jQuery");
if (typeof bootstrap === "undefined")
	throw new Error("This script requires Bootstrap's JavaScript");

// ========================== Variables =========================

// Usamos un Set para rastrear qué formularios ya cargaron sus eventos de forma dinámica
const initializedForms = new Set();

// Configuración estática fuera del evento click para ahorrar memoria
const OFFCANVAS_CONFIG = {
	supplier: {
		icon: "bi bi-building",
		title: "Agregar nuevo proveedor",
		description: "Ingrese los datos del nuevo proveedor",
	},
	product: {
		icon: "bi bi-box-seam",
		title: "Agregar nuevo producto",
		description: "Ingrese los datos del nuevo producto",
	},
	supply: {
		icon: "bi bi-basket",
		title: "Agregar nuevo insumo",
		description: "Ingrese los datos del nuevo insumo",
	},
};

// ====================== Helper Functions ======================

const initOffcanvasEvents = (type, offcanvasInstance) => {
	if (initializedForms.has(type)) return; // Si ya se inicializó, salimos

	if (type === "supplier") {
		handleSupplierFormEvents(offcanvasInstance);
	}

	initializedForms.add(type); // Marcamos como inicializado
};

async function fetchOffcanvasContent(
	type,
	$body,
	offcanvasInstance,
	triggerElement,
) {
	const elementClass = `add-${triggerElement.dataset.type}`;
	toggleLoadingState(triggerElement, elementClass, true);

	try {
		// Asumiendo el uso de Ziggy para las rutas en Laravel
		const url = route("purchases.offcanvas-form", { type });
		const response = await fetchWithErrorHandling(
			url,
			{},
			`Error al cargar el formulario de creación. Por favor, inténtelo de nuevo.`,
		);

		const html = await response.text();
		if (!html) throw new Error("Respuesta vacía del servidor.");

		$body.html(html);
		initOffcanvasEvents(type, offcanvasInstance);

		offcanvasInstance.show();
	} catch (error) {
		console.error(
			"Error fetching offcanvas content:",
			error.message || error,
		);
		SwalToast.fire({
			icon: "error",
			title: error.message || "Ocurrió un error al cargar el formulario.",
		});
	} finally {
		toggleLoadingState(triggerElement, elementClass, false);
	}
}

// ======================= Main Function ========================

export function bindOffcanvasEvents() {
	const offcanvasDOM = document.getElementById("create-offcanvas"); // JS nativo es más directo aquí
	const createOffcanvas = new bootstrap.Offcanvas(offcanvasDOM);

	const $title = $("#offcanvas-title");
	const $body = $("#offcanvas-body");

	$(document).on("click", ".btn-offcanvas", function () {
		const type = $(this).data("type");
		const config = OFFCANVAS_CONFIG[type];

		if (config) {
			$title.html(`
                <div class="d-flex flex-column">
                    <span class="d-flex align-items-center gap-3 fs-5 fw-bold">
                        <i class="${config.icon}"></i>
                        ${config.title}
                    </span>
                    <small class="text-muted d-block fs-6">
                        ${config.description}
                    </small>
                </div>
            `);

			fetchOffcanvasContent(type, $body, createOffcanvas, this);
		}
	});
}