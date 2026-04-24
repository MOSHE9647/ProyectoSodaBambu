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
	// DOM elements cache and validation.
	const searchInput = document.getElementById("product-search");
	const categoryTabsContainer = document.getElementById("category-tabs-container");
	const clearCategoryFilterButton = document.getElementById("clear-category-filter");
	const productsContainer = document.getElementById("products-grid");
	const sentinel = document.getElementById("products-scroll-sentinel");
	const skeletonTemplate = document.getElementById("skeleton-template");

	// Guard clause: abort initialization if any critical element is missing.
	if (!productsContainer || !searchInput || !sentinel || !categoryTabsContainer || !clearCategoryFilterButton) {
        console.error("Error al inicializar productos. No se encontraron los elementos necesarios.");
		SwalToast.fire({
			icon: SwalNotificationTypes.ERROR,
			title: "No se encontraron los elementos necesarios para mostrar la lista de productos.",
		});
		return;
    }

	// Keep module state in a single object for readability and maintainability.
	const state = {
		currentPage: 1,
		isFetching: false,
		hasMorePages: false,
		selectedCategoryId: "",
	};

	const categoryTabSelector = 'button[id^="category-tab-"]';

	/**
	 * Returns all category tab buttons.
	 *
	 * @returns {HTMLButtonElement[]}
	 */
	const getCategoryTabs = () =>
		Array.from(categoryTabsContainer.querySelectorAll(categoryTabSelector));

	/**
	 * Extracts category id from a tab id.
	 *
	 * @param {HTMLElement|null} tab
	 * @returns {string}
	 */
	const extractCategoryIdFromTab = (tab) =>
		tab?.id?.replace("category-tab-", "") ?? "";

	/**
	 * Enables/disables the clear category filter button.
	 *
	 * @returns {void}
	 */
	const updateClearCategoryFilterButtonState = () => {
		clearCategoryFilterButton.disabled = !state.selectedCategoryId;
	};

	/**
	 * Clears active state from all category tabs.
	 *
	 * @returns {void}
	 */
	const resetCategoryTabs = () => {
		getCategoryTabs().forEach((tab) => {
			tab.classList.remove("active");
		});
		state.selectedCategoryId = "";
		updateClearCategoryFilterButtonState();
	};

	/**
	 * Activates the selected category tab and updates filter state.
	 *
	 * @param {HTMLElement|null} tab
	 * @returns {void}
	 */
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

	// Update sentinel visibility based on pagination state.
	const updateSentinelVisibility = () => {
		sentinel.classList.toggle("d-flex", state.hasMorePages);
		sentinel.classList.toggle("d-none", !state.hasMorePages);
	};

	// Read and remove server-side pagination indicator, then sync state/UI.
	const checkAndRemoveMoreIndicator = () => {
		const indicator = productsContainer.querySelector("#has-more-pages");
		state.hasMorePages = !!indicator;
		if (indicator) indicator.remove();
		updateSentinelVisibility();
	};

	// Toggle loading skeletons, either appending or replacing existing content.
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

	/**
	 * Debounces a function call.
	 *
	 * @param {Function} func
	 * @param {number} delay
	 * @returns {(...args: any[]) => void}
	 */
	const debounce = (func, delay) => {
		let timeout;
		return (...args) => {
			clearTimeout(timeout);
			timeout = setTimeout(() => func(...args), delay);
		};
	};

	/**
	 * Fetches products using current filters and pagination.
	 *
	 * @param {boolean} [append=false]
	 * @returns {Promise<void>}
	 */
	const fetchProducts = async (append = false) => {
		if (state.isFetching) return;

		state.isFetching = true;
		toggleSkeletons(true, append);

		// Build request parameters using current filters and page.
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

	// Debounced handler for search/category changes.
	const handleNewSearch = debounce(() => {
		state.currentPage = 1;
		state.hasMorePages = true; // Assuming new search may have more pages until we check the indicator again
		window.scrollTo({ top: 0, behavior: "smooth" });
		fetchProducts(false);
	}, 400);

	/**
	 * Handles category tab clicks using event delegation.
	 *
	 * @param {MouseEvent} event
	 * @returns {void}
	 */
	const handleCategoryTabClick = (event) => {
		const clickedTab = event.target.closest(categoryTabSelector);
		if (!clickedTab || !categoryTabsContainer.contains(clickedTab)) {
			return;
		}

		setActiveCategoryTab(clickedTab);
		handleNewSearch();
	};

	/**
	 * Clears active category filter and refreshes products.
	 *
	 * @returns {void}
	 */
	const handleClearCategoryFilter = () => {
		resetCategoryTabs();
		handleNewSearch();
	};

	/**
	 * Reloads products after a sale is completed.
	 *
	 * @returns {void}
	 */
	const refreshProductsAfterSale = () => {
		state.currentPage = 1;
		state.hasMorePages = true;
		fetchProducts(false);
	};

	/**
	 * Handles infinite scroll intersection updates.
	 *
	 * @param {IntersectionObserverEntry[]} entries
	 * @returns {void}
	 */
	const handleScroll = (entries) => {
		const [entry] = entries;
		if (entry.isIntersecting && !state.isFetching && state.hasMorePages) {
			state.currentPage++;
			fetchProducts(true);
		}
	};

	// Initial state sync before user interaction.
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