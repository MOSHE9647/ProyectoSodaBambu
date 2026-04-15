document.addEventListener("DOMContentLoaded", () => {
	const searchInput = document.getElementById("product-search");
	const categorySelect = document.getElementById("category-select");
	const productsContainer = document.getElementById("products-grid");
	const sentinel = document.getElementById("products-scroll-sentinel");
	const skeletonTemplate = document.getElementById("skeleton-template");

	let currentPage = 1;
	let searchTimeout = null;
	let isFetching = false; // Evita que se hagan múltiples peticiones a la vez
	
	// Validar estado inicial consultando si Laravel incrustó el indicador en el DOM
	const initialMoreIndicator = productsContainer.querySelector("#has-more-pages");
	let hasMorePages = !!initialMoreIndicator;
	if (initialMoreIndicator) initialMoreIndicator.remove();
	
	// Ajustar visibilidad inicial del centinela
	sentinel.classList.toggle("d-flex", hasMorePages);
	sentinel.classList.toggle("d-none", !hasMorePages);

	// Función para mostrar los skeletons
	const showSkeletons = (append = false) => {
		const skeletons = skeletonTemplate.content.cloneNode(true);
		if (!append) {
			productsContainer.innerHTML = ""; // Limpiar todo para nueva búsqueda
		}
		productsContainer.appendChild(skeletons);
	};

	// Función para quitar los skeletons
	const removeSkeletons = () => {
		const skeletons =
			productsContainer.querySelectorAll(".product-skeleton");
		skeletons.forEach((s) => s.remove());
	};

	// Función principal de Fetch
	const fetchProducts = async (page, search, category, append = false) => {
		if (isFetching) return;

		isFetching = true;
		showSkeletons(append);

		const url = `/sales?page=${page}&search=${encodeURIComponent(search)}&category_id=${encodeURIComponent(category)}`;

		try {
			const response = await fetch(url, {
				headers: { "X-Requested-With": "XMLHttpRequest" },
			});

			if (!response.ok) throw new Error("Error en la red");

			const html = await response.text();

            removeSkeletons();

			// Inyectar el HTML
			if (append) {
				productsContainer.insertAdjacentHTML("beforeend", html);
			} else {
				productsContainer.innerHTML = html;
			}

			// Verificar si hay más páginas buscando el div oculto
			const moreIndicator = productsContainer.querySelector("#has-more-pages");
			hasMorePages = !!moreIndicator;

			// Limpiar el DOM eliminando el indicador oculto
			if (moreIndicator) moreIndicator.remove();

			// Si ya no hay más páginas, ocultar el centinela por completo
			sentinel.classList.toggle("d-flex", hasMorePages);
			sentinel.classList.toggle("d-none", !hasMorePages);
		} catch (error) {
			console.error("Error cargando productos:", error);
            removeSkeletons();
		} finally {
			isFetching = false;
		}
	};

	// Observer para Infinite Scroll
    const scrollObserver = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !isFetching && hasMorePages) {
            currentPage++;
            fetchProducts(currentPage, searchInput.value, categorySelect.value, true);
        }
    }, { rootMargin: '200px' }); // Cargamos un poco antes (200px)

    if (sentinel) scrollObserver.observe(sentinel);

	// Disparador de nueva búsqueda o filtro
    const triggerNewSearch = () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            hasMorePages = true;
            // Scroll arriba suave al buscar
            window.scrollTo({ top: 0, behavior: 'smooth' });
            fetchProducts(currentPage, searchInput.value, categorySelect.value, false);
        }, 400); // 400ms de debounce
    };

	searchInput.addEventListener('input', triggerNewSearch);
    categorySelect.addEventListener('change', triggerNewSearch);
});
