@php
	use App\Enums\UserRole;
@endphp

<div class="d-flex flex-column text-start">
	{{-- User Basic Information --}}
	<div class="row g-3 mb-3">
		<div class="col-6">
			<x-form.input
				:id="'name'"
				:type="'text'"
				:readonly="true"
				:value="$user->name"
				:placeholder="'Nombre'"
				:iconLeft="'bi bi-person'"
			>
				Nombre
			</x-form.input>
		</div>
		<div class="col-6">
			@php
				$userRole = UserRole::tryFrom($user->roles->first()->name);
			@endphp
			<x-form.input
				:id="'role'"
				:type="'text'"
				:readonly="true"
				:placeholder="'Rol de Usuario'"
				:iconLeft="'bi bi-shield-lock'"
				:value="optional($userRole)->label() ?? 'N/A'"
			>
				Rol de Usuario
			</x-form.input>
		</div>
	</div>
	<div class="row g-3 mb-0">
		<div class="col-12">
			<x-form.input
				:id="'email'"
				:type="'email'"
				:readonly="true"
				:value="$user->email"
				:iconLeft="'bi bi-at'"
				:placeholder="'Correo Electrónico'"
			>
				Correo Electrónico
			</x-form.input>
		</div>
	</div>

	{{-- Employee Info --}}
	@if($user->employee)
		<hr class="my-4"/>
		<div class="row g-3 mb-3">
			<div class="col-6">
				<x-form.input
					:id="'payment_frequency'"
					:type="'text'"
					:readonly="true"
					:value="$user->employee->payment_frequency->label()"
					:iconLeft="'bi bi-calendar-check'"
					:placeholder="'Modalidad de Pago'"
				>
					Modalidad de Pago
				</x-form.input>
			</div>
			<div class="col-6">
				<x-form.input
					:id="'hourly_wage'"
					:type="'text'"
					:iconLeft="'bi bi-cash-coin'"
					:textIconRight="true"
					:placeholder="'Salario Por Hora'"
					:value="$user->employee->hourly_wage"
					:readonly="true"
					:step="'0.01'"
					:min="'0'"
				>
					Salario Por Hora
					<x-slot:iconRight>
						<x-icons.colon-icon/>
					</x-slot:iconRight>
				</x-form.input>
			</div>
		</div>
		<div class="row g-3">
			<div class="col-12">
				<x-form.input
					:id="'phone'"
					:type="'text'"
					:readonly="true"
					:value="$user->employee->phone"
					:iconLeft="'bi bi-telephone'"
					:placeholder="'Teléfono'"
				>
					Teléfono
				</x-form.input>
			</div>
			<div class="col-12">
				<x-form.input
					:id="'status'"
					:type="'text'"
					:readonly="true"
					:value="$user->employee->status->label()"
					:iconLeft="'bi bi-clipboard-check'"
					:placeholder="'Estado del Colaborador'"
				>
					Estado del Colaborador
				</x-form.input>
			</div>
		</div>
	@endif
</div>
