import { SwalToast } from "../../../utils/sweetalert.js";
import { setLoadingState } from "../../../utils/utils.js";
import { submitFormToApi } from "../../../utils/http-client.js";
import { realTimeValidationHandler, submitClientForm } from "../../clients/form.js";

/**
 * Handles the submission of the client form.
 * Sends form data to the API, updates the client select dropdown if needed,
 * hides the offcanvas, and shows a toast notification.
 *
 * @param {Event} e - The submit event object.
 * @param {Object} offcanvasInstance - The Bootstrap Offcanvas instance to hide after submission.
 * @returns {Promise<void>}
 */
const submitClientFormHandler = async (e, offcanvasInstance) => {
	try {
		const form = e.currentTarget;
		const responseData = await submitFormToApi(form.action, form);

		// Update the DOM if the client select exists
		const clientSelect = $("#client_id");
		if (clientSelect.length && responseData?.client) {
			const newOption = new Option(
				responseData.client.name,
				responseData.client.id,
				true,
				true,
			);
			clientSelect.append(newOption).trigger("change");
		}

		offcanvasInstance.hide();

		SwalToast.fire({
			icon: "success",
			title: responseData.message || "Cliente creado con éxito.",
		});
	} catch (error) {
		console.error("Error submitting client form:", error);
		SwalToast.fire({
			icon: "error",
			title: error.message || "Error al crear cliente.",
		});
	} finally {
		setLoadingState("create-client-form", false);
	}
};

/**
 * Initializes event listeners for the client form.
 * Handles form submission and real-time validation events.
 *
 * @param {Object} offcanvasInstance - The Bootstrap Offcanvas instance to hide after submission.
 */
export function initClientEvents(offcanvasInstance) {
	// Handle form submission
	$(document)
		.off("submit", "#create-client-form")
		.on("submit", "#create-client-form", async function (e) {
			e.preventDefault();
			setLoadingState("create-client-form", true);

			// submitClientForm() returns true if the form is valid
			if (submitClientForm()) {
				await submitClientFormHandler(e, offcanvasInstance);
			} else {
				setLoadingState("create-client-form", false);
			}
		});

	// Handle real-time validation on input or change
	$(document)
		.off("input change", "#create-client-form")
		.on("input change", "#create-client-form", function (e) {
			realTimeValidationHandler(e);
		});
}