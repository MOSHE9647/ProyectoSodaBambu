import { initializeSalesProducts } from "./products.js";
import { initializeSalesCart } from "./cart.js";

$(() => {
    initializeSalesProducts();
    initializeSalesCart();
});
