/**
 * Scripts Globais - Timer de Provas
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Menu Mobile Toggle
    const menuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const iconBars = document.getElementById('menu-icon-bars');
    const iconClose = document.getElementById('menu-icon-close');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.contains('hidden');
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                iconBars.classList.add('hidden');
                iconClose.classList.remove('hidden');
            } else {
                mobileMenu.classList.add('hidden');
                iconBars.classList.remove('hidden');
                iconClose.classList.add('hidden');
            }
        });
    }

    // 2. Máscara de CPF nos campos correspondentes
    const cpfInputs = document.querySelectorAll('input[name="cpf"], #cpf');
    cpfInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length > 9) {
                v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (v.length > 6) {
                v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (v.length > 3) {
                v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            e.target.value = v;
        });
    });

    // 3. Toasts PHP
    document.querySelectorAll('.toast-item').forEach(toast => {
        iniciarAutoDismissToast(toast);
    });
});

function iniciarAutoDismissToast(toast) {
    const delay = parseInt(toast.getAttribute('data-auto-dismiss') || '4500', 10);
    setTimeout(() => {
        fecharToast(toast);
    }, delay);
}

function fecharToast(toastElement) {
    if (!toastElement) return;
    toastElement.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => {
        if (toastElement.parentNode) {
            toastElement.parentNode.removeChild(toastElement);
        }
    }, 300);
}

function mostrarToast(tipo, mensagem, titulo = '') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    if (!titulo) {
        titulo = tipo === 'success' ? 'Sucesso' : (tipo === 'error' ? 'Atenção' : (tipo === 'warning' ? 'Aviso' : 'Informação'));
    }

    const toast = document.createElement('div');
    toast.className = `toast-item pointer-events-auto bg-slate-900 border shadow-2xl rounded-2xl p-4 flex items-start space-x-3 transition-all duration-300 transform translate-y-2 opacity-0 ${
        tipo === 'success' ? 'border-emerald-500/40 text-emerald-400' :
        tipo === 'error' ? 'border-rose-500/40 text-rose-400' :
        tipo === 'warning' ? 'border-amber-500/40 text-amber-400' :
        'border-sky-500/40 text-sky-400'
    }`;
    toast.setAttribute('data-auto-dismiss', '4500');

    let iconHtml = '<i class="fa-solid fa-circle-info"></i>';
    if (tipo === 'success') iconHtml = '<i class="fa-solid fa-circle-check"></i>';
    if (tipo === 'error') iconHtml = '<i class="fa-solid fa-circle-exclamation"></i>';
    if (tipo === 'warning') iconHtml = '<i class="fa-solid fa-triangle-exclamation"></i>';

    toast.innerHTML = `
        <div class="text-xl flex-shrink-0 mt-0.5">
            ${iconHtml}
        </div>
        <div class="flex-1 text-slate-200">
            <h4 class="font-bold text-sm leading-tight text-white">${escapeHtml(titulo)}</h4>
            <p class="text-xs text-slate-300 mt-1">${escapeHtml(mensagem)}</p>
        </div>
        <button type="button" onclick="fecharToast(this.closest('.toast-item'))" class="text-slate-400 hover:text-white transition-colors p-1">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('opacity-0', 'translate-y-2');
    }, 20);

    iniciarAutoDismissToast(toast);
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

function abrirModal({
    titulo = 'Confirmação',
    mensagem = '',
    tipo = 'info',
    textoConfirmar = 'Confirmar',
    textoCancelar = 'Cancelar',
    aoConfirmar = null,
    esconderCancelar = false
}) {
    const container = document.getElementById('modal-container');
    const card = document.getElementById('modal-card');
    const titleEl = document.getElementById('modal-title');
    const bodyEl = document.getElementById('modal-body');
    const iconContainer = document.getElementById('modal-icon-container');
    const iconEl = document.getElementById('modal-icon');
    const btnConfirmar = document.getElementById('modal-btn-confirmar');
    const btnCancelar = document.getElementById('modal-btn-cancelar');

    if (!container) return;

    titleEl.textContent = titulo;
    bodyEl.innerHTML = mensagem;

    iconContainer.className = 'w-9 h-9 rounded-xl flex items-center justify-center border ';
    btnConfirmar.className = 'px-4 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-lg ';

    if (tipo === 'danger') {
        iconContainer.classList.add('bg-rose-500/10', 'border-rose-500/20', 'text-rose-400');
        iconEl.className = 'fa-solid fa-triangle-exclamation';
        btnConfirmar.classList.add('bg-rose-600', 'hover:bg-rose-500', 'shadow-rose-500/20');
    } else if (tipo === 'warning') {
        iconContainer.classList.add('bg-amber-500/10', 'border-amber-500/20', 'text-amber-400');
        iconEl.className = 'fa-solid fa-triangle-exclamation';
        btnConfirmar.classList.add('bg-amber-600', 'hover:bg-amber-500', 'shadow-amber-500/20');
    } else if (tipo === 'success') {
        iconContainer.classList.add('bg-emerald-500/10', 'border-emerald-500/20', 'text-emerald-400');
        iconEl.className = 'fa-solid fa-circle-check';
        btnConfirmar.classList.add('bg-emerald-600', 'hover:bg-emerald-500', 'shadow-emerald-500/20');
    } else {
        iconContainer.classList.add('bg-sky-500/10', 'border-sky-500/20', 'text-sky-400');
        iconEl.className = 'fa-solid fa-circle-info';
        btnConfirmar.classList.add('bg-sky-600', 'hover:bg-sky-500', 'shadow-sky-500/20');
    }

    btnConfirmar.textContent = textoConfirmar;
    btnCancelar.textContent = textoCancelar;
    btnCancelar.style.display = esconderCancelar ? 'none' : 'inline-block';

    btnConfirmar.onclick = () => {
        fecharModal();
        if (typeof aoConfirmar === 'function') {
            aoConfirmar();
        }
    };

    container.classList.remove('hidden');
    setTimeout(() => {
        container.classList.remove('opacity-0');
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    }, 20);
}

function fecharModal() {
    const container = document.getElementById('modal-container');
    const card = document.getElementById('modal-card');
    if (!container) return;

    container.classList.add('opacity-0');
    if (card) {
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
    }
    setTimeout(() => {
        container.classList.add('hidden');
    }, 200);
}

function confirmarAcao(mensagem, aoConfirmar, titulo = 'Confirmar Ação', tipo = 'warning') {
    abrirModal({
        titulo,
        mensagem,
        tipo,
        textoConfirmar: 'Sim, prosseguir',
        textoCancelar: 'Cancelar',
        aoConfirmar
    });
}

function abrirAlertaSala(titulo, mensagem) {
    const modal = document.getElementById('modal-alerta-sala');
    if (!modal) return;
    document.getElementById('alerta-sala-titulo').textContent = titulo;
    document.getElementById('alerta-sala-mensagem').textContent = mensagem;

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
        modal.querySelector('.transform').classList.add('scale-100');
    }, 20);
}

function fecharAlertaSala() {
    const modal = document.getElementById('modal-alerta-sala');
    if (!modal) return;
    modal.classList.add('opacity-0');
    modal.querySelector('.transform').classList.remove('scale-100');
    modal.querySelector('.transform').classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
