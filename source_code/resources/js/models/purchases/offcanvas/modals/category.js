import { fetchWithErrorHandling } from "../../../../utils/error-handling";
import { submitFormToApi } from "../../../../utils/http-client";
import { SwalModal, SwalToast } from "../../../../utils/sweetalert";
import { setLoadingState, toggleLoadingState } from "../../../../utils/utils";
import { realTimeValidationHandler, submitCategoryForm } from "../../../category/form";

const submitCategoryFormHandler = async (e, modal) => {
    try {
		const form = e.currentTarget;
		const responseData = await submitFormToApi(form.action, form);

		// Update the category select in the product form if it exists
		const categorySelect = $("#category_id");
		if (categorySelect.length && responseData?.category) {
			const newOption = new Option(
				responseData.category.name,
				responseData.category.id,
				true,
				true,
			);
			categorySelect.append(newOption).trigger("change");
		}

		modal.close();

		SwalToast.fire({
			icon: "success",
			title: responseData.message || "Categoría creada con éxito.",
		});
	} catch (error) {
        SwalToast.fire({
            icon: "error",
            title: error.message || "Ocurrió un error al crear la categoría.",
        });
    } finally {
        setLoadingState("create-category-form", false);
    }
};

/**
 * Fetches the HTML content for the modal form of the specified type.
 * Handles loading state and error display.
 *
 * @param {string} type - The type of modal to fetch (e.g., 'category').
 * @param {HTMLElement} triggerElement - The element that triggered the modal (used for loading state).
 * @returns {Promise<string|undefined>} The HTML content of the modal, or undefined if an error occurred.
 */
async function fetchModalContent(type, triggerElement) {
    const elementClass = `add-${triggerElement.dataset.type}`;
    toggleLoadingState(triggerElement, elementClass, true);

    try {
        const url = route('purchases.offcanvas-form', { type });
        const response = await fetchWithErrorHandling(url, {}, 
            `Error al cargar el formulario de creación. Por favor, inténtelo de nuevo.`
        );

        const html = await response.text();
        if (!html) throw new Error("La respuesta del servidor está vacía.");

        return html;
    } catch (error) {
        console.error("Error fetching modal content:", error);
        SwalToast.fire({
            icon: "error",
            title: error.message || "Ocurrió un error al cargar el formulario.",
        });
    } finally {
        toggleLoadingState(triggerElement, elementClass, false);
    }
}

/**
 * Opens the modal for creating a new category.
 * Fetches the modal content and handles the modal's behavior and form submission.
 *
 * @param {HTMLElement} triggerElement - The element that triggered the modal.
 */
export function openCategoryModal(triggerElement) {
    fetchModalContent('category', triggerElement).then(html => {
        if (html) {
            const modal = SwalModal.fire({
				target: document.getElementById("create-offcanvas") || document.body,
				title: "Agregar Nueva Categoría",
                showConfirmButton: false,
				showCancelButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
				html: html,
			});

            // Handle form dismissal when the modal is closed
            $(document)
                .off("click", "#cancel-category-form-button")
                .on("click", "#cancel-category-form-button", function () {
                    modal.close();
                });

            // Handle real-time validation for category form fields
            $(document)
                .off("input change", "#create-category-form")
                .on("input change", "#create-category-form", function (e) {
                    const $target = $(e.target);
                    if ($target.attr("id") === "category_name") {
                        realTimeValidationHandler(e, "name");
                    } else {
                        realTimeValidationHandler(e);
                    }
                });

            // Handle form submission within the modal
            $(document)
                .off("submit", "#create-category-form")
                .on("submit", "#create-category-form", async function (e) {
                    e.preventDefault();
                    setLoadingState("create-category-form", true);

                    if (submitCategoryForm("category_name")) {
                        await submitCategoryFormHandler(e, modal);
                    } else {
                        setLoadingState("create-category-form", false);
                    }
                });
        }
    });
};