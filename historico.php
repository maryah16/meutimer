<?php
/**
 * Página de Históricos e Auditoria - Timer de Provas
 * Registra todas as ações e alterações realizadas pelos coordenadores
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/logs.php';
require_once __DIR__ . '/backend/timer.php';

$pageTitle = 'Histórico & Auditoria de Provas';

$filtros = [];
if (!empty($_GET['prova_id'])) {
    $filtros['prova_id'] = (int)$_GET['prova_id'];
}
if (!empty($_GET['acao'])) {
    $filtros['acao'] = limparInput($_GET['acao']);
}
if (!empty($_GET['busca'])) {
    $filtros['busca'] = limparInput($_GET['busca']);
}

$pagina = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limite = 25;
$offset = ($pagina - 1) * $limite;

$dadosLogs = listarLogs($filtros, $limite, $offset);
$logs = $dadosLogs['logs'];
$total = $dadosLogs['total'];
$totalPaginas = (int)ceil($total / $limite);

$todasProvas = listarProvas();
$acoesRegistradas = obterAcoesRegistradas();

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-xs text-emerald-400 font-bold uppercase tracking-wider mb-1">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Auditoria & Rastreabilidade</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center">
                Histórico de Operações e Timers
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Registro imutável de todas as ações: criação de timers, alertas disparados, pausas e acréscimos de tempo.
            </p>
        </div>

        <div>
            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-900 border border-slate-800 text-slate-300">
                <i class="fa-solid fa-database text-sky-400 mr-2"></i> <?= $total ?> eventos auditados
            </span>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <form method="GET" action="historico.php" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Prova -->
        <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Filtrar por Prova</label>
            <select name="prova_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-sky-500">
                <option value="">Todas as Provas</option>
                <?php foreach ($todasProvas as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (!empty($filtros['prova_id']) && $filtros['prova_id'] == $p['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['titulo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Tipo de Ação -->
        <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Tipo de Ação</label>
            <select name="acao" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-sky-500">
                <option value="">Todas as Ações</option>
                <?php foreach ($acoesRegistradas as $ac): ?>
                    <option value="<?= htmlspecialchars($ac) ?>" <?= (!empty($filtros['acao']) && $filtros['acao'] === $ac) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ac) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Palavra-chave -->
        <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Buscar por Palavra-chave</label>
            <div class="relative">
                <input type="text" name="busca" value="<?= htmlspecialchars($filtros['busca'] ?? '') ?>" placeholder="Coordenador, descrição..."
                    class="w-full pl-8 pr-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-sky-500">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-500 text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
            </div>
        </div>

        <!-- Botões -->
        <div class="flex items-end space-x-2">
            <button type="submit" class="flex-1 py-2 px-3 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-colors">
                <i class="fa-solid fa-filter mr-1"></i> Filtrar
            </button>
            <?php if (!empty($filtros)): ?>
                <a href="historico.php" title="Limpar Filtros" class="p-2 rounded-xl text-xs text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabela de Logs -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
        <?php if (empty($logs)): ?>
            <div class="p-12 text-center">
                <div class="w-12 h-12 mx-auto rounded-xl bg-slate-800 text-slate-500 flex items-center justify-center text-xl mb-3">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-sm font-bold text-white mb-1">Nenhum evento registrado</h3>
                <p class="text-xs text-slate-400">Nenhum log encontrado para os critérios selecionados.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/80 border-b border-slate-800 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Data / Hora</th>
                            <th class="py-3 px-4">Coordenador & IP</th>
                            <th class="py-3 px-4">Prova Vinculada</th>
                            <th class="py-3 px-4">Ação</th>
                            <th class="py-3 px-4">Descrição da Operação</th>
                            <th class="py-3 px-4 text-center">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 text-slate-300">
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $badgeClass = 'bg-slate-800 text-slate-300 border-slate-700';
                            if (strpos($log['acao'], 'INICIAR') !== false) {
                                $badgeClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
                            } elseif (strpos($log['acao'], 'PAUSAR') !== false || strpos($log['acao'], 'ALERTA') !== false) {
                                $badgeClass = 'bg-amber-500/10 text-amber-300 border-amber-500/30';
                            } elseif (strpos($log['acao'], 'ACRESCIMO') !== false) {
                                $badgeClass = 'bg-indigo-500/10 text-indigo-300 border-indigo-500/30';
                            } elseif (strpos($log['acao'], 'CONCLUSAO') !== false || strpos($log['acao'], 'EXCLUSAO') !== false) {
                                $badgeClass = 'bg-rose-500/10 text-rose-300 border-rose-500/30';
                            } elseif (strpos($log['acao'], 'LOGIN') !== false || strpos($log['acao'], 'TIMER') !== false) {
                                $badgeClass = 'bg-sky-500/10 text-sky-300 border-sky-500/30';
                            }
                            ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap text-slate-400 font-mono text-[11px]">
                                    <?= formatarDataHora($log['criado_em']) ?>
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-white"><?= htmlspecialchars($log['coordenador_nome'] ?? 'Sistema') ?></div>
                                    <div class="text-[10px] text-slate-500 font-mono">IP: <?= htmlspecialchars($log['ip'] ?? '—') ?></div>
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap">
                                    <?php if (!empty($log['prova_id'])): ?>
                                        <a href="timer.php?id=<?= $log['prova_id'] ?>" class="text-sky-400 hover:text-sky-300 font-semibold hover:underline">
                                            <?= htmlspecialchars($log['prova_titulo'] ?? ('Prova #' . $log['prova_id'])) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-500">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border <?= $badgeClass ?>">
                                        <?= htmlspecialchars($log['acao']) ?>
                                    </span>
                                </td>

                                <td class="py-3 px-4 max-w-md">
                                    <p class="line-clamp-2 text-slate-200">
                                        <?= htmlspecialchars($log['descricao']) ?>
                                    </p>
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap text-center">
                                    <?php if (!empty($log['dados_anteriores']) || !empty($log['dados_novos'])): ?>
                                        <button type="button" onclick='abrirModalDetalhesLog(<?= json_encode($log, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-800 hover:bg-slate-700 text-sky-300 hover:text-white border border-slate-700 transition-colors">
                                            <i class="fa-solid fa-code-compare mr-1"></i> Diff
                                        </button>
                                    <?php else: ?>
                                        <span class="text-slate-600">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($totalPaginas > 1): ?>
                <div class="px-6 py-4 bg-slate-950/80 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <div>
                        Página <?= $pagina ?> de <?= $totalPaginas ?> (Total: <?= $total ?> eventos)
                    </div>
                    <div class="flex items-center space-x-1">
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <?php
                            $queryParams = $_GET;
                            $queryParams['p'] = $i;
                            $linkPage = 'historico.php?' . http_build_query($queryParams);
                            ?>
                            <a href="<?= $linkPage ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold transition-colors <?= $i === $pagina ? 'bg-sky-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function abrirModalDetalhesLog(log) {
    let anterior = 'Nenhum dado registrado.';
    let novo = 'Nenhum dado registrado.';

    try {
        if (log.dados_anteriores) anterior = JSON.stringify(JSON.parse(log.dados_anteriores), null, 2);
    } catch(e) { anterior = log.dados_anteriores; }

    try {
        if (log.dados_novos) novo = JSON.stringify(JSON.parse(log.dados_novos), null, 2);
    } catch(e) { novo = log.dados_novos; }

    const html = `
        <div class="space-y-4">
            <div>
                <div class="text-xs font-semibold text-slate-400 mb-1">Descrição:</div>
                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 text-slate-200 text-xs">${escapeHtml(log.descricao)}</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div>
                    <div class="font-bold text-amber-400 mb-1"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Estado Anterior:</div>
                    <pre class="p-3 bg-slate-950 rounded-xl border border-slate-800 font-mono text-[11px] text-slate-300 overflow-x-auto max-h-48">${escapeHtml(anterior)}</pre>
                </div>
                <div>
                    <div class="font-bold text-emerald-400 mb-1"><i class="fa-solid fa-arrow-right mr-1"></i> Novo Estado:</div>
                    <pre class="p-3 bg-slate-950 rounded-xl border border-slate-800 font-mono text-[11px] text-slate-300 overflow-x-auto max-h-48">${escapeHtml(novo)}</pre>
                </div>
            </div>

            <div class="text-[11px] text-slate-500 pt-2 border-t border-slate-800 flex justify-between">
                <span>Coordenador: ${escapeHtml(log.coordenador_nome)} (IP: ${escapeHtml(log.ip)})</span>
                <span>Registro #${log.id}</span>
            </div>
        </div>
    `;

    abrirModal({
        titulo: `Auditoria de Alteração • ${escapeHtml(log.acao)}`,
        mensagem: html,
        tipo: 'info',
        textoConfirmar: 'Fechar',
        esconderCancelar: true
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
