import { fetchWithErrorHandling } from "./error-handling.js";
import { SwalToast } from "./sweetalert.js";
import { enableBootstrapTooltips, toggleLoadingState } from "./utils.js";
import { initProductEvents } from "../models/purchases/offcanvas/products.js";
import { initSupplierEvents } from "../models/purchases/offcanvas/suppliers.js";

// ===================== Environment Checks =====================

/**
 * Ensure required libraries are loaded.
 * Throws an error if jQuery or Bootstrap JS are not available.
 */
if (typeof $ === "undefined") throw new Error("This script requires jQuery");
if (typeof bootstrap === "undefined") throw new Error("This script requires Bootstrap's JavaScript");

// ========================= Constants ==========================

/**
 * Tracks initialized forms to prevent duplicate event listeners.
 * @type {Set<string>}
 */
const initializedForms = new Set();

/**
 * Configuration for each offcanvas type.
 * Contains icon, title, and description for supplier, product, and supply.
 */
const OFFCANVAS_CONFIG = {
    supplier: {
        icon: 'bi bi-building',
        title: 'Agregar nuevo proveedor',
        description: 'Ingrese los datos del nuevo proveedor',
    },
    product: {
        icon: 'bi bi-box-seam',
        title: 'Agregar nuevo producto',
        description: 'Ingrese los datos del nuevo producto',
    },
    supply: {
        icon: 'bi bi-basket',
        title: 'Agregar nuevo insumo',
        description: 'Ingrese los datos del nuevo insumo',
    },
    category: {
        icon: 'bi bi-tags',
        title: 'Agregar nueva categoría',
        description: 'Ingrese los datos de la nueva categoría',
    },
};

// ====================== Helper Functions ======================

/**
 * Binds specific event initializers to the offcanvas form based on its type.
 * Prevents duplicate event bindings by checking initializedForms.
 * @param {string} type - The type of form (supplier, product, supply).
 * @param {object} offcanvasInstance - The Bootstrap Offcanvas instance.
 */
const bindSpecificOffcanvasEvents = (type, offcanvasInstance) => {
    if (initializedForms.has(type)) return;

    switch (type) {
        case 'supplier':
            initSupplierEvents(offcanvasInstance);
            break;
        case 'product':
            initProductEvents(offcanvasInstance);
            break;
        case 'supply':
            // initSupplyEvents(offcanvasInstance);
            break;
        case 'category':
            // initCategoryEvents(offcanvasInstance);
            break;
        default:
            console.warn(`No event initializer defined for offcanvas type: ${type}`);
    }
    
    initializedForms.add(type);
};

/**
 * Fetches the HTML content for the offcanvas form from the server and injects it into the DOM.
 * Handles loading state, error notifications, and event binding.
 * @param {string} type - The type of form to fetch.
 * @param {JQuery} $body - The jQuery element where the form HTML will be injected.
 * @param {object} offcanvasInstance - The Bootstrap Offcanvas instance.
 * @param {HTMLElement} triggerElement - The button or element that triggered the offcanvas.
 */
async function fetchOffcanvasContent(type, $body, offcanvasInstance, triggerElement) {
    const elementClass = `add-${triggerElement.dataset.type}`;
    toggleLoadingState(triggerElement, elementClass, true);

    try {
        const url = route('purchases.offcanvas-form', { type });
        const response = await fetchWithErrorHandling(url, {}, 
            `Error al cargar el formulario de creación. Por favor, inténtelo de nuevo.`
        );

        const html = await response.text();
        if (!html) throw new Error("La respuesta del servidor está vacía.");

        $body.html(html);
        bindSpecificOffcanvasEvents(type, offcanvasInstance);
        enableBootstrapTooltips($body[0]);

        offcanvasInstance.show();
    } catch (error) {
        console.error("Error fetching offcanvas content:", error);
        SwalToast.fire({
            icon: "error",
            title: error.message || "Ocurrió un error al cargar el formulario.",
        });
    } finally {
        toggleLoadingState(triggerElement, elementClass, false);
    }
}

// ======================= Main Function ========================

/**
 * Binds click events to elements with the .btn-offcanvas class.
 * When triggered, loads the appropriate form into the offcanvas and displays it.
 * Ensures the offcanvas DOM element exists before binding events.
 */
export function bindOffcanvasEvents(offcanvasId) {
    const offcanvasDOM = document.getElementById(offcanvasId);
    if (!offcanvasDOM) return; // Guard clause: exit if the modal does not exist in the current view
    
    const createOffcanvas = new bootstrap.Offcanvas(offcanvasDOM);
    const $title = $('#offcanvas-title');
    const $body = $('#offcanvas-body');

    $(document).on('click', '.btn-offcanvas', function () {
        const type = $(this).data('type');
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