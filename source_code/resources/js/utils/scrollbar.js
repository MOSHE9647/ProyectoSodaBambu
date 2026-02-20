export function scrollToItem() {
	const sidebarActiveItem = $('#sidebar .nav-link.active');
	if (sidebarActiveItem.length) {
		sidebarActiveItem[0].scrollIntoView({
			behavior: 'smooth',
			block: 'nearest'
		});
	}
}

export function checkScrollbarVisibility() {
	const nav = $('#sidebar .nav');
	if (!nav.length) return;

	function checkScrollbar() {
		if (nav[0].scrollHeight > nav[0].clientHeight) {
			nav[0].classList.add('scrollbar-visible');
		} else {
			nav[0].classList.remove('scrollbar-visible');
		}
		setTimeout(scrollToItem, 400);
	}

	checkScrollbar();

	// Observa cambios en el tamaño del propio nav
	const observer = new ResizeObserver(() => checkScrollbar());
	observer.observe(nav[0]);

	// Por si el contenido cambia o se carga dinámicamente
	$(window).on('resize', () => requestAnimationFrame(checkScrollbar));
}
