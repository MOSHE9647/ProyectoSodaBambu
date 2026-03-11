<button
	id="themeTogglerBtn"
	{{ $attributes->merge(['class' => 'btn']) }}
	data-bs-toggle="tooltip"
	data-bs-title="Cambiar tema"
>
	<x-icons.sun-icon id="sunIcon"/>
	<x-icons.moon-icon id="moonIcon"/>
</button>
