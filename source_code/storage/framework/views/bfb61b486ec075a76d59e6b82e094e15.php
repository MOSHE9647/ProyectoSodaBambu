




<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
	'id' => '',
	'type' => 'info',
	'message' => 'This is an alert message.',
	'class' => ''
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
	'type' => 'info',
	'message' => 'This is an alert message.',
	'class' => ''
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
	$icon = match ($type) {
		'success' => 'bi-check-circle',
		'info' => 'bi-info-circle',
		'warning' => 'bi-exclamation-triangle',
		'danger' => 'bi-exclamation-circle'
	};
?>

<div id="<?php echo e($id); ?>" class="alert alert-<?php echo e($type); ?> <?php echo e($class); ?>" role="alert">
	<i class="<?php echo e($icon); ?> me-2"></i>
	<?php if(isset($slot)): ?>
		<?php echo e($slot); ?>

	<?php else: ?>
		<span><?php echo e($message); ?></span>
	<?php endif; ?>
</div>
<?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/components/alert.blade.php ENDPATH**/ ?>