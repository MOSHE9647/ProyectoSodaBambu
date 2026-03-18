<div class="d-flex flex-column text-start">
    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label
                :id="'name'"
                :type="'text'"
                :readonly="true"
                :value="$supply->name"
                :placeholder="'Nombre'"
                :iconLeft="'bi bi-tag'"
            >
                Nombre
            </x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label
                :id="'measure_unit'"
                :type="'text'"
                :readonly="true"
                :value="$supply->measure_unit"
                :placeholder="'Unidad de Medida'"
                :iconLeft="'bi bi-rulers'"
            >
                Unidad de Medida
            </x-form.input.floating-label>
        </div>
    </div>

    <hr class="my-4"/>

    <div class="row g-3 mb-0">
        <div class="col-6">
            <x-form.input.floating-label
                :id="'created_at'"
                :type="'text'"
                :readonly="true"
                :value="\Carbon\Carbon::parse($supply->created_at)->locale('es')->translatedFormat('d \d\e F \d\e\l Y')"
                :iconLeft="'bi bi-calendar-plus'"
                :placeholder="'Fecha de Creación'"
            >
                Fecha de Creación
            </x-form.input.floating-label>
        </div>
    </div>
</div>