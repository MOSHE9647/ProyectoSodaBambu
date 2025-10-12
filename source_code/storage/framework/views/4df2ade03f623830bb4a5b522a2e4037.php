<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
	'id' => '',
	'class' => '',
	'checkClass' => '',
	'checked' => null,
]));

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

foreach (array_filter(([
	'id' => '',
	'class' => '',
	'checkClass' => '',
	'checked' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="form-check <?php echo e($class); ?> mb-3">
	<input
		id="<?php echo e($id); ?>"
		name="<?php echo e($id); ?>"
		type="checkbox"
		class="form-check-input <?php echo e($checkClass); ?>"
		aria-describedby="<?php echo e($id); ?>-help"
		onclick="this.value=!!this.checked"
		<?php echo e($checked ? 'checked' : ''); ?>

	>

	<label for="<?php echo e($id); ?>" class="form-check-label">
		<?php echo e($slot ?? ucwords(str_replace('-', ' ', $id))); ?>

	</label>
</div>
<?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/components/form/auth/checkbox.blade.php ENDPATH**/ ?>