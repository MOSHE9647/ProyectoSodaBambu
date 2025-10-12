<button
	id="themeTogglerBtn"
	<?php echo e($attributes->merge(['class' => 'btn'])); ?>

	data-bs-toggle="tooltip"
	data-bs-title="Cambiar tema"
>
	<?php if (isset($component)) { $__componentOriginale4092c63f28fb795531615d64bf62197 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale4092c63f28fb795531615d64bf62197 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.sun-icon','data' => ['id' => 'sunIcon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icons.sun-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'sunIcon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale4092c63f28fb795531615d64bf62197)): ?>
<?php $attributes = $__attributesOriginale4092c63f28fb795531615d64bf62197; ?>
<?php unset($__attributesOriginale4092c63f28fb795531615d64bf62197); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale4092c63f28fb795531615d64bf62197)): ?>
<?php $component = $__componentOriginale4092c63f28fb795531615d64bf62197; ?>
<?php unset($__componentOriginale4092c63f28fb795531615d64bf62197); ?>
<?php endif; ?>
	<?php if (isset($component)) { $__componentOriginal9d09ed4d868d2054fe9696530bec11ab = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9d09ed4d868d2054fe9696530bec11ab = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.moon-icon','data' => ['id' => 'moonIcon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icons.moon-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'moonIcon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9d09ed4d868d2054fe9696530bec11ab)): ?>
<?php $attributes = $__attributesOriginal9d09ed4d868d2054fe9696530bec11ab; ?>
<?php unset($__attributesOriginal9d09ed4d868d2054fe9696530bec11ab); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9d09ed4d868d2054fe9696530bec11ab)): ?>
<?php $component = $__componentOriginal9d09ed4d868d2054fe9696530bec11ab; ?>
<?php unset($__componentOriginal9d09ed4d868d2054fe9696530bec11ab); ?>
<?php endif; ?>
</button>
<?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/components/buttons/theme-toggle.blade.php ENDPATH**/ ?>