import './bootstrap';
import '@popperjs/core';

import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import {applyTheme} from "./utils/themeToggler.js";

window.$ = $; // Make jQuery globally available
window.bootstrap = bootstrap; // Make Bootstrap globally available

$(document).ready(function () {
	// Enable theme toggler button functionality
	applyTheme('#themeTogglerBtn');

	// Enable Bootstrap Tooltips
	$('[data-bs-toggle="tooltip"]')?.each(function () {
		new bootstrap.Tooltip(this);
	}) || console.warn('No tooltips found on the page.');
})
