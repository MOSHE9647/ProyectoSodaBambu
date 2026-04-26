import { SwalToast } from "../../utils/sweetalert";
import { setLoadingState } from "../../utils/utils";
import {
	realTimeValidationHandler,
	submitSupplierForm,
} from "../suppliers/form";

const submitSupplierFormHandler = async (e, offcanvasInstance) => {
	try {
		const form = e.currentTarget;
		const formData = new FormData(form);

		const data = {};
		formData.forEach((value, key) => {
			if (Object.prototype.hasOwnProperty.call(data, key)) {
				if (!Array.isArray(data[key])) data[key] = [data[key]];
				data[key].push(value);
			} else {
				data[key] = value;
			}
		});

		const response = await fetch(form.action, {
			method: form.method,
			headers: {
				"Content-Type": "application/json",
				"X-Requested-With": "XMLHttpRequest",
				"X-CSRF-TOKEN":
					typeof csrfToken !== "undefined" ? csrfToken : "",
			},
			body: JSON.stringify(data),
		});

		// Manejo específico para errores de validación de Laravel (422)
		if (response.status === 422) {
			const errorData = await response.json();
			// Toma el primer error del primer campo que falló
			const firstError = Object.values(errorData.errors)[0][0];
			throw new Error(firstError);
		}

		if (!response.ok)
			throw new Error(
				`Error inesperado del servidor (${response.status})`,
			);

		const responseData = await response.json();

		// Actualizar el DOM si existe el select
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
		console.error("Form submission failed:", error);
		SwalToast.fire({
			icon: "error",
			title: error.message || "Error al crear proveedor.",
		});
	} finally {
		setLoadingState("create-supplier-form", false);
	}
};

export function handleSupplierFormEvents(offcanvasInstance) {
	$(document)
		.off("submit", "#create-supplier-form")
		.on("submit", "#create-supplier-form", async function (e) {
			e.preventDefault();
			setLoadingState("create-supplier-form", true);

			if (submitSupplierForm()) {
				await submitSupplierFormHandler(e, offcanvasInstance);
			} else {
				setLoadingState("create-supplier-form", false);
			}
		});

	$(document)
		.off("input change", "#create-supplier-form")
		.on("input change", "#create-supplier-form", function (e) {
			realTimeValidationHandler(e);
		});
}