import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert";

export const initializeSalesProducts = () => {
	// 1. Caché de elementos del DOM
	const searchInput = document.getElementById("product-search");
	const categorySelect = document.getElementById("category-select");
	const productsContainer = document.getElementById("products-grid");
	const sentinel = document.getElementById("products-scroll-sentinel");
	const skeletonTemplate = document.getElementById("skeleton-template");

	// Cláusula de guarda: Si no existen los elementos críticos, abortamos la inicialización
	if (!productsContainer || !searchInput || !categorySelect || !sentinel) {
        console.error("Error al inicializar productos. No se encontraron los elementos necesarios.");
		SwalToast.fire({
			icon: SwalNotificationTypes.ERROR,
			title: "No se encontraron los elementos necesarios para mostrar la lista de productos.",
		});
		return;
    }

	// 2. Agrupación del estado para mayor claridad
	const state = {
		currentPage: 1,
		isFetching: false,
		hasMorePages: false,
	};

	// 3. Funciones Utilitarias (DOM y Lógica)
	const updateSentinelVisibility = () => {
		sentinel.classList.toggle("d-flex", state.hasMorePages);
		sentinel.classList.toggle("d-none", !state.hasMorePages);
	};

	// Centraliza la validación y limpieza del indicador de Laravel
	const checkAndRemoveMoreIndicator = () => {
		const indicator = productsContainer.querySelector("#has-more-pages");
		state.hasMorePages = !!indicator;
		if (indicator) indicator.remove();
		updateSentinelVisibility();
	};

	const toggleSkeletons = (show, append = false) => {
		if (show) {
			if (!append) productsContainer.innerHTML = ""; // Limpiar si es nueva búsqueda
			const skeletons = skeletonTemplate.content.cloneNode(true);
			productsContainer.appendChild(skeletons);
		} else {
			const skeletons =
				productsContainer.querySelectorAll(".product-skeleton");
			skeletons.forEach((s) => s.remove());
		}
	};

	// Función debounce reutilizable y aislada
	const debounce = (func, delay) => {
		let timeout;
		return (...args) => {
			clearTimeout(timeout);
			timeout = setTimeout(() => func(...args), delay);
		};
	};

	// 4. Función principal de Fetch
	const fetchProducts = async (append = false) => {
		if (state.isFetching) return;

		state.isFetching = true;
		toggleSkeletons(true, append);

		// Generación limpia de parámetros de URL
		const params = new URLSearchParams({
			page: state.currentPage,
			search: searchInput.value,
			category_id: categorySelect.value,
		});

		try {
			const response = await fetch(`/sales/sell?${params.toString()}`, {
				headers: { "X-Requested-With": "XMLHttpRequest" },
			});

			if (!response.ok)
				throw new Error(`Error en la red: ${response.status}`);

			const html = await response.text();

			toggleSkeletons(false);

			if (append) {
				productsContainer.insertAdjacentHTML("beforeend", html);
			} else {
				productsContainer.innerHTML = html;
			}

			checkAndRemoveMoreIndicator();
		} catch (error) {
			console.error("Error cargando productos:", error);
            SwalToast.fire({
                icon: SwalNotificationTypes.ERROR,
                title: "Ocurrió un error al obtener la lista de productos. Intenta nuevamente.",
            });

			toggleSkeletons(false);
		} finally {
			state.isFetching = false;
		}
	};

	// 5. Manejadores de Eventos (Handlers)
	const handleNewSearch = debounce(() => {
		state.currentPage = 1;
		state.hasMorePages = true; // Asumimos true temporalmente hasta que el fetch lo valide
		window.scrollTo({ top: 0, behavior: "smooth" });
		fetchProducts(false);
	}, 400);

	const handleScroll = (entries) => {
		const [entry] = entries;
		if (entry.isIntersecting && !state.isFetching && state.hasMorePages) {
			state.currentPage++;
			fetchProducts(true);
		}
	};

	// 6. Inicialización y Asignación de Listeners
	checkAndRemoveMoreIndicator(); // Validación inicial al cargar la vista

	searchInput.addEventListener("input", handleNewSearch);
	categorySelect.addEventListener("change", handleNewSearch);

	const scrollObserver = new IntersectionObserver(handleScroll, {
		rootMargin: "200px",
	});
	scrollObserver.observe(sentinel);
};