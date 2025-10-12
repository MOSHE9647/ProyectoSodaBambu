<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
	'href' => route('home'),
	'type' => 'sidebar',
	'imgClass' => null,
	'imgStyle' => 'width: 60px; height: 60px',
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
	'href' => route('home'),
	'type' => 'sidebar',
	'imgClass' => null,
	'imgStyle' => 'width: 60px; height: 60px',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
	$image = '<img';
	if ($imgClass) {
		$image .= ' class="' . $imgClass . '"';
	}
	if ($imgStyle) {
		$image .= ' style="' . $imgStyle . '"';
	}
	$image .= ' src="' . asset('storage/logo.webp') . '" alt="Soda El Bambú Logo" aria-hidden="true">';
?>

<?php if($type === 'sidebar'): ?>
	<a
		href="<?php echo e($href); ?>"
		class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none"
	>
		<div
			class="brand-logo bi pe-none me-2 d-flex flex-column align-items-center justify-content-center border rounded-circle">
			<?php echo $image; ?>

		</div>
		<?php echo e($slot); ?>

	</a>

<?php elseif($type === 'login'): ?>
	<div class="brand-logo d-flex flex-column align-items-center justify-content-center border rounded-circle mb-3">
		<?php echo $image; ?>

	</div>
	<?php echo e($slot); ?>

<?php endif; ?>
<?php /**PATH C:\Users\isaac\OneDrive - Universidad Nacional de Costa Rica\2025\II CICLO 2025\INGENIERIA EN SISTEMAS II\Proyecto\ProyectoSodaBambu\source_code\resources\views/components/sidebar/logo.blade.php ENDPATH**/ ?>