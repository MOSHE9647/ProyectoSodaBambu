<div class="d-flex flex-column text-start">
	{{-- User Basic Information --}}
	<div class="row g-3 mb-3">
		<div class="col-6">
			<x-form.input.floating-label
                :id="'name'"
                :type="'text'"
                :readonly="true"
                :value="$category->name"
                :placeholder="'Nombre'"
                :iconLeft="'bi bi-tag'"
                :readonly="true"
            >
                Nombre
            </x-form.input.floating-label>
		</div>
		<div class="col-6">
			<x-form.input.floating-label
                :id="'description'"
                :type="'text'"
                :readonly="true"
                :value="$category->description ?? 'N/A'"
                :iconLeft="'bi bi-card-text'"
                :placeholder="'Descripción'"
                :readonly="true"
            >
                Descripción
            </x-form.input.floating-label>
		</div>
	</div>
	<div class="row g-3 mb-0">
		<div class="col-12">
			<x-form.input.floating-label
                :id="'created_at'"
                :type="'datetime-local'"
                :readonly="true"
                :value="$category->created_at"
                :iconLeft="'bi bi-calendar-plus'"
                :placeholder="'Fecha de Creación'"
                :readonly="true"
            >
                Fecha de Creación
            </x-form.input.floating-label>
		</div>
	</div>
</div>