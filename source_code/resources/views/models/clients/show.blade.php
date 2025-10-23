<div class="d-flex flex-column text-start">
	{{-- Client Basic Information --}}
	<div class="row g-3 mb-3">
		<div class="col-6">
			<x-form.input.floating-label
				:id="'first_name'"
				:type="'text'"
				:readonly="true"
				:value="$client->first_name"
				:placeholder="'Nombre'"
				:iconLeft="'bi bi-person'"
			>
				Nombre
			</x-form.input.floating-label>
		</div>
		<div class="col-6">
			<x-form.input.floating-label
				:id="'last_name'"
				:type="'text'"
				:readonly="true"
				:value="$client->last_name"
				:placeholder="'Apellidos'"
				:iconLeft="'bi bi-person-fill'"
			>
				Apellidos
			</x-form.input.floating-label>
		</div>
	</div>
	<div class="row g-3 mb-0">
		<div class="col-6">
			<x-form.input.floating-label
				:id="'email'"
				:type="'email'"
				:readonly="true"
				:value="$client->email"
				:iconLeft="'bi bi-at'"
				:placeholder="'Correo Electrónico'"
			>
				Correo Electrónico
			</x-form.input.floating-label>
		</div>
		<div class="col-6">
			<x-form.input.floating-label
				:id="'phone'"
				:type="'text'"
				:readonly="true"
				:value="$client->phone"
				:iconLeft="'bi bi-telephone'"
				:placeholder="'Teléfono'"
			>
				Teléfono
			</x-form.input.floating-label>
		</div>
	</div>

	<hr class="my-4"/>

	{{-- Creation Info (Optional Detail) --}}
	<div class="row g-3 mb-0">
		<div class="col-6">
			<x-form.input.floating-label
				:id="'created_at'"
				:type="'text'"
				:readonly="true"
				:value="$client->created_at->format('Y-m-d H:i:s')"
				:iconLeft="'bi bi-calendar-plus'"
				:placeholder="'Fecha de Creación'"
			>
				Fecha de Creación
			</x-form.input.floating-label>
		</div>
	</div>
</div>