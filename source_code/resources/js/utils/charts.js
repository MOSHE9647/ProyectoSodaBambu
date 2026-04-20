/**
 * Returns the current Bootstrap theme from the HTML attribute.
 * @returns {"light"|"dark"|string}
 */
export function getCurrentTheme() {
	return document.documentElement.getAttribute('data-bs-theme') || 'light';
}

/**
 * Deeply merges plain objects while replacing arrays and primitives.
 * @param {Record<string, any>} target
 * @param {Record<string, any>} source
 * @returns {Record<string, any>}
 */
function deepMerge(target, source) {
	const output = { ...target };

	Object.keys(source || {}).forEach((key) => {
		const sourceValue = source[key];
		const targetValue = output[key];

		const isSourceObject = sourceValue && typeof sourceValue === 'object' && !Array.isArray(sourceValue);
		const isTargetObject = targetValue && typeof targetValue === 'object' && !Array.isArray(targetValue);

		if (isSourceObject && isTargetObject) {
			output[key] = deepMerge(targetValue, sourceValue);
			return;
		}

		output[key] = sourceValue;
	});

	return output;
}

/**
 * Builds default options for the dashboard income area chart.
 * Consumers can extend these options through the `options` parameter.
 * @param {Object} params
 * @param {string[]} params.labels
 * @param {number[]} params.values
 * @param {string} params.axisTitle
 * @param {string} params.theme
 * @returns {Object}
 */
export function buildIncomeAreaChartOptions({ labels, values, axisTitle, theme }) {
	return {
		series: [{
			name: 'Ingresos',
			data: values,
		}],
		xaxis: {
			categories: labels,
			title: { text: axisTitle },
		},
		chart: {
			type: 'area',
			height: 200,
			fontFamily: 'inherit',
			background: 'transparent',
			toolbar: {
				show: true,
				tools: {
					download: true,
					selection: true,
					zoom: true,
					zoomin: true,
					zoomout: true,
					pan: true,
					reset: true,
				},
				autoSelected: 'pan',
			},
			sparkline: { enabled: true },
		},
		theme: { mode: theme },
		stroke: {
			curve: 'smooth',
			width: 2,
		},
		fill: {
			type: 'gradient',
			gradient: {
				shadeIntensity: 1,
				opacityFrom: 0.7,
				opacityTo: 0.3,
				stops: [0, 90, 100],
			},
		},
		colors: ['#198754'],
		tooltip: {
			theme,
			y: {
				formatter: function (val) {
					return '₡ ' + val.toLocaleString();
				},
			},
		},
	};
}

/**
 * Creates and renders an ApexChart instance.
 * @param {Object} params
 * @param {string|HTMLElement} params.container
 * @param {Object} params.options
 * @param {typeof ApexCharts} [params.chartLibrary]
 * @returns {ApexCharts|null}
 */
export function createApexChart({ container, options, chartLibrary = window.ApexCharts }) {
	const target = typeof container === 'string' ? document.querySelector(container) : container;

	if (!target || !chartLibrary) {
		return null;
	}

	const chart = new chartLibrary(target, options);
	chart.render();

	return chart;
}

/**
 * Creates the default income area chart used in dashboard-like views.
 * Keeps the current behavior by default, while allowing option overrides.
 * @param {Object} params
 * @param {string|HTMLElement} params.container
 * @param {string[]} params.labels
 * @param {number[]} params.values
 * @param {string} params.axisTitle
 * @param {Object} [params.options]
 * @param {() => string} [params.getTheme]
 * @param {typeof ApexCharts} [params.chartLibrary]
 * @returns {ApexCharts|null}
 */
export function createIncomeAreaChart({
	container,
	labels = [],
	values = [],
	axisTitle = '',
	options = {},
	getTheme = getCurrentTheme,
	chartLibrary,
}) {
	const theme = getTheme();
	const defaultOptions = buildIncomeAreaChartOptions({ labels, values, axisTitle, theme });
	const mergedOptions = deepMerge(defaultOptions, options);

	return createApexChart({
		container,
		options: mergedOptions,
		chartLibrary,
	});
}

/**
 * Observes theme changes and updates chart theme/tooltip mode.
 * @param {ApexCharts[]} charts
 * @param {Object} [params]
 * @param {string} [params.themeAttribute]
 * @param {() => string} [params.getTheme]
 * @returns {MutationObserver|null}
 */
export function observeThemeChanges(
	charts,
	{
		themeAttribute = 'data-bs-theme',
		getTheme = getCurrentTheme,
	} = {}
) {
	if (!Array.isArray(charts) || charts.length === 0) {
		return null;
	}

	const observer = new MutationObserver(function (mutations) {
		const themeChanged = mutations.some(function (mutation) {
			return mutation.attributeName === themeAttribute;
		});

		if (!themeChanged) {
			return;
		}

		const newTheme = getTheme();

		charts.forEach(function (chart) {
			chart.updateOptions({
				theme: { mode: newTheme },
				tooltip: { theme: newTheme },
			});
		});
	});

	observer.observe(document.documentElement, {
		attributes: true,
		attributeFilter: [themeAttribute],
	});

	return observer;
}
