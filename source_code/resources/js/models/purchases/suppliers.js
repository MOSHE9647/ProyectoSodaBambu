import { SwalToast } from "../../utils/sweetalert.js";
import { setLoadingState } from "../../utils/utils.js";
import { submitFormToApi } from "../../utils/httpClient.js";
import { realTimeValidationHandler, submitSupplierForm } from "../suppliers/form.js";


/**
 * Handles the submission of the supplier form.
 * Sends form data to the API, updates the supplier select dropdown if needed,
 * hides the offcanvas, and shows a toast notification.
 *
 * @param {Event} e - The submit event object.
 * @param {Object} offcanvasInstance - The Bootstrap Offcanvas instance to hide after submission.
 * @returns {Promise<void>}
 */
const submitSupplierFormHandler = async (e, offcanvasInstance) => {
	try {
		const form = e.currentTarget;
		const responseData = await submitFormToApi(form.action, form);

		// Update the DOM if the supplier select exists
		const supplierSelect = $("#supplier_id");
		if (supplierSelect.length && responseData?.supplier) {
			const newOption = new Option(
				responseData.supplier.name,
				responseData.supplier.id,
				true,
				true,
			);
			supplierSelect.append(newOption).trigger("change");
		}

		offcanvasInstance.hide();

		SwalToast.fire({
			icon: "success",
			title: responseData.message || "Proveedor creado con éxito.",
		});
	} catch (error) {
		console.error("Error submitting supplier form:", error);
		SwalToast.fire({
			icon: "error",
			title: error.message || "Error al crear proveedor.",
		});
	} finally {
		setLoadingState("create-supplier-form", false);
	}
};


/**
 * Initializes event listeners for the supplier form.
 * Handles form submission and real-time validation events.
 *
 * @param {Object} offcanvasInstance - The Bootstrap Offcanvas instance to hide after submission.
 */
export function initSupplierEvents(offcanvasInstance) {
	// Handle form submission
	$(document)
		.off("submit", "#create-supplier-form")
		.on("submit", "#create-supplier-form", async function (e) {
			e.preventDefault();
			setLoadingState("create-supplier-form", true);

			// submitSupplierForm() returns true if the form is valid
			if (submitSupplierForm()) {
				await submitSupplierFormHandler(e, offcanvasInstance);
			} else {
				setLoadingState("create-supplier-form", false);
			}
		});

	// Handle real-time validation on input or change
	$(document)
		.off("input change", "#create-supplier-form")
		.on("input change", "#create-supplier-form", function (e) {
			realTimeValidationHandler(e);
		});
}