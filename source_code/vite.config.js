import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import glob from 'fast-glob';

export default defineConfig({
	plugins: [
		laravel({
			input: [
				// Laravel Vite main files
				'resources/css/app.css', 
				'resources/js/app.js',

				// Include all related JS files in resources/js directory
				...glob.sync('resources/js/**/*.js'),

				// Include all related CSS files in resources/css directory
				...glob.sync('resources/css/**/*.css'),
			],
			refresh: true,
		}),
	],
});
