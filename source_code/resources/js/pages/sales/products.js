import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert";

/**
 * Initializes the sales products UI behavior, including:
 * - DOM element validation and setup
 * - Product fetching with pagination
 * - Infinite scroll via `IntersectionObserver`
 * - Debounced search and category filtering
 * - Skeleton loading states and error notifications
 *
 * This function wires all required event listeners and performs an initial
 * check for the server-side "has more pages" indicator to control sentinel visibility.
 *
 * @function initializeSalesProducts
 * @returns {void}
 */
export const initializeSalesProducts = () => {
	// DOM Elements Cache and Validation
	const searchInput = document.getElementById("product-search");
	const categoryTabsContainer = document.getElementById("category-tabs-container");
	const clearCategoryFilterButton = document.getElementById("clear-category-filter");
	const productsContainer = document.getElementById("products-grid");
	const sentinel = document.getElementById("products-scroll-sentinel");
	const skeletonTemplate = document.getElementById("skeleton-template");

	// Guard Clause: If critical elements are missing, abort initialization and notify the user
	if (!productsContainer || !searchInput || !sentinel || !categoryTabsContainer || !clearCategoryFilterButton) {
        console.error("Error al inicializar productos. No se encontraron los elementos necesarios.");
		SwalToast.fire({
			icon: SwalNotificationTypes.ERROR,
			title: "No se encontraron los elementos necesarios para mostrar la lista de productos.",
		});
		return;
    }

	// Grouping state in a single object for better management and readability
	const state = {
		currentPage: 1,
		isFetching: false,
		hasMorePages: false,
		selectedCategoryId: "",
	};

	const categoryTabSelector = 'button[id^="category-tab-"]';

	const getCategoryTabs = () =>
		Array.from(categoryTabsContainer.querySelectorAll(categoryTabSelector));

	const extractCategoryIdFromTab = (tab) =>
		tab?.id?.replace("category-tab-", "") ?? "";

	const updateClearCategoryFilterButtonState = () => {
		clearCategoryFilterButton.disabled = !state.selectedCategoryId;
	};

	const resetCategoryTabs = () => {
		getCategoryTabs().forEach((tab) => {
			tab.classList.remove("active");
		});
		state.selectedCategoryId = "";
		updateClearCategoryFilterButtonState();
	};

	const setActiveCategoryTab = (tab) => {
		if (!tab) {
			resetCategoryTabs();
			return;
		}

		getCategoryTabs().forEach((tabElement) => {
			tabElement.classList.toggle("active", tabElement === tab);
		});

		state.selectedCategoryId = extractCategoryIdFromTab(tab);
		updateClearCategoryFilterButtonState();
	};

	// Auxiliary function to update sentinel visibility based on the presence of more pages
	const updateSentinelVisibility = () => {
		sentinel.classList.toggle("d-flex", state.hasMorePages);
		sentinel.classList.toggle("d-none", !state.hasMorePages);
	};

	// Centralized function to check for the server-side "has more pages" indicator and update state/UI accordingly
	const checkAndRemoveMoreIndicator = () => {
		const indicator = productsContainer.querySelector("#has-more-pages");
		state.hasMorePages = !!indicator;
		if (indicator) indicator.remove();
		updateSentinelVisibility();
	};

	// Auxiliary function to toggle skeleton loading states, with an option to append or replace existing content
	const toggleSkeletons = (show, append = false) => {
		if (show) {
			if (!append) productsContainer.innerHTML = ""; // Clear content only if not appending
			const skeletons = skeletonTemplate.content.cloneNode(true);
			productsContainer.appendChild(skeletons);
		} else {
			const skeletons =
				productsContainer.querySelectorAll(".product-skeleton");
			skeletons.forEach((s) => s.remove());
		}
	};

	// Debounce function for reutilizable and isolated use
	const debounce = (func, delay) => {
		let timeout;
		return (...args) => {
			clearTimeout(timeout);
			timeout = setTimeout(() => func(...args), delay);
		};
	};

	// Core function to fetch products based on current state (page, search, category), with error handling and UI updates
	const fetchProducts = async (append = false) => {
		if (state.isFetching) return;

		state.isFetching = true;
		toggleSkeletons(true, append);

		// URL parameters construction based on current state and input values
		const params = new URLSearchParams({
			page: state.currentPage,
			search: searchInput.value,
			category_id: state.selectedCategoryId,
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

	// Event handler for search input and category change, debounced to prevent excessive requests during typing or rapid changes
	const handleNewSearch = debounce(() => {
		state.currentPage = 1;
		state.hasMorePages = true; // Assuming new search may have more pages until we check the indicator again
		window.scrollTo({ top: 0, behavior: "smooth" });
		fetchProducts(false);
	}, 400);

	const handleCategoryTabClick = (event) => {
		const clickedTab = event.target.closest(categoryTabSelector);
		if (!clickedTab || !categoryTabsContainer.contains(clickedTab)) {
			return;
		}

		setActiveCategoryTab(clickedTab);
		handleNewSearch();
	};

	const handleClearCategoryFilter = () => {
		resetCategoryTabs();
		handleNewSearch();
	};

	const refreshProductsAfterSale = () => {
		state.currentPage = 1;
		state.hasMorePages = true;
		fetchProducts(false);
	};

	// Event handler for infinite scroll using IntersectionObserver, triggers product fetching when sentinel is in view and conditions are met
	const handleScroll = (entries) => {
		const [entry] = entries;
		if (entry.isIntersecting && !state.isFetching && state.hasMorePages) {
			state.currentPage++;
			fetchProducts(true);
		}
	};

	// Initial check for the "has more pages" indicator to set up the initial state and sentinel visibility correctly before any user interaction
	checkAndRemoveMoreIndicator(); // Initial check on page load to set sentinel visibility based on server-side indicator

	const initiallyActiveCategoryTab = getCategoryTabs().find((tab) =>
		tab.classList.contains("active"),
	);
	if (initiallyActiveCategoryTab) {
		setActiveCategoryTab(initiallyActiveCategoryTab);
	} else {
		resetCategoryTabs();
	}

	searchInput.addEventListener("input", handleNewSearch);
	categoryTabsContainer.addEventListener("click", handleCategoryTabClick);
	clearCategoryFilterButton.addEventListener("click", handleClearCategoryFilter);
	window.addEventListener("sales:refresh-products-after-sale", refreshProductsAfterSale);

	const scrollObserver = new IntersectionObserver(handleScroll, {
		rootMargin: "200px",
	});
	scrollObserver.observe(sentinel);
};