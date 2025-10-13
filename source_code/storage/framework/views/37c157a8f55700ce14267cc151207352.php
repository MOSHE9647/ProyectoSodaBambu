<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" data-bs-theme="">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#28A745"/>

	<!-- Favicon -->
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('/favicon/apple-touch-icon.png')); ?>">
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('/favicon/favicon-32x32.png')); ?>">
	<link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('/favicon/favicon-16x16.png')); ?>">
	<link rel="manifest" href="<?php echo e(asset('/site.webmanifest')); ?>">

	<!-- CSRF Token -->
	<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

	<!-- Title -->
	<title><?php echo e(config('app.name', 'Laravel')); ?></title>

	<!-- Theme Script -->
		<script nonce>
		(function () {
			function getTheme() {
				const stored = localStorage.getItem('theme');
				if (stored === 'dark' || stored === 'light') return stored;
				return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
			}

			const theme = getTheme();
			document.documentElement.setAttribute('data-bs-theme', theme);
		})();
	</script>

	<!-- Scripts -->
	<?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>
	<div id="app">
		
		<?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>

		
		<main class="py-4">
			<?php echo $__env->yieldContent('content'); ?>
		</main>
	</div>

	
	<script src="<?php echo e(asset('/sw.js')); ?>"></script>
	<script src="<?php echo e(asset('/scripts/registerServiceWorker.js')); ?>"></script>
</body>
</html>
<?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/layouts/app.blade.php ENDPATH**/ ?>