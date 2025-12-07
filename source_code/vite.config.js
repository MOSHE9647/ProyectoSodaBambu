import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import glob from 'fast-glob';

// // --- AGREGAR ESTAS LÍNEAS PARA DEPURAR ---
// const authFiles = glob.sync('resources/js/auth/**/*.js');
// const modelFiles = glob.sync('resources/js/models/**/main.js');

// console.log('--- DEPURACIÓN VITE ---');
// console.log('Archivos de Auth encontrados:', authFiles);
// console.log('Archivos de Models encontrados:', modelFiles);
// console.log('-----------------------');
// // -----------------------------------------

export default defineConfig({
	plugins: [
		laravel({
			input: [
				// Laravel Vite main files
				'resources/css/app.css', 
				'resources/js/app.js',

				// Include all related JS files
				...glob.sync('resources/js/**/*.js'),

				// Include all CSS files in resources/css directory
				...glob.sync('resources/css/**/*.css'),
			],
			refresh: true,
		}),
	],
});
