<?php
/**
 * Tela Principal do Timer de Prova (Modo Aplicação e Modo Telão/Projetor)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/timer.php';

$provaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$prova = obterProva($provaId);

if (!$prova) {
    adicionarToast('error', 'Prova ou timer não encontrado.');
    header('Location: index.php');
    exit;
}

$pageTitle = 'Timer: ' . $prova['titulo'];
$usuario = getUsuarioAtual();
$podeControlar = usuarioLogado();

// Segundos restantes a exibir
$segundosRestantes = $prova['segundos_restantes'] !== null ? (int)$prova['segundos_restantes'] : (int)$prova['tempo_total_segundos'];

require_once __DIR__ . '/includes/header.php';
?>

<div id="timer-main-container" class="space-y-6 timer-container-fullscreen">
    <!-- Barra Superior de Controle e Metadados -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-sky-500/10 text-sky-300 border border-sky-500/20">
                    <i class="fa-solid fa-door-open mr-1.5 text-[10px]"></i> <?= htmlspecialchars($prova['sala'] ?: 'Sala Geral') ?>
                </span>

                <span id="timer-status-badge"></span>

                <?php if ($prova['alerta_som']): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                        <i class="fa-solid fa-volume-high text-sky-400 mr-1.5"></i> Som: <?= ucfirst($prova['tipo_som']) ?>
                    </span>
                <?php endif; ?>

                <?php if ($prova['tempo_alerta_intervalo'] > 0): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                        <i class="fa-solid fa-bell text-amber-400 mr-1.5"></i> A cada <?= round($prova['tempo_alerta_intervalo'] / 60) ?> min
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                <?= htmlspecialchars($prova['titulo']) ?>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <button type="button" id="btn-timer-fullscreen" class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors flex items-center shadow-md">
                <i class="fa-solid fa-expand mr-1.5 text-sky-400"></i> Modo Telão (Fullscreen)
            </button>

            <button type="button" id="btn-testar-som" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors flex items-center">
                <i class="fa-solid fa-volume-high mr-1.5 text-amber-400"></i> Testar Som
            </button>

            <a href="historico.php?prova_id=<?= $prova['id'] ?>" title="Ver Trilha de Auditoria" class="p-2 rounded-xl text-slate-400 hover:text-emerald-400 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">
                <i class="fa-solid fa-shield-halved text-sm"></i>
            </a>

            <?php if ($podeControlar): ?>
                <a href="configurar_timer.php?id=<?= $prova['id'] ?>" title="Editar Configurações" class="p-2 rounded-xl text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">
                    <i class="fa-solid fa-gear text-sm"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Card Principal do Relógio Gigante -->
    <div class="timer-hero-card rounded-3xl p-6 sm:p-12 text-center relative overflow-hidden">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-96 h-48 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="text-xs sm:text-sm font-semibold uppercase tracking-widest text-slate-400 mb-4">
            Tempo Restante de Prova
        </div>

        <!-- Dígitos Digitais Gigantes (HH : MM : SS) -->
        <div id="timer-digits-container" class="font-timer text-5xl sm:text-7xl md:text-8xl lg:text-9xl font-black tracking-wider flex items-center justify-center select-none text-sky-400 digits-glow-sky py-2">
            <span id="timer-horas">00</span>
            <span class="mx-1 sm:mx-3 opacity-60 text-slate-500">:</span>
            <span id="timer-minutos">00</span>
            <span class="mx-1 sm:mx-3 opacity-60 text-slate-500">:</span>
            <span id="timer-segundos">00</span>
        </div>

        <!-- Barra de Progresso de Tempo Decorrido -->
        <div class="max-w-2xl mx-auto mt-6">
            <div class="w-full bg-slate-950 rounded-full h-3.5 p-0.5 border border-slate-800 overflow-hidden shadow-inner">
                <div id="timer-progress-bar" class="bg-gradient-to-r from-sky-500 via-indigo-500 to-emerald-400 h-2.5 rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-400 mt-2 font-medium px-1">
                <span>Início (<?= formatarSegundos($prova['tempo_total_segundos']) ?>)</span>
                <span id="timer-progress-text" class="text-slate-300 font-semibold">0% decorrido</span>
                <span>Término (00:00:00)</span>
            </div>
        </div>

        <!-- Controles Principais do Coordenador -->
        <?php if ($podeControlar): ?>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3 pt-6 border-t border-slate-800/80">
                <!-- Botão Iniciar -->
                <button type="button" id="btn-timer-iniciar" class="px-8 py-3.5 rounded-2xl font-black text-sm text-white bg-gradient-to-r from-emerald-600 to-brand-600 hover:from-emerald-500 hover:to-brand-500 shadow-xl shadow-emerald-500/25 transition-transform hover:scale-105 flex items-center">
                    <i class="fa-solid fa-play mr-2 text-base"></i> Iniciar Prova
                </button>

                <!-- Botão Pausar -->
                <button type="button" id="btn-timer-pausar" class="hidden px-7 py-3.5 rounded-2xl font-bold text-sm text-slate-950 bg-amber-400 hover:bg-amber-300 shadow-xl shadow-amber-500/25 transition-all flex items-center">
                    <i class="fa-solid fa-pause mr-2 text-base"></i> Pausar Timer
                </button>

                <!-- Botão Retomar -->
                <button type="button" id="btn-timer-retomar" class="hidden px-7 py-3.5 rounded-2xl font-bold text-sm text-white bg-emerald-600 hover:bg-emerald-500 shadow-xl shadow-emerald-500/25 transition-all flex items-center">
                    <i class="fa-solid fa-play mr-2 text-base"></i> Retomar Timer
                </button>

                <!-- Acréscimo de Tempo (+5min e +10min) -->
                <div class="flex items-center space-x-1.5 bg-slate-950/80 p-1 rounded-2xl border border-slate-800">
                    <button type="button" class="btn-acrescimo px-3 py-2 rounded-xl text-xs font-bold text-sky-300 hover:text-white hover:bg-sky-600/30 transition-colors" data-minutos="5" title="Conceder +5 minutos">
                        +5 min
                    </button>
                    <button type="button" class="btn-acrescimo px-3 py-2 rounded-xl text-xs font-bold text-sky-300 hover:text-white hover:bg-sky-600/30 transition-colors" data-minutos="10" title="Conceder +10 minutos">
                        +10 min
                    </button>
                </div>

                <!-- Botão Reiniciar -->
                <button type="button" id="btn-timer-reiniciar" class="px-4 py-3 rounded-2xl text-xs font-semibold text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors">
                    <i class="fa-solid fa-rotate-left mr-1.5"></i> Reiniciar
                </button>

                <!-- Botão Concluir Prova -->
                <button type="button" id="btn-timer-concluir" class="px-5 py-3 rounded-2xl text-xs font-bold text-white bg-slate-800 hover:bg-rose-600 border border-slate-700 hover:border-rose-500 transition-colors">
                    <i class="fa-solid fa-flag-checkered mr-1.5"></i> Encerrar Prova
                </button>
            </div>
        <?php else: ?>
            <div class="mt-6 text-xs text-slate-400">
                <i class="fa-solid fa-eye text-sky-400 mr-1.5"></i> Modo visualizador de telão. Apenas o coordenador autenticado pode pausar ou alterar a contagem.
            </div>
        <?php endif; ?>
    </div>

    <!-- Instruções e Avisos aos Alunos na Sala -->
    <?php if (!empty($prova['instrucoes'])): ?>
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8">
            <div class="flex items-center space-x-2 text-xs font-bold text-amber-400 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Orientações aos Candidatos / Alunos</span>
            </div>
            <div class="text-sm sm:text-base text-slate-200 leading-relaxed space-y-2 whitespace-pre-line font-medium">
                <?= htmlspecialchars($prova['instrucoes']) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Script de Inicialização do Timer -->
<script src="assets/js/timer.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    window.examTimer = new ExamTimer({
        provaId: <?= (int)$prova['id'] ?>,
        tempoTotal: <?= (int)$prova['tempo_total_segundos'] ?>,
        segundosRestantes: <?= (int)$segundosRestantes ?>,
        intervaloAlerta: <?= (int)$prova['tempo_alerta_intervalo'] ?>,
        alertaSom: <?= $prova['alerta_som'] ? 'true' : 'false' ?>,
        tipoSom: '<?= addslashes($prova['tipo_som'] ?? 'beep') ?>',
        alertaAvisoTela: <?= $prova['alerta_aviso_tela'] ? 'true' : 'false' ?>,
        alertaVoz: <?= $prova['alerta_voz'] ? 'true' : 'false' ?>,
        status: '<?= addslashes($prova['status']) ?>'
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
