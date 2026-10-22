<?php
/**
 * Componente Global de Modais - Timer de Provas
 */
?>
<!-- 1. Modal Universal de Confirmação -->
<div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div id="modal-card" class="bg-slate-900 border border-slate-700/80 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden transform scale-95 transition-transform duration-200">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div id="modal-icon-container" class="w-9 h-9 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center">
                    <i id="modal-icon" class="fa-solid fa-circle-info"></i>
                </div>
                <h3 id="modal-title" class="text-base font-bold text-white">Título do Modal</h3>
            </div>
            <button type="button" onclick="fecharModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="px-6 py-5 text-sm text-slate-300 leading-relaxed" id="modal-body">
            Mensagem descritiva do modal.
        </div>

        <div class="px-6 py-3 bg-slate-950/60 border-t border-slate-800 flex items-center justify-end space-x-2" id="modal-actions">
            <button type="button" id="modal-btn-cancelar" onclick="fecharModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                Cancelar
            </button>
            <button type="button" id="modal-btn-confirmar" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-colors shadow-lg shadow-sky-500/20">
                Confirmar
            </button>
        </div>
    </div>
</div>

<!-- 2. Modal de Alerta / Notificação em Tela Cheia para a Sala de Aula -->
<div id="modal-alerta-sala" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/90 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div class="bg-gradient-to-br from-slate-900 to-slate-950 border-2 border-amber-500/60 rounded-3xl shadow-2xl max-w-xl w-full p-8 text-center transform scale-95 transition-transform duration-300 relative overflow-hidden">
        <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-48 h-48 bg-amber-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-20 h-20 mx-auto rounded-3xl bg-amber-500/20 border border-amber-500/40 text-amber-400 flex items-center justify-center text-4xl mb-5 animate-bounce shadow-xl shadow-amber-500/25">
            <i class="fa-solid fa-bell"></i>
        </div>

        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold uppercase tracking-wider mb-3">
            <i class="fa-solid fa-bullhorn"></i> Aviso da Coordenação
        </div>

        <h2 class="text-2xl sm:text-3xl font-black text-white mb-3" id="alerta-sala-titulo">
            Atenção Alunos!
        </h2>

        <p class="text-base sm:text-lg text-slate-200 leading-relaxed font-medium mb-6" id="alerta-sala-mensagem">
            Restam apenas 15 minutos para o término da prova. Comecem o preenchimento da folha de respostas.
        </p>

        <div class="flex items-center justify-center">
            <button type="button" onclick="fecharAlertaSala()" class="px-8 py-3.5 rounded-2xl font-black text-sm text-slate-950 bg-gradient-to-r from-amber-400 to-yellow-300 hover:from-amber-300 hover:to-yellow-200 shadow-xl shadow-amber-500/30 transition-transform hover:scale-105">
                <i class="fa-solid fa-check mr-2"></i> Ciente / Continuar Prova
            </button>
        </div>
    </div>
</div>
