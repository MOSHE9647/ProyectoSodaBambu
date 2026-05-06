import { SwalModal, SwalToast, SwalNotificationTypes } from "../../utils/sweetalert.js";

const getTheme = () => document.documentElement.getAttribute('data-bs-theme') || 'light';

const getThemeClasses = () => ({
    popup: 'cash-closure-swal swal-popup',
    input: getTheme() === 'dark'
        ? 'cash-closure-input cash-closure-input--dark'
        : 'cash-closure-input',
});

const ClosureModalOptions = {
    title: 'cash-closure-title',
    htmlContainer: 'cash-closure-html',
    actions: 'd-none',
    confirmButton: 'd-none',
    cancelButton: 'd-none',
};

const formatCR = (val) => Number(val || 0).toLocaleString('es-CR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const currency = (val) => `\u20A1 ${formatCR(val)}`;
const numericValue = (value) => Number.isFinite(Number(value)) ? Number(value) : 0;
const inputValue = (id) => parseFloat(document.getElementById(id)?.value);

const getClosureStyles = () => `
    .cash-closure-swal {
        width: min(1180px, calc(100vw - 2rem)) !important;
        padding: 0 !important;
        overflow: hidden;
        border: 1px solid var(--bambu-translucent-border);
        border-radius: 0.9rem;
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
        box-shadow: var(--table-container-shadow);
    }

    .cash-closure-title {
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        margin: 0 !important;
        padding: 1rem 1.35rem !important;
        border-bottom: 1px solid var(--bambu-translucent-border);
        color: var(--bambu-logo-bg);
        font-size: 1rem;
        font-weight: 800;
        text-align: left;
    }

    .cash-closure-title::after {
        content: "\\F116";
        font-family: "bootstrap-icons";
        color: var(--bs-secondary-color);
        font-size: 1.1rem;
        font-weight: 400;
    }

    .cash-closure-html {
        width: 100%;
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden;
    }

    .cash-closure-shell {
        display: grid;
        grid-template-columns: minmax(240px, 0.78fr) minmax(360px, 1.45fr) minmax(240px, 0.78fr);
        gap: 1.35rem;
        min-height: 575px;
        padding: 1.5rem 1.35rem;
        background: var(--bs-body-bg);
    }

    .cash-closure-panel {
        overflow: hidden;
        border: 1px solid var(--bambu-translucent-border);
        border-radius: 0.75rem;
        background: var(--table-container-bg);
        box-shadow: 0 0.35rem 1.2rem rgb(0 0 0 / 5%);
    }

    .cash-closure-panel__title {
        margin: 0;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid var(--bambu-translucent-border);
        color: var(--bs-secondary-color);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .cash-closure-summary {
        padding: 0.45rem 1.15rem 1.15rem;
    }

    .cash-closure-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid var(--bambu-translucent-border);
        color: var(--bs-secondary-color);
        font-weight: 700;
    }

    .cash-closure-summary-row strong {
        color: var(--bs-body-color);
        white-space: nowrap;
    }

    .cash-closure-total {
        margin-top: 1rem;
        padding: 1rem;
        border: 1px solid rgb(40 167 69 / 16%);
        border-radius: 0.35rem;
        background: var(--radio-button-bg);
        color: var(--bambu-logo-bg);
    }

    .cash-closure-total span {
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .cash-closure-total strong {
        display: block;
        font-size: 1.65rem;
        line-height: 1.1;
    }

    .cash-closure-main-title {
        margin: 0 0 1rem;
        color: var(--bs-body-color);
        font-size: 1.15rem;
        font-weight: 800;
        text-align: left;
    }

    .cash-closure-methods {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }

    .cash-closure-method {
        padding: 1rem;
        border: 1px solid var(--bambu-translucent-border);
        border-radius: 0.55rem;
        background: var(--table-container-bg);
        box-shadow: 0 0.25rem 0.75rem rgb(0 0 0 / 4%);
    }

    .cash-closure-method__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.7rem;
    }

    .cash-closure-method__name {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        color: var(--bs-body-color);
        font-weight: 800;
    }

    .cash-closure-method__name i {
        color: var(--bambu-logo-bg);
        font-size: 1.2rem;
    }

    .cash-closure-method__system {
        color: var(--bs-secondary-color);
        font-size: 0.86rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cash-closure-money-field {
        display: grid;
        grid-template-columns: auto 1fr;
        align-items: center;
        border: 1px solid var(--bambu-translucent-border);
        border-radius: 0.45rem;
        background: var(--bs-body-bg);
    }

    .cash-closure-money-field span {
        padding-left: 1rem;
        color: var(--bs-secondary-color);
        font-weight: 800;
    }

    .cash-closure-input {
        width: 100%;
        min-height: 3rem;
        border: 0;
        outline: none;
        background: transparent;
        color: var(--bs-body-color);
        font-size: 1rem;
        font-weight: 700;
    }

    .cash-closure-input::placeholder,
    .cash-closure-notes::placeholder {
        color: var(--bs-secondary-color);
    }

    .cash-closure-feedback {
        display: none;
        margin-top: 0.7rem;
        padding: 0.45rem 0.6rem;
        border-radius: 0.35rem;
        font-size: 0.8rem;
        font-weight: 800;
        text-align: left;
    }

    .cash-closure-feedback.is-visible {
        display: block;
    }

    .cash-closure-feedback.is-ok {
        border: 1px solid rgb(40 167 69 / 25%);
        background: rgb(40 167 69 / 10%);
        color: var(--bambu-logo-bg);
    }

    .cash-closure-feedback.is-danger {
        border: 1px solid rgb(220 53 69 / 25%);
        background: rgb(220 53 69 / 10%);
        color: var(--bs-danger);
    }

    .cash-closure-side {
        display: flex;
        flex-direction: column;
        gap: 1.35rem;
    }

    .cash-closure-notes-wrap {
        padding: 1rem;
    }

    .cash-closure-notes {
        width: 100%;
        min-height: 118px;
        resize: vertical;
        border: 1px solid var(--bambu-translucent-border);
        border-radius: 0.45rem;
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
        padding: 0.85rem;
        font-weight: 700;
        outline: none;
    }

    .cash-closure-difference {
        padding: 1.25rem 1.35rem;
    }

    .cash-closure-difference__label {
        color: var(--bs-secondary-color);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .cash-closure-difference__amount {
        display: block;
        margin-top: 0.45rem;
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.15;
        text-align: left;
    }

    .cash-closure-difference__amount small {
        font-size: 0.76rem;
        font-weight: 800;
    }

    .cash-closure-actions {
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
        margin-top: 1.2rem;
    }

    .cash-closure-actions .btn {
        min-height: 3rem;
        font-weight: 800;
    }

    @media (max-width: 991.98px) {
        .cash-closure-shell {
            grid-template-columns: 1fr;
            min-height: auto;
        }
    }
`;

const ensureClosureStyles = () => {
    if (document.getElementById('cash-closure-modal-styles')) return;

    const style = document.createElement('style');
    style.id = 'cash-closure-modal-styles';
    style.textContent = getClosureStyles();
    document.head.appendChild(style);
};

const removeClosureStyles = () => {
    document.getElementById('cash-closure-modal-styles')?.remove();
};

const setFeedback = (item, diff) => {
    if (item.input.value === '') {
        item.feedback.className = 'cash-closure-feedback';
        item.feedback.textContent = '';
        return;
    }

    if (Math.abs(diff) < 0.01) {
        item.feedback.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Coincide con sistema';
        item.feedback.className = 'cash-closure-feedback is-visible is-ok';
        return;
    }

    const word = diff < 0 ? 'Faltante' : 'Sobrante';
    item.feedback.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>${word}: ${currency(Math.abs(diff))}`;
    item.feedback.className = 'cash-closure-feedback is-visible is-danger';
};

const renderDifference = (diff) => {
    const isBalanced = Math.abs(diff) < 0.01;
    const label = diff < 0 ? 'Faltante' : 'Sobrante';
    const sign = diff < 0 ? '-' : '+';

    return isBalanced
        ? `${currency(0)} <small>(Cuadrado)</small>`
        : `${sign} ${currency(Math.abs(diff))} <small>(${label})</small>`;
};

const buildClosureHtml = (data, theme) => {
    const openingBalance = numericValue(data.opening_balance);
    const cashSales = Math.max(numericValue(data.system_cash) - openingBalance, 0);
    const cashSummary = openingBalance ? cashSales : data.system_cash;
    const systemCash = numericValue(data.system_cash);
    const systemCard = numericValue(data.system_card);
    const systemSinpe = numericValue(data.system_sinpe);

    return `
        <div class="cash-closure-shell text-start">
            <aside class="cash-closure-panel">
                <h3 class="cash-closure-panel__title">Resumen del Turno</h3>
                <div class="cash-closure-summary">
                    <div class="cash-closure-summary-row">
                        <span>Apertura</span>
                        <strong>${currency(openingBalance)}</strong>
                    </div>
                    <div class="cash-closure-summary-row">
                        <span>Ventas Efectivo</span>
                        <strong>${currency(cashSummary)}</strong>
                    </div>
                    <div class="cash-closure-summary-row">
                        <span>Ventas Tarjeta</span>
                        <strong>${currency(data.system_card)}</strong>
                    </div>
                    <div class="cash-closure-summary-row">
                        <span>Ventas SINPE</span>
                        <strong>${currency(data.system_sinpe)}</strong>
                    </div>
                    <div class="cash-closure-total">
                        <span>Total Esperado</span>
                        <strong>${currency(data.system_total)}</strong>
                    </div>
                </div>
            </aside>

            <section>
                <h3 class="cash-closure-main-title">Validaci&oacute;n de Medios de Pago</h3>
                <div class="cash-closure-methods">
                    <article class="cash-closure-method">
                        <div class="cash-closure-method__head">
                            <span class="cash-closure-method__name"><i class="bi bi-cash-stack"></i>Efectivo</span>
                            <span class="cash-closure-method__system">Sistema: ${currency(data.system_cash)}</span>
                        </div>
                        <label class="cash-closure-money-field" for="physical-cash">
                            <span>&#8353;</span>
                            <input type="number" id="physical-cash" class="${theme.input}" min="0" step="0.01" value="${systemCash.toFixed(2)}" placeholder="0.00">
                        </label>
                        <div id="cash-feedback" class="cash-closure-feedback"></div>
                    </article>

                    <article class="cash-closure-method">
                        <div class="cash-closure-method__head">
                            <span class="cash-closure-method__name"><i class="bi bi-credit-card"></i>Tarjeta</span>
                            <span class="cash-closure-method__system">Sistema: ${currency(data.system_card)}</span>
                        </div>
                        <label class="cash-closure-money-field" for="physical-card">
                            <span>&#8353;</span>
                            <input type="number" id="physical-card" class="${theme.input}" min="0" step="0.01" value="${systemCard.toFixed(2)}" placeholder="0.00">
                        </label>
                        <div id="card-feedback" class="cash-closure-feedback"></div>
                    </article>

                    <article class="cash-closure-method">
                        <div class="cash-closure-method__head">
                            <span class="cash-closure-method__name"><i class="bi bi-phone"></i>SINPE M&oacute;vil</span>
                            <span class="cash-closure-method__system">Sistema: ${currency(data.system_sinpe)}</span>
                        </div>
                        <label class="cash-closure-money-field" for="physical-sinpe">
                            <span>&#8353;</span>
                            <input type="number" id="physical-sinpe" class="${theme.input}" min="0" step="0.01" value="${systemSinpe.toFixed(2)}" placeholder="0.00">
                        </label>
                        <div id="sinpe-feedback" class="cash-closure-feedback"></div>
                    </article>
                </div>
            </section>

            <aside class="cash-closure-side">
                <section class="cash-closure-panel">
                    <h3 class="cash-closure-panel__title">Notas de Cierre</h3>
                    <div class="cash-closure-notes-wrap">
                        <textarea id="closure-notes" class="cash-closure-notes" maxlength="500" placeholder="Agregar observaciones sobre el turno..."></textarea>
                    </div>
                </section>

                <section class="cash-closure-panel cash-closure-difference">
                    <span class="cash-closure-difference__label">Diferencia Total</span>
                    <strong id="diff-display" class="cash-closure-difference__amount text-success">${renderDifference(0)}</strong>
                    <div class="cash-closure-actions">
                        <button type="button" id="cash-closure-submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Finalizar Cierre
                        </button>
                        <button type="button" id="cash-closure-cancel" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-2"></i>Cancelar
                        </button>
                    </div>
                </section>
            </aside>
        </div>
    `;
};

const bindClosureModalEvents = (data) => {
    const elements = {
        cash: { input: document.getElementById('physical-cash'), feedback: document.getElementById('cash-feedback'), sys: numericValue(data.system_cash) },
        card: { input: document.getElementById('physical-card'), feedback: document.getElementById('card-feedback'), sys: numericValue(data.system_card) },
        sinpe: { input: document.getElementById('physical-sinpe'), feedback: document.getElementById('sinpe-feedback'), sys: numericValue(data.system_sinpe) },
    };
    const diffDisplay = document.getElementById('diff-display');

    const updateBalance = () => {
        let totalPhysical = 0;

        Object.values(elements).forEach(item => {
            const value = Number.isNaN(inputValue(item.input.id)) ? 0 : inputValue(item.input.id);
            totalPhysical += value;
            setFeedback(item, value - item.sys);
        });

        const grandDiff = totalPhysical - numericValue(data.system_total);
        diffDisplay.innerHTML = renderDifference(grandDiff);
        diffDisplay.className = `cash-closure-difference__amount ${Math.abs(grandDiff) < 0.01 ? 'text-success' : 'text-danger'}`;
    };

    Object.values(elements).forEach(item => item.input.addEventListener('input', updateBalance));
    updateBalance();

    document.getElementById('cash-closure-submit').addEventListener('click', () => SwalModal.clickConfirm());
    document.getElementById('cash-closure-cancel').addEventListener('click', () => SwalModal.clickCancel());
};

const showClosureModal = async (registerId) => {
    try {
        const response = await fetch(route('cash-registers.close-data', registerId));
        if (!response.ok) throw new Error("No se pudo obtener la informacion de la caja.");

        const data = await response.json();
        const theme = getThemeClasses();

        SwalModal.fire({
            title: 'Cierre de Caja',
            customClass: {
                ...ClosureModalOptions,
                popup: theme.popup,
            },
            width: 'min(1180px, calc(100vw - 2rem))',
            html: buildClosureHtml(data, theme),
            showCancelButton: true,
            showConfirmButton: true,
            confirmButtonText: 'Finalizar Cierre',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                ensureClosureStyles();
                bindClosureModalEvents(data);
            },
            didDestroy: removeClosureStyles,
            preConfirm: () => {
                const physicalCash = inputValue('physical-cash');
                const physicalCard = inputValue('physical-card');
                const physicalSinpe = inputValue('physical-sinpe');

                if ([physicalCash, physicalCard, physicalSinpe].some(Number.isNaN)) {
                    SwalModal.showValidationMessage('Por favor, ingresa los montos de todos los medios de pago.');
                    return false;
                }

                return {
                    physical_cash: physicalCash,
                    physical_card: physicalCard,
                    physical_sinpe: physicalSinpe,
                    notes: document.getElementById('closure-notes').value.trim(),
                };
            },
        }).then((result) => {
            if (result.isConfirmed) {
                saveClosure(registerId, result.value);
            }
        });
    } catch (error) {
        SwalToast.fire({
            icon: SwalNotificationTypes.ERROR,
            title: error.message || "Error al cargar datos de cierre.",
        });
    }
};

const saveClosure = async (registerId, payload) => {
    try {
        const response = await fetch(route('cash-registers.close', registerId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": typeof csrfToken !== "undefined" ? csrfToken : "",
            },
            body: JSON.stringify(payload),
        });

        const result = await response.json();
        if (!response.ok) throw new Error(result.message || "Error al procesar el cierre.");

        SwalModal.fire({
            icon: SwalNotificationTypes.SUCCESS,
            title: 'Caja Cerrada',
            text: 'El reporte de cierre ha sido generado correctamente.',
            confirmButtonText: 'Aceptar',
        }).then(() => window.location.reload());
    } catch (error) {
        SwalModal.fire({
            icon: SwalNotificationTypes.ERROR,
            title: 'Error al cerrar',
            text: error.message,
        });
    }
};

function handleClosureClick(e, registerId) {
    e.preventDefault();

    const menuItem = document.getElementById('cash-closure-menu-item');
    const triggerBtn = document.getElementById('btn-trigger-cash-closure');

    const isActive = menuItem?.getAttribute('data-is-active') === 'true';
    if (!isActive) return;

    const id = registerId || triggerBtn?.getAttribute('data-register-id');
    if (id) {
        showClosureModal(id);
    }
}

export function initializeCashClosure() {
    window.addEventListener('cash-register-opened', function(event) {
        const triggerBtn = document.getElementById('btn-trigger-cash-closure');
        if (triggerBtn) {
            triggerBtn.removeEventListener('click', handleClosureClick);
            triggerBtn.addEventListener('click', (e) => handleClosureClick(e, event.detail.id));
        }
    });

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'data-bs-theme') {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer && !swalContainer.classList.contains('swal2-hidden')) {
                    SwalModal.close();
                }
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme'],
    });

    const triggerBtn = document.getElementById('btn-trigger-cash-closure');
    if (triggerBtn) {
        triggerBtn.addEventListener('click', handleClosureClick);
    }
}
