import './bootstrap'; //<- Import Bootstrap and dependencies
import '@popperjs/core'; //<- Import Popper.js for Bootstrap tooltips and popovers
import 'sweetalert2/themes/bootstrap-5.css'; //<- Import SweetAlert2 Bootstrap 5 theme

import $ from 'jquery';
import ApexCharts from 'apexcharts';
import * as bootstrap from 'bootstrap';
import { applyTheme } from './utils/theme-toggler.js';
import { SwalNotificationTypes, SwalToast } from "./utils/sweetalert.js";
import { initializePageLoadingProgressBar } from './utils/progress-bar.js';
import { scrollToItem, checkScrollbarVisibility } from './utils/scrollbar.js';
import { checkConnectionStatus, updateConnectionStatus } from './utils/connection-status.js';
import {initializeCashClosure} from './pages/sales/cash-closure.js';

// ==================== Global Functions ====================

window.$ = $;
window.SwalToast = SwalToast;
window.bootstrap = bootstrap;
window.ApexCharts = ApexCharts; 
window.SwalNotificationTypes = SwalNotificationTypes; 
window.updateConnectionStatus = updateConnectionStatus;

// ==================== Main Initialization ====================

$(document).ready(function () {
	// Enable theme toggler button functionality
	applyTheme('#themeTogglerBtn');

	// Initialize page loading progress bar
	initializePageLoadingProgressBar();

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

	initializeCashClosure(); //<- Initialize cash closure page scripts if on that page
})