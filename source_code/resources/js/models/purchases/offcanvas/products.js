import { setLoadingState } from "../../../utils/utils";
import { submitFormToApi } from "../../../utils/http-client";
import { initProductFormEvents, submitProductForm } from "../../products/form";

const PURCHASABLE_TYPES = window.purchaseFormData?.purchasableTypes || [];
const PRODUCTS_LIST = window.purchaseFormData?.products || [];

const submitProductFormHandler = async (e, offcanvasInstance) => {
    try {
        const form = e.currentTarget;
        const responseData = await submitFormToApi(form.action, form);

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
		.off("submit", 'form[id$="-product-form"]')
		.off("submit", "#create-product-form")
		.on("submit", "#create-product-form", async function (e) {
			e.preventDefault();
			setLoadingState("create-product-form", true);

			if (submitProductForm(e.currentTarget)) {
				await submitProductFormHandler(e, offcanvasInstance);
			} else {
				setLoadingState("create-product-form", false);
			}
		});

	// Initialize events from the main product form script
	initProductFormEvents("#create-product-form");
}