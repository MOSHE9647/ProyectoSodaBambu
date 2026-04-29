import { SwalModal, SwalToast, SwalNotificationTypes } from "../../utils/sweetalert.js";

/**
 * Get current theme (light or dark)
 */
const getTheme = () => document.documentElement.getAttribute('data-bs-theme') || 'light';

/**
 * Get dynamic classes based on current theme
 */
const getThemeClasses = () => {
    const isDark = getTheme() === 'dark';
    return {
        popup: isDark 
            ? 'swal-popup-dark bg-dark text-white rounded-4 border-success shadow-lg' 
            : 'bg-body text-body rounded-4 border-success shadow-lg border',
        input: isDark 
            ? 'bg-dark text-white border-secondary' 
            : 'bg-body text-body border-secondary',
        inputGroup: isDark 
            ? 'bg-dark text-white border-secondary' 
            : 'bg-body text-body border-secondary',
        container: isDark 
            ? 'bg-secondary bg-opacity-10' 
            : 'bg-light',
        text: isDark ? 'text-white' : 'text-body',
        textMuted: isDark ? 'text-white-50' : 'text-muted',
    };
};

const ClosureModalOptions = {
    confirmButton: 'btn btn-success px-4 py-2 fw-bold',
    cancelButton: 'btn btn-outline-secondary px-4 py-2',
};

/**
 * Formats numbers as Costa Rican currency
 */
const formatCR = (val) => Number(val).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/**
 * Fetches the server data and displays the cash closure modal.
 */
