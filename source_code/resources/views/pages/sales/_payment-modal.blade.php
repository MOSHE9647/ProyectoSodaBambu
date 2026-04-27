<style>
    .swal2-popup {
        width: 900px !important;
        max-width: 95vw !important;
    }

    .payment-modal-layout {
        position: relative;
        --payment-primary: #7ac143;
        --payment-primary-soft: #ecf7df;
        --payment-border: var(--bs-border-color);
        --payment-muted: var(--bs-secondary-color);
        --payment-orange: #ff9800;
        --payment-surface: var(--bs-body-bg);
        --payment-surface-alt: var(--bs-tertiary-bg);
        --payment-text: var(--bs-body-color);
        --payment-input-bg: var(--bs-body-bg);
        --payment-input-text: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .payment-modal-layout {
        --payment-primary: var(--sidebar-active-item-bg);
        --payment-primary-soft: color-mix(in srgb, var(--sidebar-active-item-bg) 28%, transparent);
        --payment-orange: #f0a429;
        --payment-surface: var(--bs-secondary-bg);
        --payment-surface-alt: var(--bs-tertiary-bg);
        --payment-border: rgba(255, 255, 255, 0.14);
        --payment-input-bg: var(--bs-tertiary-bg);
    }

    .payment-modal-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 10px;
    }

    .payment-panel {
        border: 1px solid var(--payment-border);
        border-radius: 10px;
        padding: 8px;
        background: var(--payment-surface);
        color: var(--payment-text);
    }

    .payment-total-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid var(--payment-border);
        border-radius: 8px;
        padding: 7px 10px;
        background: var(--payment-surface-alt);
        margin-bottom: 6px;
    }

    .payment-method-list {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 6px;
    }

    .payment-method-option {
        border: 1px solid var(--payment-border);
        border-radius: 10px;
        padding: 8px 10px;
        background: var(--payment-surface);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all .2s ease;
        color: var(--payment-text);
    }

    .payment-method-option:hover {
        border-color: var(--payment-primary);
        background: color-mix(in srgb, var(--payment-surface-alt) 70%, var(--payment-primary-soft));
    }

    .payment-method-option .method-icon {
        width: auto;
        height: auto;
        border-radius: 0;
        background: transparent;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }

    .payment-method-option.is-active {
        border-color: var(--payment-primary);
        background: var(--payment-primary-soft);
        box-shadow: 0 0 0 1px rgba(122, 193, 67, .25);
    }

    .payment-method-option .method-check {
        color: var(--payment-primary);
        opacity: 0;
    }

    .payment-method-option.is-active .method-check {
        opacity: 1;
    }

    .payment-summary-list {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-height: 84px;
        max-height: 132px;
        overflow-y: auto;
        padding-right: 4px;
        margin-bottom: 6px;
    }

    .payment-summary-item {
        border: 1px solid var(--payment-border);
        border-radius: 8px;
        padding: 8px 10px;
        background: var(--payment-surface-alt);
    }

    .payment-remove-btn {
        width: 24px;
        height: 24px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }

    .payment-totals {
        border-top: 1px solid var(--payment-border);
        padding-top: 6px;
        margin-top: 6px;
    }

    .payment-label {
        margin-bottom: 4px;
        font-size: .82rem;
        color: var(--payment-muted);
        font-weight: 600;
    }

    .payment-amount-header {
        display: flex;
        align-items: flex-end;
        justify-content: flex-start;
        margin-bottom: 4px;
    }

    .payment-amount-header .payment-label {
        margin-bottom: 0;
    }

    .payment-amount-row {
        display: flex;
        align-items: stretch;
        gap: 6px;
        margin-bottom: 8px;
    }

    .payment-amount-row #payment-amount-input {
        flex: 1;
        margin-bottom: 0 !important;
    }

    .btn-clear-amount {
        padding: 2px 10px;
        font-size: .75rem;
        line-height: 1.2;
        white-space: nowrap;
    }

    .payment-inline-alert-backdrop {
        position: absolute;
        inset: 0;
        z-index: 30;
        background: rgba(0, 0, 0, 0.32);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px;
        border-radius: 10px;
    }

    .payment-inline-alert-card {
        width: min(360px, 100%);
        background: #fff;
        color: #1f1f1f;
        border-radius: 12px;
        border: 1px solid #e6e6e6;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
        padding: 14px;
    }

    .payment-inline-alert-title {
        font-weight: 700;
        margin-bottom: 6px;
    }

    .payment-inline-alert-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 12px;
    }

    .payment-modal-layout .form-control {
        background-color: var(--payment-input-bg);
        color: var(--payment-input-text);
        border-color: var(--payment-border);
    }

    .payment-modal-layout .form-control:focus {
        border-color: var(--payment-primary);
        box-shadow: 0 0 0 .2rem rgba(122, 193, 67, .25);
    }

    .payment-modal-layout .text-success {
        color: var(--payment-primary) !important;
    }

    .btn-payment-add {
        background: var(--payment-orange);
        border-color: var(--payment-orange);
        color: #1f1f1f;
        font-weight: 700;
    }

    .btn-payment-add:hover,
    .btn-payment-add:focus {
        background: #ef9000;
        border-color: #ef9000;
        color: #1f1f1f;
    }

    .payment-keyboard {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 3px;
        margin-top: 3px;
    }

    .keyboard-btn {
        padding: 0;
        min-height: 44px;
        font-size: .98rem;
        font-weight: 700;
        border-radius: 8px;
        border: 1px solid var(--payment-border);
        background: var(--payment-surface-alt);
        color: var(--payment-text);
        transition: all .15s ease;
        cursor: pointer;
    }

    .keyboard-btn:hover {
        border-color: var(--payment-primary);
        background: color-mix(in srgb, var(--payment-surface-alt) 60%, var(--payment-primary));
        transform: translateY(-1px);
    }

    .keyboard-btn:active {
        transform: translateY(0);
    }

    .keyboard-btn.sign-change {
        background: #f7dc7a;
        border-color: #f7dc7a;
        color: #1f1f1f;
    }

    .keyboard-btn.sign-change:hover {
        background: #f2d35f;
        border-color: #f2d35f;
    }

    .keyboard-btn.delete {
        background: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    .keyboard-btn.delete:hover {
        background: #c82333;
        border-color: #c82333;
    }

    .keyboard-btn.quick-add {
        background: #c7efbd;
        border-color: #b8e6ad;
    }

    [data-bs-theme="dark"] .keyboard-btn.quick-add {
        background: color-mix(in srgb, var(--sidebar-active-item-bg) 25%, transparent);
        border-color: color-mix(in srgb, var(--sidebar-active-item-bg) 50%, transparent);
    }

    .keyboard-btn.decimal {
        background: #f9d7c9;
        border-color: #f2c4b0;
    }

    .keyboard-btn.double-zero,
    .keyboard-btn.triple-zero {
        background: var(--payment-surface-alt);
    }

    [data-bs-theme="dark"] .keyboard-btn.decimal {
        background: rgba(255, 152, 0, 0.18);
        border-color: rgba(255, 152, 0, 0.3);
    }

    @media (max-width: 768px) {
        .swal2-popup {
            width: 96vw !important;
        }

        .payment-modal-grid {
            grid-template-columns: 1fr;
        }
        .keyboard-btn {
            min-height: 42px;
        }
    }
</style>

<form id="payment-form" class="payment-modal-layout text-start">
    <div class="payment-modal-grid">
        <section class="payment-panel">
            <div class="payment-method-list">
                <button type="button" class="payment-method-option is-active" data-payment-method="cash">
                    <span class="d-flex align-items-center fw-semibold">
                        <span class="method-icon"><i class="bi bi-cash-coin"></i></span>
                        Efectivo
                    </span>
                    <i class="bi bi-check-circle-fill method-check"></i>
                </button>

                <button type="button" class="payment-method-option" data-payment-method="card">
                    <span class="d-flex align-items-center fw-semibold">
                        <span class="method-icon"><i class="bi bi-credit-card"></i></span>
                        Tarjeta
                    </span>
                    <i class="bi bi-check-circle-fill method-check"></i>
                </button>

                <button type="button" class="payment-method-option" data-payment-method="sinpe">
                    <span class="d-flex align-items-center fw-semibold">
                        <span class="method-icon"><x-icons.sinpe-movil width="22" height="22" /></span>
                        SINPE
                    </span>
                    <i class="bi bi-check-circle-fill method-check"></i>
                </button>
            </div>

            <div class="payment-amount-header">
                <label for="payment-amount-input" class="payment-label">Monto a pagar:</label>
            </div>
            <div class="payment-amount-row">
                <input
                    id="payment-amount-input"
                    class="form-control"
                    type="text"
                    inputmode="decimal"
                    value="{{ number_format($paymentTotal, 0, '.', '') }}"
                >
                <button id="clear-payment-amount-button" type="button" class="btn btn-outline-secondary btn-clear-amount">Limpiar</button>
            </div>
            <div id="payment-change-preview" class="small text-success mb-2 d-none"></div>

            <div id="payment-reference-group" class="d-none">
                <label for="payment-reference-input" class="payment-label">Referencia:</label>
                <input
                    id="payment-reference-input"
                    class="form-control mb-2"
                    type="text"
                    placeholder="Comprobante o referencia"
                >
            </div>

            <div class="payment-keyboard">
                <button type="button" class="keyboard-btn" data-keyboard-key="1">1</button>
                <button type="button" class="keyboard-btn" data-keyboard-key="2">2</button>
                <button type="button" class="keyboard-btn" data-keyboard-key="3">3</button>
                <button type="button" class="keyboard-btn quick-add" data-keyboard-key="100">+100</button>

                <button type="button" class="keyboard-btn" data-keyboard-key="4">4</button>
                <button type="button" class="keyboard-btn" data-keyboard-key="5">5</button>
                <button type="button" class="keyboard-btn" data-keyboard-key="6">6</button>
                <button type="button" class="keyboard-btn quick-add" data-keyboard-key="500">+500</button>

                <button type="button" class="keyboard-btn" data-keyboard-key="7">7</button>
                <button type="button" class="keyboard-btn" data-keyboard-key="8">8</button>
                <button type="button" class="keyboard-btn" data-keyboard-key="9">9</button>
                <button type="button" class="keyboard-btn quick-add" data-keyboard-key="1000">+1000</button>

                <button type="button" class="keyboard-btn double-zero" data-keyboard-key="00">00</button>
                <button type="button" class="keyboard-btn" data-keyboard-key="0">0</button>
                <button type="button" class="keyboard-btn triple-zero" data-keyboard-key="000">000</button>
                <button type="button" class="keyboard-btn delete" data-keyboard-key="delete"><i class="bi bi-backspace"></i></button>
            </div>

            <button id="add-payment-button" type="button" class="btn btn-payment-add w-100 mt-2">AGREGAR PAGO</button>
        </section>

        <section class="payment-panel d-flex flex-column">
            <h6 class="mb-1 fw-bold">Resumen de Pagos</h6>

            <div id="payment-summary-list" class="payment-summary-list">
                <div class="small text-muted">Aun no agregaste pagos.</div>
            </div>

            <div class="payment-totals small">
                <div class="d-flex justify-content-between mb-1">
                    <span>Total Factura:</span>
                    <span id="payment-total" class="fw-semibold">₡ {{ number_format($paymentTotal, 0, ',', ' ') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Total Pagado:</span>
                    <span id="payment-paid" class="fw-semibold">₡ 0</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Restante:</span>
                    <span id="payment-remaining" class="fw-bold">₡ {{ number_format($paymentTotal, 0, ',', ' ') }}</span>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span>Vuelto:</span>
                    <span id="payment-change" class="fw-semibold text-success">₡ 0</span>
                </div>
            </div>

            <button id="complete-sale-button" type="submit" class="btn btn-success w-100 mt-auto" disabled>
                COMPLETAR VENTA
            </button>
        </section>

    <div id="payment-rows-container" class="d-none" aria-hidden="true"></div>
</form>