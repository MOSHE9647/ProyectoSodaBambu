import { SwalModal } from "../../../utils/sweetalert";
import { setLoadingState, toggleLoadingState } from "../../../utils/utils";
import { openCategoryModal } from "./modals/category";

const PURCHASABLE_TYPES = window.purchaseFormData?.purchasableTypes || [];
const PRODUCTS_LIST = window.purchaseFormData?.products || [];

/**
 * Generates a random valid EAN-13 barcode number.
 *
 * @param {number} length - The total length of the EAN code (default is 13).
 * @param {HTMLElement} triggerElement - The element that triggered the barcode generation, used for loading state.
 * @returns {string} The generated EAN-13 code as a string.
 *
 * The function generates a random EAN-13 code by:
 * 1. Generating (length - 1) random digits.
 * 2. Calculating the checksum digit according to the EAN-13 standard.
 * 3. Appending the checksum digit to the end of the code.
 * 4. Toggling the loading state on the trigger element during the process.
 */
const generateEan13 = (length = 13, triggerElement) => {
    const elementClass = `add-${triggerElement.dataset.type}`;
    toggleLoadingState(triggerElement, elementClass, true);

    let ean = "";
    for (let i = 0; i < length - 1; i++) {
        ean += Math.floor(Math.random() * 10).toString();
    }

    // Calculate checksum digit according to EAN-13 standard
    let sum = 0;
    for (let i = 0; i < ean.length; i++) {
        sum += parseInt(ean[i]) * (i % 2 === 0 ? 1 : 3);
    }
    const checksum = (10 - (sum % 10)) % 10;
    ean += checksum.toString();

    toggleLoadingState(triggerElement, elementClass, false);
    return ean;
};

const submitProductFormHandler = async (e, offcanvasInstance) => {
    try {
        const form = e.currentTarget;
        // Simulación de respuesta del backend para pruebas
        const responseData = {
            product: { id: 123, name: "Producto de prueba" },
            message: "Producto creado con éxito."
        };

        // Update all the selects that exist in the DOM with the new product
        const purchasableTypeSelector = $.escapeSelector(PURCHASABLE_TYPES.product);
        const productSelects = $("#purchase-details-table")
            .find(`tbody tr[data-purchasable-type="${purchasableTypeSelector}"]`)
            .find('[name="purchasable_id"]');
            
        if (productSelects.length && responseData?.product) {
            productSelects.each(function () {
                const newOption = new Option(
                    responseData.product.name,
                    responseData.product.id,
                    true,
                    true,
                );
                $(this).append(newOption).trigger("change");
            });

            // Adds the new product to the global PRODUCTS_LIST so it can be used 
            // in other selects without needing to refresh the page
            PRODUCTS_LIST.push(responseData.product);
        }

        offcanvasInstance.hide();

        SwalToast.fire({
            icon: "success",
            title: responseData.message || "Producto creado con éxito.",
        });
    } catch (error) {
        console.error("Error submitting product form:", error);
        SwalToast.fire({
            icon: "error",
            title: error.message || "Error al crear producto.",
        });
    } finally {
        setLoadingState("create-product-form", false);
    }
};

/**
 * Initializes events for the product form within the offcanvas.
 * Binds form submission, real-time validation, and button clicks for adding categories and generating barcodes.
 * 
 * @param {Object} offcanvasInstance - The instance of the offcanvas component.
 */
export function initProductEvents(offcanvasInstance) {
    // Handle form submission
    $(document)
        .off("submit", "#create-product-form")
        .on("submit", "#create-product-form", async function (e) {
            e.preventDefault();
            console.log("Product form submitted");
            setLoadingState("create-product-form", true);

            if (true) {
                await submitProductFormHandler(e, offcanvasInstance);
            } else {
                setLoadingState("create-product-form", false);
            }
        });

    // Handle real-time validation on input or change
    $(document)
        .off("input change", "#create-product-form")
        .on("input change", "#create-product-form", function (e) {
            // realTimeValidationHandler(e);
        });

    // Handle click on "Add Category" button
    $(document)
		.off("click", "#add-category-btn")
		.on("click", "#add-category-btn", function (e) {
			// Open a SweetAlert modal to add a new category
			openCategoryModal(this);
		});

    // Handle click on "Generate Barcode" button
    $(document)
        .off("click", "#generate-barcode-btn")
        .on("click", "#generate-barcode-btn", function (e) {
            // Generate a new barcode
            const ean = generateEan13(13, this);
            $("#barcode").val(ean);
        });
}