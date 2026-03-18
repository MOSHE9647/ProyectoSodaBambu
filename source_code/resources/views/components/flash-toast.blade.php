{{-- Flash Toast Notification --}}
@php
	$flashMessages = array_filter([
		'success' => session('success'),
		'error' => session('error'),
		'warning' => session('warning'),
		'info' => session('info'),
	], filled(...));

	$hasFlashMessages = !empty($flashMessages);
@endphp

@if ($hasFlashMessages)
	<script type="module">
		// Map flash message types to SweetAlert2 icons
		const flashMessages = @json($flashMessages);
		const notificationTypes = SwalNotificationTypes || {};

		// Determine the type of flash message to display (priority: success > error > warning > info)
		const selectedType = Object.keys(flashMessages).find((key) => {
			const hasMessage = Boolean(flashMessages[key]);
			const hasNotificationType = Boolean(notificationTypes[String(key).toUpperCase()]);
			return hasMessage && hasNotificationType;
		});

		if (SwalToast && selectedType) {
			SwalToast.fire({
				icon: notificationTypes[selectedType.toUpperCase()],
				title: flashMessages[selectedType],
			});
		} else {
			console.warn('No se pudo mostrar el toast flash: tipo no soportado o SwalToast no disponible.', flashMessages);
		}
	</script>
@endif
