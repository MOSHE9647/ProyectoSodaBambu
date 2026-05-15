// ==================== Environment Checks ====================

// Ensure jQuery is loaded
if (typeof $ === "undefined") {
	throw new Error("This script requires jQuery");
}

// ======================== Constants =========================

const defaultOptions = {
	backdrop: "static",
	keyboard: false,
	showCloseButton: true,
	verticallyCentered: true,
	scrollable: false,
	modalClass: "",
	modalStyle: "",
};

// ===================== Helper Functions =====================

/**
 * Generates the HTML structure for a Bootstrap modal.
 * 
 * @param {string} modalId - The unique identifier for the modal element.
 * @param {string} modalTitle - The text to be displayed in the modal header.
 * @param {string} modalContent - The HTML content to be placed inside the modal body.
 * @param {Object} options - Configuration overrides for the modal (backdrop, keyboard, classes, etc.).
 * @returns {string} The complete HTML string for the Bootstrap modal.
 */
const createBootstrapModal = (modalId = "myModal", modalTitle = "Modal Title", modalContent = "...", options = {}) => {
	const modalOptions = { ...defaultOptions, ...options };

	return `
    <div class="modal fade" id="${modalId}" data-bs-backdrop="${modalOptions.backdrop}" data-bs-keyboard="${modalOptions.keyboard}" tabindex="-1" aria-labelledby="${modalId}-label" aria-hidden="true">
        <div class="modal-dialog ${modalOptions.verticallyCentered} ${modalOptions.scrollable} ${modalOptions.modalClass}" style="${modalOptions.modalStyle}">
            <div class="modal-content">
                <div class="modal-header ps-4 pe-4">
                    <h1 class="modal-title fs-3" id="${modalId}-label">${modalTitle}</h1>
                    ${modalOptions.showCloseButton ? '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' : ""}
                </div>
                <div class="modal-body">
                    ${modalContent}
                </div>
            </div>
        </div>
    </div>
    `;
};

// ====================== Modal Functions =====================

/**
 * Creates, appends, and displays a Bootstrap modal.
 * 
 * @param {string} modalId - The unique identifier for the modal element.
 * @param {string} modalTitle - The text to be displayed in the modal header.
 * @param {string} modalContent - The HTML content to be placed inside the modal body.
 * @param {Object} options - Configuration overrides for the modal (backdrop, keyboard, classes, etc.) with the following possible properties:
 *   - backdrop: (string) The backdrop behavior ('static', true, false). Default is 'static'.
 *   - keyboard: (boolean) Whether the modal should close when the escape key is pressed. Default is false.
 *   - showCloseButton: (boolean) Whether to include a close button in the modal header. Default is true.
 *   - verticallyCentered: (boolean) Whether to vertically center the modal. Default is true.
 *   - scrollable: (boolean) Whether the modal body should be scrollable. Default is false.
 *   - modalClass: (string) Additional CSS classes to apply to the modal dialog. Default is "".
 *   - modalStyle: (string) Inline CSS styles to apply to the modal dialog. Default is "".
 * @returns {bootstrap.Modal} The Bootstrap modal instance.
 */
export const showBootstrapModal = (modalId, modalTitle, modalContent, options = {}) => {
	// Remove any existing modal with the same ID to prevent duplicates
    const existingModal = $(`#${modalId}`);
    if (existingModal.length) {
        existingModal.remove();
    }

    // Create and append the new modal to the body
    const modalHtml = createBootstrapModal(modalId, modalTitle, modalContent, options);
    $("body").append(modalHtml);

    // Show the modal using Bootstrap's JavaScript API
    const modalElement = document.getElementById(modalId);
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();

    // Return the modal instance for further manipulation if needed
    return modalInstance;
};
