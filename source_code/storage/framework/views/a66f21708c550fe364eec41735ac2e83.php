<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
	'id' => '',
	'name' => null,
	'placeholder' => '',
	'class' => '',
	'inputClass' => '',
	'type' => null,
	'value' => null,
	'required' => false,
	'readonly' => false,
	'disabled' => false,
	'autocomplete' => null,
	'autofocus' => false,
	'errorMessage' => null,
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
	'name' => null,
	'placeholder' => '',
	'class' => '',
	'inputClass' => '',
	'type' => null,
	'value' => null,
	'required' => false,
	'readonly' => false,
	'disabled' => false,
	'autocomplete' => null,
	'autofocus' => false,
	'errorMessage' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="input-group has-validation">
	<div class="form-floating <?php echo e($class); ?> mb-3">
		<input
			id="<?php echo e($id); ?>"
			name="<?php echo e($name ?? $id); ?>"
			type="<?php echo e($type ?? 'text'); ?>"
			class="form-control <?php echo e($inputClass); ?>"
			placeholder="<?php echo e($placeholder); ?>"
			aria-describedby="<?php echo e($name ?? $id); ?>-error"
			<?php if(isset($value)): ?> value="<?php echo e($value); ?>" <?php endif; ?>
			<?php echo e($required ? 'required' : ''); ?>

			<?php echo e($readonly ? 'readonly' : ''); ?>

			<?php echo e($disabled ? 'disabled' : ''); ?>

			<?php echo e($autocomplete ? "autocomplete=$autocomplete" : ''); ?>

			<?php echo e($autofocus ? 'autofocus' : ''); ?>

		>

		<label for="<?php echo e($id); ?>" class="form-label">
			<?php echo e($slot ?? ucwords(str_replace('-', ' ', $name ?? $id))); ?>

		</label>

		<div id="<?php echo e($name ?? $id); ?>-error" class="invalid-feedback ps-2" role="alert">
			<strong><?php echo e($errorMessage ?? 'Error no especificado'); ?></strong>
		</div>
	</div>
</div>
<?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/components/form/auth/input.blade.php ENDPATH**/ ?>