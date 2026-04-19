import { initializeSalesCart, getActiveSaleData } from "./cart.js";
import { initializeSalesProducts } from "./products.js";
import { initializeSalesOrderTabs } from "./orders.js";

const SaleData = {
	payment_status: "paid",
	date: new Date().toISOString(),
	total: 8500,
	sale_details: [
		{
			product_id: 8,
			quantity: 2,
			unit_price: 4250,
			applied_tax: 0,
			sub_total: 8500,
		},
	],
	payment_details: [
		{
			method: "sinpe",
			amount: 5000,
			reference: "99887788",
		},
		{
			method: "card",
			amount: 3500,
			reference: "123456789012",
		},
		{
			method: "cash",
			amount: 3500,
			change_amount: 0,
		},
	],
};

const paymentTest = async () => {
	console.log("GetActiveSaleData output:", getActiveSaleData());
    try {
		SaleData.sale_details = getActiveSaleData().sale_details;
		SaleData.total = getActiveSaleData().total;
		const amountPerPayment = SaleData.total / SaleData.payment_details.length;
		SaleData.payment_details = SaleData.payment_details.map((payment) => ({
			...payment,
			amount: amountPerPayment,
		}));

        const response = await fetch("/sales", {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				"X-Requested-With": "XMLHttpRequest",
				"X-CSRF-TOKEN": csrfToken,
			},
			body: JSON.stringify(SaleData),
		});
		
		if (response.ok) {
			const responseData = await response.json();
			alert(JSON.stringify(responseData, null, 2));
		} else {
			const errorData = await response.json();
			alert(`Error ${response.status}: ${errorData.message}`);
		}
    } catch (error) {
        console.error("Error during test sale flow:", error);
    }
};

$(() => {
    initializeSalesProducts();
    initializeSalesCart();
	initializeSalesOrderTabs();

    const finalizeSaleButton = $("#finalize-sale-btn");
    if (finalizeSaleButton.length) {
        finalizeSaleButton.on("click", function () {
            // Aquí puedes agregar la lógica para finalizar la venta, como mostrar un resumen o enviar los datos al servidor
            paymentTest();
        });
    }
});
