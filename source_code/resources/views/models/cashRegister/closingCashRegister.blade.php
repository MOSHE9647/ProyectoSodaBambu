{{-- ============================================================
     Modal: Cierre de Caja Registradora
     Modelos: CashRegister, CashRegisterReport, CashRegisterDetail
     Stack: Laravel Blade + Bootstrap 5 + jQuery (Vanilla JS)
     ============================================================ --}}

{{-- ── MODAL ────────────────────────────────────────────────── --}}
<div class="modal fade" id="modalCierreCaja" tabindex="-1"
     aria-labelledby="modalCierreCajaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">

            {{-- ── HEADER ──────────────────────────────────────────────── --}}
            <div class="modal-header caja-header px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-cash-register fs-5 text-white opacity-75"></i>
                    <h5 class="modal-title fw-semibold text-white mb-0" id="modalCierreCajaLabel">
                        Cerrando la caja registradora
                    </h5>
                </div>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="badge caja-badge-orders px-3 py-2 rounded-pill">
                        <i class="bi bi-receipt me-1"></i>
                        <span id="caja-total-ordenes">—</span>
                    </span>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
            </div>

            {{-- ── BODY ─────────────────────────────────────────────────── --}}
            <div class="modal-body px-4 py-3 bg-white">

                {{-- LOADING STATE --}}
                <div id="caja-loading" class="text-center py-5">
                    <div class="spinner-border text-success" role="status" style="width:2.5rem;height:2.5rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-muted mt-3 small">Calculando movimientos…</p>
                </div>

                {{-- CONTENT (hidden until loaded) --}}
                <div id="caja-content" style="display:none;">

                    {{-- ── SECCIÓN: MÉTODOS DE PAGO ──────────────────────── --}}
                    <div class="mb-3">
                        <p class="text-muted small text-uppercase fw-semibold ls-wide mb-2">
                            <i class="bi bi-bar-chart-steps me-1"></i> Desglose por método de pago
                        </p>

                        {{-- EFECTIVO --}}
                        <div class="caja-method-card mb-2" id="card-efectivo">
                            <div class="caja-method-header d-flex justify-content-between align-items-center"
                                 data-bs-toggle="collapse" data-bs-target="#collapse-efectivo"
                                 role="button" aria-expanded="true">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="caja-method-icon bg-success-subtle text-success rounded-3 p-2">
                                        <i class="bi bi-cash fs-6"></i>
                                    </span>
                                    <span class="fw-semibold">Efectivo</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-bold" id="efectivo-sistema">₡ 0,00</span>
                                    <i class="bi bi-chevron-down caja-chevron small text-muted"></i>
                                </div>
                            </div>
                            <div class="collapse show" id="collapse-efectivo">
                                <div class="caja-method-body">
                                    <div class="row g-2 small text-muted mb-2">
                                        <div class="col-6">Saldo de Apertura</div>
                                        <div class="col-6 text-end" id="efectivo-apertura">₡ 0,00</div>
                                        <div class="col-6">Pagos en Efectivo</div>
                                        <div class="col-6 text-end" id="efectivo-pagos">₡ 0,00</div>
                                    </div>
                                    <hr class="my-2 opacity-25">
                                    <div class="row g-2 small mb-3">
                                        <div class="col-6 text-muted">Contado (Sistema)</div>
                                        <div class="col-6 text-end fw-semibold" id="efectivo-sistema-detail">₡ 0,00</div>
                                        <div class="col-6 text-muted">Diferencia</div>
                                        <div class="col-6 text-end fw-bold" id="efectivo-diferencia">₡ 0,00</div>
                                    </div>
                                    {{-- Input físico efectivo --}}
                                    <div class="mb-0">
                                        <label class="form-label small text-muted mb-1">
                                            <i class="bi bi-calculator me-1"></i>Conteo de efectivo físico
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0 text-muted">₡</span>
                                            <input type="number" step="0.01" min="0"
                                                   class="form-control caja-physical-input border-start-0 ps-0"
                                                   id="input-efectivo"
                                                   data-method="cash"
                                                   placeholder="0,00"
                                                   inputmode="decimal">
                                            <button class="btn btn-outline-secondary btn-clear-input" type="button"
                                                    data-target="input-efectivo" title="Limpiar">
                                                <i class="bi bi-x-lg small"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- TARJETA --}}
                        <div class="caja-method-card mb-2" id="card-tarjeta">
                            <div class="caja-method-header d-flex justify-content-between align-items-center"
                                 data-bs-toggle="collapse" data-bs-target="#collapse-tarjeta"
                                 role="button" aria-expanded="false">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="caja-method-icon bg-primary-subtle text-primary rounded-3 p-2">
                                        <i class="bi bi-credit-card fs-6"></i>
                                    </span>
                                    <span class="fw-semibold">Tarjeta</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-bold" id="tarjeta-sistema">₡ 0,00</span>
                                    <i class="bi bi-chevron-down caja-chevron small text-muted"></i>
                                </div>
                            </div>
                            <div class="collapse" id="collapse-tarjeta">
                                <div class="caja-method-body">
                                    <div class="row g-2 small mb-3">
                                        <div class="col-6 text-muted">Contado (Sistema)</div>
                                        <div class="col-6 text-end fw-semibold" id="tarjeta-sistema-detail">₡ 0,00</div>
                                        <div class="col-6 text-muted">Diferencia</div>
                                        <div class="col-6 text-end fw-bold" id="tarjeta-diferencia">₡ 0,00</div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label small text-muted mb-1">
                                            <i class="bi bi-credit-card-2-front me-1"></i>Monto físico en tarjeta
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0 text-muted">₡</span>
                                            <input type="number" step="0.01" min="0"
                                                   class="form-control caja-physical-input border-start-0 ps-0"
                                                   id="input-tarjeta"
                                                   data-method="card"
                                                   placeholder="0,00"
                                                   inputmode="decimal">
                                            <button class="btn btn-outline-secondary btn-clear-input" type="button"
                                                    data-target="input-tarjeta" title="Limpiar">
                                                <i class="bi bi-x-lg small"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- SINPE --}}
                        <div class="caja-method-card mb-2" id="card-sinpe">
                            <div class="caja-method-header d-flex justify-content-between align-items-center"
                                 data-bs-toggle="collapse" data-bs-target="#collapse-sinpe"
                                 role="button" aria-expanded="false">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="caja-method-icon bg-warning-subtle text-warning rounded-3 p-2">
                                        <i class="bi bi-phone fs-6"></i>
                                    </span>
                                    <span class="fw-semibold">SINPE Móvil</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-bold" id="sinpe-sistema">₡ 0,00</span>
                                    <i class="bi bi-chevron-down caja-chevron small text-muted"></i>
                                </div>
                            </div>
                            <div class="collapse" id="collapse-sinpe">
                                <div class="caja-method-body">
                                    <div class="row g-2 small mb-3">
                                        <div class="col-6 text-muted">Contado (Sistema)</div>
                                        <div class="col-6 text-end fw-semibold" id="sinpe-sistema-detail">₡ 0,00</div>
                                        <div class="col-6 text-muted">Diferencia</div>
                                        <div class="col-6 text-end fw-bold" id="sinpe-diferencia">₡ 0,00</div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label small text-muted mb-1">
                                            <i class="bi bi-phone-vibrate me-1"></i>Monto físico SINPE
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0 text-muted">₡</span>
                                            <input type="number" step="0.01" min="0"
                                                   class="form-control caja-physical-input border-start-0 ps-0"
                                                   id="input-sinpe"
                                                   data-method="sinpe"
                                                   placeholder="0,00"
                                                   inputmode="decimal">
                                            <button class="btn btn-outline-secondary btn-clear-input" type="button"
                                                    data-target="input-sinpe" title="Limpiar">
                                                <i class="bi bi-x-lg small"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3 opacity-25">

                    {{-- ── RESUMEN TOTAL ──────────────────────────────────── --}}
                    <div class="caja-summary-card mb-3">
                        <div class="row g-2 small">
                            <div class="col-6 text-muted">Total Sistema</div>
                            <div class="col-6 text-end fw-semibold" id="total-sistema">₡ 0,00</div>
                            <div class="col-6 text-muted">Total Físico Contado</div>
                            <div class="col-6 text-end fw-semibold" id="total-fisico">₡ 0,00</div>
                            <div class="col-12"><hr class="my-1 opacity-25"></div>
                            <div class="col-6 fw-semibold">Diferencia Total</div>
                            <div class="col-6 text-end fw-bold fs-6" id="total-diferencia">₡ 0,00</div>
                        </div>
                    </div>

                    {{-- ── NOTAS ──────────────────────────────────────────── --}}
                    <div>
                        <label for="caja-notas" class="form-label small text-muted mb-1">
                            <i class="bi bi-chat-left-text me-1"></i>Notas del cierre
                            <span class="text-muted">(opcional)</span>
                        </label>
                        <textarea id="caja-notas" rows="2"
                                  class="form-control form-control-sm"
                                  placeholder="Observaciones sobre el cierre…"
                                  maxlength="500"></textarea>
                    </div>

                </div>{{-- /caja-content --}}
            </div>

            {{-- ── FOOTER ───────────────────────────────────────────────── --}}
            <div class="modal-footer px-4 py-3 bg-light border-top-0 d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-success btn-sm px-4 fw-semibold" id="btn-cerrar-caja">
                    <i class="bi bi-lock-fill me-1"></i> Cerrar Caja
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm px-3"
                        data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Descartar
                </button>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3" id="btn-venta-diaria">
                        <i class="bi bi-bar-chart-line me-1"></i> Venta diaria
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ============================================================
     ESTILOS
     ============================================================ --}}