const showClosureModal = async (registerId) => {
    try {
        const response = await fetch(route('cash-registers.close-data', registerId));
        if (!response.ok) throw new Error("No se pudo obtener la información de la caja.");
        
        const data = await response.json();
        const theme = getThemeClasses();

        SwalModal.fire({
            title: '<i class="bi bi-cash-register me-2 text-success"></i>Cierre de Caja',
            customClass: {
                ...ClosureModalOptions,
                popup: theme.popup
            },
            width: '550px',
            html: `
                <div class="text-start p-2">
                    <div class="mb-4 d-flex justify-content-between align-items-center border-bottom border-secondary pb-2">
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">${data.total_orders} órdenes</span>
                        <strong class="fs-5 ${theme.text}">Total Sistema: ₡ ${formatCR(data.system_total)}</strong>
                    </div>
                    
                    <p class="small ${theme.textMuted} mb-3 text-uppercase fw-bold ls-wide">Validación de Medios de Pago</p>

                    <!-- EFECTIVO -->
                    <div class="${theme.container} p-3 rounded-3 mb-2 border border-secondary shadow-sm">
                        <div class="d-flex justify-content-between mb-2 align-items-center">
                            <span class="${theme.text}"><i class="bi bi-cash-stack me-2 text-success"></i>Efectivo</span>
                            <span class="small ${theme.textMuted}">Sistema: ₡ ${formatCR(data.system_cash)}</span>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text ${theme.inputGroup}">₡</span>
                            <input type="number" id="physical-cash" class="form-control ${theme.input}" placeholder="Monto en efectivo">
                        </div>
                        <div id="cash-feedback" class="small mt-2 d-none p-2 rounded"></div>
                    </div>

                    <!-- TARJETA -->
                    <div class="${theme.container} p-3 rounded-3 mb-2 border border-secondary border-opacity-50">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="${theme.text}"><i class="bi bi-credit-card me-2 text-primary"></i>Tarjeta</span>
                            <span class="small ${theme.textMuted}">Sistema: ₡ ${formatCR(data.system_card)}</span>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text ${theme.inputGroup}">₡</span>
                            <input type="number" id="physical-card" class="form-control ${theme.input}" placeholder="Monto según váuchers">
                        </div>
                        <div id="card-feedback" class="small mt-2 d-none p-2 rounded"></div>
                    </div>

                    <!-- SINPE MÓVIL -->
                    <div class="${theme.container} p-3 rounded-3 mb-3 border border-secondary border-opacity-50">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="${theme.text}"><i class="bi bi-phone me-2 text-info"></i>SINPE Móvil</span>
                            <span class="small ${theme.textMuted}">Sistema: ₡ ${formatCR(data.system_sinpe)}</span>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text ${theme.inputGroup}">₡</span>
                            <input type="number" id="physical-sinpe" class="form-control ${theme.input}" value="${formatCR(data.system_sinpe)}" placeholder="Monto según transferencias">
                        </div>
                        <div id="sinpe-feedback" class="small mt-2 d-none p-2 rounded"></div>
                    </div>

                    <div class="mt-3">
                        <label class="small ${theme.textMuted} mb-1 text-uppercase fw-bold">Notas de Cierre (Opcional)</label>
                        <textarea id="closure-notes" class="form-control ${theme.input}" rows="2" placeholder="Observaciones adicionales..."></textarea>
                    </div>

                    <!-- RESUMEN FINAL -->
                    <div class="border-top border-secondary pt-3 mt-3">
                        <div class="d-flex justify-content-between align-items-center fs-5 fw-bold">
                            <span class="${theme.text}">Diferencia Total</span>
                            <span id="diff-display" class="text-danger">- ₡ ${formatCR(data.system_total)}</span>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Finalizar Cierre',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                const elements = {
                    cash: { input: document.getElementById('physical-cash'), feedback: document.getElementById('cash-feedback'), sys: data.system_cash },
                    card: { input: document.getElementById('physical-card'), feedback: document.getElementById('card-feedback'), sys: data.system_card },
                    sinpe: { input: document.getElementById('physical-sinpe'), feedback: document.getElementById('sinpe-feedback'), sys: data.system_sinpe }
                };
                const diffDisplay = document.getElementById('diff-display');

                const updateBalance = () => {
                    let totalPhysical = 0;
                    
                    Object.values(elements).forEach(item => {
                        const val = parseFloat(item.input.value) || 0;
                        totalPhysical += val;
                        const diff = val - item.sys;

                        item.feedback.classList.remove('d-none');
                        if (diff === 0) {
                            item.feedback.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Coincide con sistema';
                            item.feedback.className = 'small mt-2 p-2 rounded bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                        } else {
                            const word = diff < 0 ? 'Faltante' : 'Sobrante';
                            item.feedback.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>${word}: ₡ ${formatCR(Math.abs(diff))}`;
                            item.feedback.className = 'small mt-2 p-2 rounded bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                        }
                    });

                    const grandDiff = totalPhysical - data.system_total;
                    diffDisplay.textContent = `${grandDiff < 0 ? '-' : '+'} ₡ ${formatCR(Math.abs(grandDiff))}`;
                    diffDisplay.className = Math.abs(grandDiff) < 0.01 ? 'text-success' : 'text-danger';
                };

                ['physical-cash', 'physical-card', 'physical-sinpe'].forEach(id => {
                    document.getElementById(id).addEventListener('input', updateBalance);
                });
            },
            preConfirm: () => {
                const val = (id) => parseFloat(document.getElementById(id).value);
                
                if (isNaN(val('physical-cash')) || isNaN(val('physical-card')) || isNaN(val('physical-sinpe'))) {
                    SwalModal.showValidationMessage('Por favor, ingresa los montos de todos los medios de pago.');
                    return false;
                }

                return { 
                    physical_cash: val('physical-cash'),
                    physical_card: val('physical-card'),
                    physical_sinpe: val('physical-sinpe'),
                    notes: document.getElementById('closure-notes').value.trim()
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                saveClosure(registerId, result.value);
            }
        });

    } catch (error) {
        SwalToast.fire({
            icon: SwalNotificationTypes.ERROR,
            title: error.message || "Error al cargar datos de cierre."
        });
    }
};

/**
 * Sends the final data to the server
 */
const saveClosure = async (registerId, payload) => {
    try {
        const response = await fetch(route('cash-registers.close', registerId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                "X-Requested-With": "XMLHttpRequest",
				"X-CSRF-TOKEN":
					typeof csrfToken !== "undefined" ? csrfToken : "",
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (!response.ok) throw new Error(result.message || "Error al procesar el cierre.");

        SwalModal.fire({
            icon: SwalNotificationTypes.SUCCESS,
            title: '¡Caja Cerrada!',
            text: 'El reporte de cierre ha sido generado correctamente.',
            confirmButtonText: 'Aceptar'
        }).then(() => window.location.reload());

    } catch (error) {
        SwalModal.fire({
            icon: SwalNotificationTypes.ERROR,
            title: 'Error al cerrar',
            text: error.message
        });
    }
};

/**
 * Event handler for the cash closure trigger button.
 */
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
    // Escuchar el evento personalizado para re-vincular o asegurar que el botón funciona
    window.addEventListener('cash-register-opened', function(event) {
        const triggerBtn = document.getElementById('btn-trigger-cash-closure');
        if (triggerBtn) {
            // Removemos por si acaso ya existe para no duplicar listeners
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
        attributeFilter: ['data-bs-theme']
    });

    const triggerBtn = document.getElementById('btn-trigger-cash-closure');
    if (triggerBtn) {
        triggerBtn.addEventListener('click', handleClosureClick);
    }
}