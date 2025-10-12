<?php $__env->startSection('content'); ?>
	
	<div class="container w-100 d-flex flex-column align-items-center justify-content-center text-center mb-2">
		
		
		<div
			class="brand-logo d-flex flex-column align-items-center justify-content-center border rounded-circle mb-3"
			style="width: 80px; height: 80px;"
		>
			<i
				class="bi bi-key rounded-circle d-flex align-items-center justify-content-center"
				style="font-size: 2rem; color: white; width: 36px; height: 36px"
			></i>
		</div>
		
		
		<h1 class="brand-title">Recuperar Contraseña</h1>
		<p class="brand-subtitle">Ingrese su correo para recibir instrucciones</p>
	</div>

	
	<?php if($errors->any()): ?>
		
		<?php if (isset($component)) { $__componentOriginal5194778a3a7b899dcee5619d0610f5cf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.alert','data' => ['id' => 'forgot-password-alert','type' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('forgot-password-alert'),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('danger')]); ?>
			
			<?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
				<span><?php echo e(__($error)); ?></span>
			<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
		 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $attributes = $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $component = $__componentOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
	<?php elseif(session('status')): ?>
		
		<?php if (isset($component)) { $__componentOriginal5194778a3a7b899dcee5619d0610f5cf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.alert','data' => ['id' => 'status-alert','type' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('status-alert'),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('success')]); ?>
			<span><?php echo e(session('status')); ?></span>
		 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $attributes = $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $component = $__componentOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
	<?php else: ?>
		
		<?php if (isset($component)) { $__componentOriginal5194778a3a7b899dcee5619d0610f5cf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.alert','data' => ['id' => 'info-alert','type' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('info-alert'),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('info')]); ?>
			<span>Se enviará un enlace de recuperación a su correo electrónico registrado.</span>
		 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $attributes = $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $component = $__componentOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
	<?php endif; ?>

	
	<form id="forgot-password-form" action="<?php echo e(route('password.email')); ?>" method="POST"
		  class="auth-form d-flex flex-column align-items-center justify-content-center w-100">
		
		<?php echo csrf_field(); ?>

		
		<?php if (isset($component)) { $__componentOriginal2ce417f24874842de9a30bb834f01a5b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ce417f24874842de9a30bb834f01a5b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.auth.input','data' => ['id' => 'email','type' => 'email','class' => 'w-100 mt-3','placeholder' => 'Correo Electrónico','autocomplete' => 'email','value' => old('email'),'autofocus' => true,'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.auth.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('email'),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('email'),'class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('w-100 mt-3'),'placeholder' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Correo Electrónico'),'autocomplete' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('email'),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('email')),'autofocus' => true,'required' => true]); ?>
			<i class="bi bi-envelope me-2"></i>
			Correo Electrónico
		 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ce417f24874842de9a30bb834f01a5b)): ?>
<?php $attributes = $__attributesOriginal2ce417f24874842de9a30bb834f01a5b; ?>
<?php unset($__attributesOriginal2ce417f24874842de9a30bb834f01a5b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ce417f24874842de9a30bb834f01a5b)): ?>
<?php $component = $__componentOriginal2ce417f24874842de9a30bb834f01a5b; ?>
<?php unset($__componentOriginal2ce417f24874842de9a30bb834f01a5b); ?>
<?php endif; ?>

		
		<?php if (isset($component)) { $__componentOriginal7ea37f59cfe110c4fffd354bd8ce9de7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7ea37f59cfe110c4fffd354bd8ce9de7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.auth.buttons.submit','data' => ['id' => 'forgot-password-button','class' => 'btn-primary w-100 my-3','spinnerId' => 'forgot-password-spinner']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.auth.buttons.submit'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('forgot-password-button'),'class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('btn-primary w-100 my-3'),'spinnerId' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('forgot-password-spinner')]); ?>
			<div id="forgot-password-button-text" class="d-flex flex-row align-items-center justify-content-center">
				<i class="bi bi-send me-2"></i>
				Enviar Enlace de Recuperación
			</div>
		 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7ea37f59cfe110c4fffd354bd8ce9de7)): ?>
<?php $attributes = $__attributesOriginal7ea37f59cfe110c4fffd354bd8ce9de7; ?>
<?php unset($__attributesOriginal7ea37f59cfe110c4fffd354bd8ce9de7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7ea37f59cfe110c4fffd354bd8ce9de7)): ?>
<?php $component = $__componentOriginal7ea37f59cfe110c4fffd354bd8ce9de7; ?>
<?php unset($__componentOriginal7ea37f59cfe110c4fffd354bd8ce9de7); ?>
<?php endif; ?>

		
		<a
			href="<?php echo e(route('login')); ?>"
			class="back-link d-flex align-items-center align-self-start mt-3 text-decoration-none"
		>
			<i
				class="bi bi-arrow-left me-2"
				data-bs-toggle="tooltip"
				data-bs-title="Regrese al inicio de sesión"
			></i>
			Volver al inicio de sesión
		</a>
	</form>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
	<?php echo app('Illuminate\Foundation\Vite')(['resources/js/auth/passwords/forgot.js']); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/auth/passwords/forgot.blade.php ENDPATH**/ ?>