<style>
    /* ── Variables (alineadas al verde del sistema) ─────────── */
    :root {
        --caja-green:      #28a745;
        --caja-green-dark: #1e7e34;
        --caja-border:     #e9ecef;
        --caja-hover:      #f8f9fa;
        --caja-radius:     0.6rem;
    }

    /* ── Header ─────────────────────────────────────────────── */
    .caja-header {
        background: linear-gradient(135deg, var(--caja-green) 0%, var(--caja-green-dark) 100%);
    }
    .caja-badge-orders {
        background: rgba(255,255,255,0.20);
        color: #fff;
        font-size: .75rem;
        font-weight: 500;
        border: 1px solid rgba(255,255,255,0.30);
    }

    /* ── Method cards ────────────────────────────────────────── */
    .caja-method-card {
        border: 1px solid var(--caja-border);
        border-radius: var(--caja-radius);
        overflow: hidden;
        transition: box-shadow .15s ease;
    }
    .caja-method-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.07); }

    .caja-method-header {
        padding: .65rem 1rem;
        background: #fff;
        cursor: pointer;
        user-select: none;
        transition: background .12s;
    }
    .caja-method-header:hover { background: var(--caja-hover); }

    .caja-method-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px; height: 32px;
    }

    .caja-method-body {
        padding: .75rem 1rem;
        background: #fafafa;
        border-top: 1px solid var(--caja-border);
    }

    /* Chevron animation */
    .caja-chevron { transition: transform .2s ease; }
    [aria-expanded="true"] .caja-chevron { transform: rotate(180deg); }

    /* ── Physical inputs ─────────────────────────────────────── */
    .caja-physical-input:focus {
        border-color: var(--caja-green);
        box-shadow: 0 0 0 .2rem rgba(40,167,69,.15);
    }

    /* ── Summary card ────────────────────────────────────────── */
    .caja-summary-card {
        background: #f8f9fa;
        border: 1px solid var(--caja-border);
        border-radius: var(--caja-radius);
        padding: .85rem 1rem;
    }

    /* ── Diferencia positiva/negativa ────────────────────────── */
    .diff-positive { color: #198754; }
    .diff-negative { color: #dc3545; }
    .diff-zero     { color: #6c757d; }

    /* ── Letter-spacing helper ───────────────────────────────── */
    .ls-wide { letter-spacing: .06em; }

    /* ── Clear button ────────────────────────────────────────── */
    .btn-clear-input {
        border-color: #ced4da;
        color: #6c757d;
        padding: .25rem .5rem;
    }
    .btn-clear-input:hover { color: #dc3545; background: #fff0f0; border-color: #dc3545; }
</style>

{{-- ============================================================
     JAVASCRIPT (jQuery + Bootstrap 5 ya cargados en el layout)
     ============================================================ --}}
<script>
$(function () {

    // ── Helpers ──────────────────────────────────────────────
    const fmt = (n) => '₡ ' + parseFloat(n || 0).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function applyDiffClass(el, val) {
        $(el).removeClass('diff-positive diff-negative diff-zero');
        if (val > 0)      $(el).addClass('diff-positive');
        else if (val < 0) $(el).addClass('diff-negative');
        else              $(el).addClass('diff-zero');
        $(el).text(fmt(val));
    }

    // ── Estado local ─────────────────────────────────────────
    let cajaData = {
        cash_register_id: null,
        opening_balance: 0,
        system: { cash: 0, card: 0, sinpe: 0 },
        physical: { cash: '', card: '', sinpe: '' },
        total_orders: 0,
    };

    // ── Abrir modal: recibe el ID de la sesión activa ─────────
    //   Llamar desde fuera: $('#modalCierreCaja').data('id', cashRegisterId).modal('show')
    //   O disparar el evento: $(document).trigger('abrirCierreCaja', { id: cashRegisterId })

    $('#modalCierreCaja').on('show.bs.modal', function (e) {
        const id = $(this).data('id');
        if (!id) return;
        iniciarCierre(id);
    });

    // También escucha evento global
    $(document).on('abrirCierreCaja', function (e, params) {
        if (!params || !params.id) return;
        $('#modalCierreCaja').data('id', params.id);
        $('#modalCierreCaja').modal('show');
    });

    function iniciarCierre(cashRegisterId) {
        cajaData.cash_register_id = cashRegisterId;
        resetUI();

        // Solicita datos al servidor
        $.ajax({
            url: `/cash-registers/${cashRegisterId}/close-data`,   // ← Ajusta tu ruta
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                populateUI(res);
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la información de la caja.' });
                $('#modalCierreCaja').modal('hide');
            }
        });
    }

    function resetUI() {
        $('#caja-loading').show();
        $('#caja-content').hide();
        // Limpiar inputs
        $('.caja-physical-input').val('');
        // Reset diferencias
        ['efectivo', 'tarjeta', 'sinpe'].forEach(m => $(`#${m}-diferencia`).text('₡ 0,00').removeClass('diff-positive diff-negative diff-zero'));
        $('#total-diferencia').text('₡ 0,00').removeClass('diff-positive diff-negative diff-zero');
        $('#caja-notas').val('');
    }

    /**
     * res = {
     *   total_orders: 2,
     *   opening_balance: 30000.00,
     *   system_cash: 26.39,       // solo transacciones, sin apertura
     *   system_card: 7.52,
     *   system_sinpe: 0,
     * }
     */
    function populateUI(res) {
        cajaData.opening_balance = parseFloat(res.opening_balance || 0);
        cajaData.system.cash     = parseFloat(res.system_cash    || 0);
        cajaData.system.card     = parseFloat(res.system_card    || 0);
        cajaData.system.sinpe    = parseFloat(res.system_sinpe   || 0);
        cajaData.total_orders    = res.total_orders || 0;

        const totalCashSystem = cajaData.opening_balance + cajaData.system.cash;

        // Header
        $('#caja-total-ordenes').text(`${cajaData.total_orders} ${cajaData.total_orders === 1 ? 'orden' : 'órdenes'}: ${fmt(totalCashSystem + cajaData.system.card + cajaData.system.sinpe)}`);

        // Efectivo
        $('#efectivo-sistema').text(fmt(totalCashSystem));
        $('#efectivo-apertura').text(fmt(cajaData.opening_balance));
        $('#efectivo-pagos').text(fmt(cajaData.system.cash));
        $('#efectivo-sistema-detail').text(fmt(totalCashSystem));
        applyDiffClass('#efectivo-diferencia', 0); // se recalcula al ingresar físico

        // Tarjeta
        $('#tarjeta-sistema').text(fmt(cajaData.system.card));
        $('#tarjeta-sistema-detail').text(fmt(cajaData.system.card));
        applyDiffClass('#tarjeta-diferencia', 0);

        // SINPE
        $('#sinpe-sistema').text(fmt(cajaData.system.sinpe));
        $('#sinpe-sistema-detail').text(fmt(cajaData.system.sinpe));
        applyDiffClass('#sinpe-diferencia', 0);

        // Totales
        const totalSist = totalCashSystem + cajaData.system.card + cajaData.system.sinpe;
        $('#total-sistema').text(fmt(totalSist));
        $('#total-fisico').text(fmt(0));
        applyDiffClass('#total-diferencia', 0);

        $('#caja-loading').hide();
        $('#caja-content').fadeIn(200);
    }

    // ── Recalcular en tiempo real al cambiar inputs ───────────
    $(document).on('input', '.caja-physical-input', function () {
        const method = $(this).data('method'); // cash | card | sinpe
        const physVal = parseFloat($(this).val()) || 0;
        cajaData.physical[method === 'cash' ? 'cash' : method === 'card' ? 'card' : 'sinpe'] = physVal;

        // Obtener sistema del método correcto
        let syst;
        if (method === 'cash') {
            syst = cajaData.opening_balance + cajaData.system.cash;
            const diff = physVal - syst;
            applyDiffClass('#efectivo-diferencia', diff);
        } else if (method === 'card') {
            syst = cajaData.system.card;
            const diff = physVal - syst;
            applyDiffClass('#tarjeta-diferencia', diff);
        } else {
            syst = cajaData.system.sinpe;
            const diff = physVal - syst;
            applyDiffClass('#sinpe-diferencia', diff);
        }

        // Totales
        const phCash  = parseFloat($('#input-efectivo').val()) || 0;
        const phCard  = parseFloat($('#input-tarjeta').val()) || 0;
        const phSinpe = parseFloat($('#input-sinpe').val())   || 0;
        const totalFisico = phCash + phCard + phSinpe;

        const totalSist = (cajaData.opening_balance + cajaData.system.cash) + cajaData.system.card + cajaData.system.sinpe;
        const totalDiff  = totalFisico - totalSist;

        $('#total-fisico').text(fmt(totalFisico));
        applyDiffClass('#total-diferencia', totalDiff);
    });

    // ── Limpiar inputs individuales ───────────────────────────
    $(document).on('click', '.btn-clear-input', function () {
        const target = $(this).data('target');
        $(`#${target}`).val('').trigger('input');
    });

    // ── Chevron collapse sync ─────────────────────────────────
    $('#modalCierreCaja .collapse').on('show.bs.collapse hide.bs.collapse', function (e) {
        const header = $(this).prev('.caja-method-header');
        if (e.type === 'show') header.attr('aria-expanded', 'true');
        else                   header.attr('aria-expanded', 'false');
    });

    // ── Venta Diaria ──────────────────────────────────────────
    $('#btn-venta-diaria').on('click', function () {
        const id = $('#modalCierreCaja').data('id');
        if (id) window.open(`/cash-registers/${id}/daily-report`, '_blank'); // Ajusta tu ruta
    });

    // ── Cerrar Caja (submit) ──────────────────────────────────
    $('#btn-cerrar-caja').on('click', function () {
        const phCash  = parseFloat($('#input-efectivo').val()) || 0;
        const phCard  = parseFloat($('#input-tarjeta').val()) || 0;
        const phSinpe = parseFloat($('#input-sinpe').val())   || 0;

        const payload = {
            cash_register_id: cajaData.cash_register_id,
            notes: $('#caja-notas').val().trim(),
            details: [
                {
                    payment_method: 'cash',
                    system_amount:  parseFloat((cajaData.opening_balance + cajaData.system.cash).toFixed(2)),
                    physical_amount: phCash,
                    difference:      parseFloat((phCash - (cajaData.opening_balance + cajaData.system.cash)).toFixed(2)),
                },
                {
                    payment_method: 'card',
                    system_amount:  parseFloat(cajaData.system.card.toFixed(2)),
                    physical_amount: phCard,
                    difference:      parseFloat((phCard - cajaData.system.card).toFixed(2)),
                },
                {
                    payment_method: 'sinpe',
                    system_amount:  parseFloat(cajaData.system.sinpe.toFixed(2)),
                    physical_amount: phSinpe,
                    difference:      parseFloat((phSinpe - cajaData.system.sinpe).toFixed(2)),
                },
            ],
        };

        Swal.fire({
            title: '¿Cerrar la caja?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor:  '#6c757d',
            confirmButtonText: 'Sí, cerrar',
            cancelButtonText:  'Cancelar',
        }).then(result => {
            if (!result.isConfirmed) return;

            $('#btn-cerrar-caja').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Cerrando…');

            $.ajax({
                url: '/cash-registers/close',       // ← Ajusta tu ruta
                method: 'POST',
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: JSON.stringify(payload),
                success: function (res) {
                    $('#modalCierreCaja').modal('hide');
                    Swal.fire({ icon: 'success', title: '¡Caja cerrada!', text: res.message || 'La caja fue cerrada correctamente.', timer: 2500, showConfirmButton: false });
                    // Puedes emitir un evento para refrescar la vista
                    $(document).trigger('cajaCerrada', res);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Error al cerrar la caja.';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                },
                complete: function () {
                    $('#btn-cerrar-caja').prop('disabled', false).html('<i class="bi bi-lock-fill me-1"></i> Cerrar Caja');
                }
            });
        });
    });

    // ── Limpiar estado al cerrar el modal ─────────────────────
    $('#modalCierreCaja').on('hidden.bs.modal', function () {
        cajaData = { cash_register_id: null, opening_balance: 0, system: { cash: 0, card: 0, sinpe: 0 }, physical: { cash: '', card: '', sinpe: '' }, total_orders: 0 };
    });

});
</script>
