import { SwalToast } from "../../../utils/sweetalert";
import { setLoadingState } from "../../../utils/utils";
import { submitFormToApi } from "../../../utils/http-client";
import { realTimeValidationHandler, submitSupplyForm } from "../../supplies/form";

const PURCHASABLE_TYPES = window.purchaseFormData?.purchasableTypes || [];
const SUPPLIES_LIST = window.purchaseFormData?.supplies || [];

const submitSupplyFormHandler = async (e, offcanvasInstance) => {
    try {
		const form = e.currentTarget;
		const responseData = await submitFormToApi(form.action, form);

		// Update all the selects that exist in the DOM with the new supply
		const purchasableTypeSelector = $.escapeSelector(PURCHASABLE_TYPES.supply);
		const supplySelects = $("#purchase-details-table")
			.find(`tbody tr[data-purchasable-type="${purchasableTypeSelector}"]`,)
			.find('[name="purchasable_id"]');

		if (supplySelects.length && responseData?.supply) {
			supplySelects.each(function () {
				const newOption = new Option(
					responseData.supply.name,
					responseData.supply.id,
					true,
					true,
				);
				$(this).append(newOption).trigger("change");
			});

			// Adds the new supply to the global SUPPLIES_LIST so it can be used
			// in other selects without needing to refresh the page
			SUPPLIES_LIST.push(responseData.supply);
		}

        offcanvasInstance.hide();

        SwalToast.fire({
            icon: "success",
            title: responseData.message || "Insumo creado con éxito.",
        });
	} catch (error) {
        console.error("Error submitting supply form:", error);
        SwalToast.fire({
            icon: "error",
            title: error.message || "Error al crear insumo.",
        });
    } finally {
        setLoadingState("create-supply-form", false);
    }
};

export function initSupplyEvents(offcanvasInstance) {
    // Handle form submission
    $(document)
        .off("submit", "#create-supply-form")
        .on("submit", "#create-supply-form", async function (e) {
            e.preventDefault();
            setLoadingState("create-supply-form", true);

            if (submitSupplyForm()) {
                await submitSupplyFormHandler(e, offcanvasInstance);
            } else {
                setLoadingState("create-supply-form", false);
            }
        });

    // Handle real-time validation on input or change
    $(document)
        .off("input change", "#create-supply-form")
        .on("input change", "#create-supply-form", function (e) {
            realTimeValidationHandler(e);
        });
}