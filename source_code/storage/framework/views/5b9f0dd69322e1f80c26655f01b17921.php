<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['id' => '', 'spinnerId' => '', 'class' => '']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['id' => '', 'spinnerId' => '', 'class' => '']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<button id="<?php echo e($id); ?>" type="submit" class="btn <?php echo e($class); ?>">
	<div id="<?php echo e($spinnerId); ?>" class="d-none flex-row align-items-center justify-content-center me-2">
		<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
		<span class="visually-hidden" role="status">Cargando...</span>
	</div>
	<?php echo e($slot); ?>

</button>
<?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/components/form/auth/buttons/submit.blade.php ENDPATH**/ ?>