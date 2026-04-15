@for($i = 0; $i < 6; $i++) 
<div class="card p-3 shadow-sm h-100 product-skeleton" aria-hidden="true">
    <div class="d-flex flex-column h-100 placeholder-glow">
        {{-- Header: Name & Price --}}
        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
            <span class="placeholder col-7 fw-bold mb-0"></span>
            <span class="placeholder col-3 fw-bold flex-shrink-0"></span>
        </div>

        {{-- Description / Relevant Info --}}
        <p class="mb-3">
            <span class="placeholder col-5"></span>
        </p>

        {{-- Footer: Category & Stock --}}
        <div class="mt-auto d-flex justify-content-between align-items-center">
            <span class="placeholder col-4 px-2 py-1 border rounded"></span>
            <span class="placeholder col-3 text-body-secondary"></span>
        </div>
    </div>
</div>
@endfor