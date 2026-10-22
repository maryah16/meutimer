<?php
/**
 * Página Inicial / Painel do Coordenador - Timer de Provas
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/timer.php';

$pageTitle = 'Painel de Timers e Avaliações';
$usuario = getUsuarioAtual();
$provas = $usuario ? listarProvas($usuario['id']) : listarProvas();

// Estatísticas
$db = getDB();
$totalProvas = (int)$db->query("SELECT COUNT(*) FROM provas")->fetchColumn();
$provasEmAndamento = (int)$db->query("SELECT COUNT(*) FROM provas WHERE status = 'em_andamento'")->fetchColumn();
$provasConcluidas = (int)$db->query("SELECT COUNT(*) FROM provas WHERE status = 'concluida'")->fetchColumn();
$totalLogs = (int)$db->query("SELECT COUNT(*) FROM logs_auditoria")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner Principal -->
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-sky-950/30 to-slate-900 border border-slate-800 p-8 sm:p-12 mb-10 shadow-2xl">
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 max-w-3xl">
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-sky-500/10 border border-sky-500/30 text-sky-300 text-xs font-bold uppercase tracking-wider mb-5">
            <i class="fa-solid fa-stopwatch text-sky-400"></i>
            <span>Sistema Oficial de Controle de Tempo para Exames</span>
        </div>

        <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight mb-4">
            Timer de Provas com <span class="bg-gradient-to-r from-sky-400 via-brand-300 to-sky-500 bg-clip-text text-transparent">alertas configuráveis</span> e auditoria completa.
        </h1>

        <p class="text-base sm:text-lg text-slate-300 leading-relaxed mb-8">
            Controle o tempo de vestibulares, provas escolares e concursos com precisão. Configure alertas sonoros, avisos na tela e sintetizador de voz com modo de projeção em tela cheia para salas de aula.
        </p>

        <div class="flex flex-wrap items-center gap-4">
            <?php if ($usuario): ?>
                <a href="configurar_timer.php" class="px-6 py-3.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 shadow-xl shadow-sky-500/25 transition-all flex items-center">
                    <i class="fa-solid fa-plus-circle mr-2 text-base"></i> Configurar Novo Timer
                </a>
                <a href="historico.php" class="px-5 py-3.5 rounded-xl font-semibold text-sm text-slate-300 bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 transition-all flex items-center">
                    <i class="fa-solid fa-shield-halved mr-2 text-emerald-400"></i> Trilha de Auditoria
                </a>
            <?php else: ?>
                <a href="login.php" class="px-6 py-3.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 shadow-xl shadow-sky-500/25 transition-all flex items-center">
                    <i class="fa-solid fa-arrow-right-to-bracket mr-2 text-base"></i> Entrar como Coordenador
                </a>
                <a href="registro.php" class="px-5 py-3.5 rounded-xl font-semibold text-sm text-slate-200 bg-slate-800/80 hover:bg-slate-700 border border-slate-700 transition-all flex items-center">
                    <i class="fa-solid fa-user-plus mr-2 text-sky-400"></i> Cadastrar Coordenador
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Métricas do Sistema -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-file-signature"></i>
        </div>
        <div>
            <div class="text-2xl font-black text-white"><?= $totalProvas ?></div>
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Provas Cadastradas</div>
        </div>
    </div>

    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div>
            <div class="text-2xl font-black text-white"><?= $provasEmAndamento ?></div>
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Em Andamento</div>
        </div>
    </div>

    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div class="text-2xl font-black text-white"><?= $provasConcluidas ?></div>
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Concluídas</div>
        </div>
    </div>

    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div>
            <div class="text-2xl font-black text-white"><?= $totalLogs ?></div>
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Logs Auditados</div>
        </div>
    </div>
</div>

<!-- Lista de Timers / Provas -->
<div class="space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-white flex items-center">
                <i class="fa-solid fa-stopwatch text-sky-400 mr-2.5"></i> Provas e Timers Configurados
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Selecione uma prova para abrir o relógio de contagem regressiva em tela cheia</p>
        </div>
        <?php if ($usuario): ?>
            <a href="configurar_timer.php" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-colors flex items-center">
                <i class="fa-solid fa-plus mr-1.5"></i> Novo Timer
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($provas)): ?>
        <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-12 text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-800 text-slate-500 flex items-center justify-center text-2xl mb-4">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">Nenhum timer configurado ainda</h3>
            <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto mb-6">
                Defina o tempo total, alertas sonoros e avisos de sala para iniciar a contagem regressiva oficial.
            </p>
            <?php if ($usuario): ?>
                <a href="configurar_timer.php" class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-colors">
                    <i class="fa-solid fa-plus mr-1.5"></i> Configurar Primeiro Timer
                </a>
            <?php else: ?>
                <a href="login.php" class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-colors">
                    <i class="fa-solid fa-arrow-right-to-bracket mr-1.5"></i> Faça Login para Configurar
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($provas as $p): ?>
                <?php
                $segRest = $p['segundos_restantes'] !== null ? (int)$p['segundos_restantes'] : (int)$p['tempo_total_segundos'];
                $percentual = $p['tempo_total_segundos'] > 0 ? Math.min(100, round((($p['tempo_total_segundos'] - $segRest) / $p['tempo_total_segundos']) * 100)) : 0;
                ?>
                <div class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-3xl p-6 flex flex-col justify-between transition-all hover:shadow-2xl hover:-translate-y-1 group">
                    <div>
                        <!-- Header do Card -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-sky-500/10 text-sky-300 border border-sky-500/20">
                                <i class="fa-solid fa-door-open mr-1.5 text-[10px]"></i> <?= htmlspecialchars($p['sala'] ?: 'Sala Geral') ?>
                            </span>

                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider
                                <?= $p['status'] === 'em_andamento' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 
                                   ($p['status'] === 'pausada' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 
                                   ($p['status'] === 'concluida' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30' : 
                                   'bg-slate-800 text-slate-400 border border-slate-700')) ?>">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $p['status'] === 'em_andamento' ? 'bg-emerald-400 animate-pulse' : ($p['status'] === 'pausada' ? 'bg-amber-400' : ($p['status'] === 'concluida' ? 'bg-rose-400' : 'bg-slate-400')) ?>"></span>
                                <?= $p['status'] === 'em_andamento' ? 'Em Andamento' : ($p['status'] === 'pausada' ? 'Pausada' : ($p['status'] === 'concluida' ? 'Encerrada' : 'Configurada')) ?>
                            </span>
                        </div>

                        <!-- Título da Prova -->
                        <h3 class="text-lg font-bold text-white group-hover:text-sky-300 transition-colors line-clamp-1 mb-1">
                            <?= htmlspecialchars($p['titulo']) ?>
                        </h3>

                        <div class="text-xs text-slate-400 mb-4 flex items-center space-x-2">
                            <span><i class="fa-solid fa-user-tie text-slate-500 mr-1"></i> <?= htmlspecialchars($p['coordenador_nome']) ?></span>
                            <span>&bull;</span>
                            <span><i class="fa-solid fa-clock text-slate-500 mr-1"></i> <?= formatarSegundos($p['tempo_total_segundos']) ?></span>
                        </div>

                        <!-- Resumo dos Alertas Ativos -->
                        <div class="mb-4 flex flex-wrap gap-1.5">
                            <?php if ($p['alerta_som']): ?>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 border border-slate-700" title="Alerta Sonoro Ativo">
                                    <i class="fa-solid fa-volume-high text-sky-400 mr-1"></i> Som: <?= ucfirst($p['tipo_som']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($p['alerta_aviso_tela']): ?>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 border border-slate-700" title="Aviso Visual em Tela">
                                    <i class="fa-solid fa-display text-amber-400 mr-1"></i> Avisos em Tela
                                </span>
                            <?php endif; ?>
                            <?php if ($p['tempo_alerta_intervalo'] > 0): ?>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 border border-slate-700">
                                    <i class="fa-solid fa-repeat text-indigo-400 mr-1"></i> A cada <?= round($p['tempo_alerta_intervalo'] / 60) ?> min
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Display de Tempo Restante -->
                        <div class="p-3 bg-slate-950/80 rounded-2xl border border-slate-800 mb-4 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400">Tempo Restante:</span>
                            <span class="font-timer text-lg font-bold <?= $p['status'] === 'concluida' ? 'text-rose-400' : 'text-sky-400' ?>">
                                <?= formatarSegundos($segRest) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Ações -->
                    <div class="flex items-center space-x-2 pt-3 border-t border-slate-800/80">
                        <a href="timer.php?id=<?= $p['id'] ?>" class="flex-1 py-2.5 px-3 rounded-xl text-center text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-md shadow-sky-600/25 transition-all flex items-center justify-center">
                            <i class="fa-solid fa-play mr-1.5"></i> Abrir Timer
                        </a>
                        <?php if ($usuario && ($usuario['id'] == $p['coordenador_id'])): ?>
                            <a href="configurar_timer.php?id=<?= $p['id'] ?>" title="Editar Configurações" class="p-2.5 rounded-xl text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">
                                <i class="fa-solid fa-gear text-xs"></i>
                            </a>
                            <button type="button" onclick="confirmarExclusaoProva(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['titulo'])) ?>')" title="Excluir Prova" class="p-2.5 rounded-xl text-slate-400 hover:text-rose-400 bg-slate-800 hover:bg-rose-500/10 border border-slate-700 hover:border-rose-500/30 transition-colors">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmarExclusaoProva(provaId, titulo) {
    confirmarAcao(
        `Tem certeza que deseja excluir permanentemente o timer da prova <strong>"${escapeHtml(titulo)}"</strong>? Esta operação será registrada nos logs de auditoria.`,
        () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'excluir_prova.php';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'prova_id';
            input.value = provaId;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        },
        'Excluir Prova',
        'danger'
    );
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
