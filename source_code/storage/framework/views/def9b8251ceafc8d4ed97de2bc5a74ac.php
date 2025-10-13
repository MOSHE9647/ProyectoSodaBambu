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
	<?php echo app('Illuminate\Foundation\Vite')(['resources/css/auth.css', 'resources/js/app.js']); ?>
</head>
<body>
	<div id="app"
		 class="container auth-container w-100 d-flex flex-column align-items-center justify-content-center position-relative rounded-4">
		<div class="container p-0">
			
			<?php if (isset($component)) { $__componentOriginal741885c129e61adfe6cf71bd08c056a3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal741885c129e61adfe6cf71bd08c056a3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.theme-toggle','data' => ['class' => 'border rounded-circle position-absolute top-0 end-0 m-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.theme-toggle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'border rounded-circle position-absolute top-0 end-0 m-3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal741885c129e61adfe6cf71bd08c056a3)): ?>
<?php $attributes = $__attributesOriginal741885c129e61adfe6cf71bd08c056a3; ?>
<?php unset($__attributesOriginal741885c129e61adfe6cf71bd08c056a3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal741885c129e61adfe6cf71bd08c056a3)): ?>
<?php $component = $__componentOriginal741885c129e61adfe6cf71bd08c056a3; ?>
<?php unset($__componentOriginal741885c129e61adfe6cf71bd08c056a3); ?>
<?php endif; ?>

			
			<?php echo $__env->yieldContent('content'); ?>
		</div>
	</div>

	
	<?php echo $__env->yieldContent('js'); ?>

	
	<script src="<?php echo e(asset('/sw.js')); ?>"></script>
	<script src="<?php echo e(asset('/scripts/registerServiceWorker.js')); ?>"></script>
</body>
</html>
<?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/layouts/auth.blade.php ENDPATH**/ ?>