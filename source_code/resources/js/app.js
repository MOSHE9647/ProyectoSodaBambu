import './bootstrap'; //<- Import Bootstrap and dependencies
import '@popperjs/core'; //<- Import Popper.js for Bootstrap tooltips and popovers
import 'sweetalert2/themes/bootstrap-5.css'; //<- Import SweetAlert2 Bootstrap 5 theme

import $ from 'jquery';
import ApexCharts from 'apexcharts';
import * as bootstrap from 'bootstrap';
import { applyTheme } from './utils/theme-toggler.js';
import { scrollToItem, checkScrollbarVisibility } from './utils/scrollbar.js';
import { checkConnectionStatus, updateConnectionStatus } from './utils/connection-status.js';

window.$ = $; // Make jQuery globally available
window.bootstrap = bootstrap; // Make Bootstrap globally available
window.ApexCharts = ApexCharts; // Make ApexCharts globally available
window.updateConnectionStatus = updateConnectionStatus;

$(document).ready(function () {
	// Enable theme toggler button functionality
	applyTheme('#themeTogglerBtn');

	// Enable Bootstrap Tooltips
	$('[data-bs-toggle="tooltip"]')?.each(function () {
		new bootstrap.Tooltip(this);
	}) || console.warn('No tooltips found on the page.');

	// Scroll sidebar to active item
	scrollToItem();

	// Check if scrollbar is needed for sidebar navigation
	checkScrollbarVisibility();

	// Start checking connection status
	checkConnectionStatus();
})