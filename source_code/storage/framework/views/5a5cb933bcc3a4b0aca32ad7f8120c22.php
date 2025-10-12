<?php

	use App\Enums\UserRole;

?>

<div id="sidebar" class="d-flex flex-column flex-shrink-0 p-3 bg-body-tertiary">
	
	<?php if (isset($component)) { $__componentOriginal4cc49915c0f77910c7d052830e9d1044 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4cc49915c0f77910c7d052830e9d1044 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar.logo','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar.logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
		<div class="d-flex flex-column">
			<span class="fs-4" style="font-weight: bold; font-size: 2rem">
				<?php echo e(config("app.name", "Soda El Bambú")); ?>

			</span>
			<small class="text-body-secondary">Sistema de Gestión Interna</small>
		</div>
	 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4cc49915c0f77910c7d052830e9d1044)): ?>
<?php $attributes = $__attributesOriginal4cc49915c0f77910c7d052830e9d1044; ?>
<?php unset($__attributesOriginal4cc49915c0f77910c7d052830e9d1044); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4cc49915c0f77910c7d052830e9d1044)): ?>
<?php $component = $__componentOriginal4cc49915c0f77910c7d052830e9d1044; ?>
<?php unset($__componentOriginal4cc49915c0f77910c7d052830e9d1044); ?>
<?php endif; ?>
	<hr>

	
	<?php if (isset($component)) { $__componentOriginal01bf3b01a557c75eb9cd135a2177f1b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal01bf3b01a557c75eb9cd135a2177f1b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar.menu.menu','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar.menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal01bf3b01a557c75eb9cd135a2177f1b0)): ?>
<?php $attributes = $__attributesOriginal01bf3b01a557c75eb9cd135a2177f1b0; ?>
<?php unset($__attributesOriginal01bf3b01a557c75eb9cd135a2177f1b0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal01bf3b01a557c75eb9cd135a2177f1b0)): ?>
<?php $component = $__componentOriginal01bf3b01a557c75eb9cd135a2177f1b0; ?>
<?php unset($__componentOriginal01bf3b01a557c75eb9cd135a2177f1b0); ?>
<?php endif; ?>
	<hr>

	
	<div class="d-flex flex-row align-items-center justify-content-between">
		
		<div class="dropdown">
			<a
				href="#"
				class="d-flex align-items-center link-body-emphasis text-decoration-none dropdown-toggle"
				data-bs-toggle="dropdown" aria-expanded="false"
			>
				
				<?php if (isset($component)) { $__componentOriginal575177c3fef620b01a5fa1598c57ec35 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal575177c3fef620b01a5fa1598c57ec35 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.user-account-icon','data' => ['class' => 'rounded-circle me-3','width' => '32','height' => '32']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icons.user-account-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'rounded-circle me-3','width' => '32','height' => '32']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal575177c3fef620b01a5fa1598c57ec35)): ?>
<?php $attributes = $__attributesOriginal575177c3fef620b01a5fa1598c57ec35; ?>
<?php unset($__attributesOriginal575177c3fef620b01a5fa1598c57ec35); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal575177c3fef620b01a5fa1598c57ec35)): ?>
<?php $component = $__componentOriginal575177c3fef620b01a5fa1598c57ec35; ?>
<?php unset($__componentOriginal575177c3fef620b01a5fa1598c57ec35); ?>
<?php endif; ?>

				
				<div class="d-flex flex-column me-2">
					<strong><?php echo e(Auth::user()->name); ?></strong>
					<?php
						$userHasRole = Auth::user()->getRoleNames()->isNotEmpty();
						if ($userHasRole) {
							$userRoleName = Auth::user()->getRoleNames()->first();
							$userRole = UserRole::tryFrom($userRoleName)?->label() ?? UserRole::GUEST->label();
						} else {
							$userRole = UserRole::GUEST->label();
						}
					?>
					<small class="text-body-secondary"><?php echo e($userRole); ?></small>
				</div>
			</a>
			<ul class="dropdown-menu text-small shadow">
				<li class="list-item">
					<a class="dropdown-item" href="#">
						<?php if (isset($component)) { $__componentOriginaled8fadf8011064ff183f62cbabf0b1c8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaled8fadf8011064ff183f62cbabf0b1c8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.config-icon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icons.config-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaled8fadf8011064ff183f62cbabf0b1c8)): ?>
<?php $attributes = $__attributesOriginaled8fadf8011064ff183f62cbabf0b1c8; ?>
<?php unset($__attributesOriginaled8fadf8011064ff183f62cbabf0b1c8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaled8fadf8011064ff183f62cbabf0b1c8)): ?>
<?php $component = $__componentOriginaled8fadf8011064ff183f62cbabf0b1c8; ?>
<?php unset($__componentOriginaled8fadf8011064ff183f62cbabf0b1c8); ?>
<?php endif; ?>
						Configuración
					</a>
				</li>
				<li>
					<hr class="dropdown-divider">
				</li>
				<li class="list-item">
					<a
						id="logoutBtn"
						class="dropdown-item"
						href="<?php echo e(route('logout')); ?>"
						onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
					>
						<?php if (isset($component)) { $__componentOriginal5893a78d3e17c30ace166b43253baf4e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5893a78d3e17c30ace166b43253baf4e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.logout-icon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icons.logout-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5893a78d3e17c30ace166b43253baf4e)): ?>
<?php $attributes = $__attributesOriginal5893a78d3e17c30ace166b43253baf4e; ?>
<?php unset($__attributesOriginal5893a78d3e17c30ace166b43253baf4e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5893a78d3e17c30ace166b43253baf4e)): ?>
<?php $component = $__componentOriginal5893a78d3e17c30ace166b43253baf4e; ?>
<?php unset($__componentOriginal5893a78d3e17c30ace166b43253baf4e); ?>
<?php endif; ?>
						Cerrar sesión
					</a>
					<form
						id="logout-form"
						action="<?php echo e(route('logout')); ?>"
						method="POST" class="d-none">
						<?php echo csrf_field(); ?>
					</form>
				</li>
			</ul>
		</div>
		
		<?php if (isset($component)) { $__componentOriginal741885c129e61adfe6cf71bd08c056a3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal741885c129e61adfe6cf71bd08c056a3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.buttons.theme-toggle','data' => ['class' => 'border rounded-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('buttons.theme-toggle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'border rounded-circle']); ?>
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
	</div>
</div>
<?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/components/sidebar/sidebar.blade.php ENDPATH**/ ?>