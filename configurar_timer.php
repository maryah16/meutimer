<?php
/**
 * Tela de Configuração do Timer de Provas
 * Permite definir tempo total, alertas, intervalo entre alertas, sons e avisos
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/timer.php';

exigirLogin();

$pageTitle = 'Configurar Timer de Prova';
$provaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$prova = $provaId > 0 ? obterProva($provaId) : null;

// Se for edição, calcula horas e minutos
$horasPadrao = 4;
$minutosPadrao = 0;
$segundosPadrao = 0;
$intervaloPadraoMinutos = 30;

if ($prova) {
    $totalSeg = (int)$prova['tempo_total_segundos'];
    $horasPadrao = floor($totalSeg / 3600);
    $minutosPadrao = floor(($totalSeg % 3600) / 60);
    $segundosPadrao = $totalSeg % 60;
    $intervaloPadraoMinutos = round(((int)$prova['tempo_alerta_intervalo']) / 60);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = salvarConfiguracaoProva($_POST);

    if ($resultado['sucesso']) {
        adicionarToast('success', $resultado['mensagem']);
        header('Location: timer.php?id=' . $resultado['prova_id']);
        exit;
    } else {
        adicionarToast('error', $resultado['mensagem']);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto my-6">
    <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-xs text-sky-400 font-bold uppercase tracking-wider mb-1">
                <i class="fa-solid fa-sliders"></i>
                <span>Parâmetros de Avaliação</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                <?= $prova ? 'Editar Timer da Prova' : 'Configurar Novo Timer de Prova' ?>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Defina o tempo total, intervalo de alertas e notificações sonoras e visuais
            </p>
        </div>
        <a href="index.php" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Voltar
        </a>
    </div>

    <form method="POST" action="configurar_timer.php<?= $provaId ? '?id=' . $provaId : '' ?>" id="form-timer" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-8">
        <?php if ($provaId): ?>
            <input type="hidden" name="prova_id" value="<?= $provaId ?>">
        <?php endif; ?>

        <!-- Seção 1: Identificação da Prova e Sala -->
        <div>
            <div class="flex items-center space-x-3 mb-4 pb-3 border-b border-slate-800">
                <div class="w-8 h-8 rounded-lg bg-sky-600/20 text-sky-400 flex items-center justify-center font-bold text-sm">
                    1
                </div>
                <div>
                    <h2 class="text-base font-bold text-white">Identificação da Prova</h2>
                    <p class="text-xs text-slate-400">Nome da avaliação, turma e orientações aos alunos</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="titulo" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Título da Prova / Avaliação <span class="text-sky-400">*</span>
                    </label>
                    <input type="text" name="titulo" id="titulo" required
                        value="<?= htmlspecialchars($_POST['titulo'] ?? ($prova['titulo'] ?? 'Vestibulinho ETEC 2026 - 1º Módulo')) ?>"
                        placeholder="Ex: Vestibulinho 2026 - Exame Geral"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                </div>

                <div>
                    <label for="sala" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Sala / Bloco / Turma
                    </label>
                    <input type="text" name="sala" id="sala"
                        value="<?= htmlspecialchars($_POST['sala'] ?? ($prova['sala'] ?? 'Sala 102 - Bloco A')) ?>"
                        placeholder="Ex: Sala 04 - Bloco B"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                </div>
            </div>

            <div class="mt-4">
                <label for="instrucoes" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Instruções para o Telão da Sala de Aula (Opcional)
                </label>
                <textarea name="instrucoes" id="instrucoes" rows="2"
                    placeholder="Ex: Celulares desligados na mochila. Caneta preta ou azul. Saída permitida após 1 hora de prova."
                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors"><?= htmlspecialchars($_POST['instrucoes'] ?? ($prova['instrucoes'] ?? "• Proibido o uso de calculadoras e celulares.\n• Preencham a folha de respostas com caneta azul ou preta.\n• Saída permitida somente após 1 hora do início.")) ?></textarea>
            </div>
        </div>

        <!-- Seção 2: Definição do Tempo Total -->
        <div>
            <div class="flex items-center space-x-3 mb-4 pb-3 border-b border-slate-800">
                <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm">
                    2
                </div>
                <div>
                    <h2 class="text-base font-bold text-white">Duração Total da Prova</h2>
                    <p class="text-xs text-slate-400">Configure a quantidade de horas, minutos e segundos da prova</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 max-w-md">
                <div>
                    <label for="horas" class="block text-xs font-semibold text-slate-300 mb-1 text-center">Horas</label>
                    <input type="number" name="horas" id="horas" min="0" max="23"
                        value="<?= htmlspecialchars($_POST['horas'] ?? $horasPadrao) ?>"
                        class="w-full text-center font-timer text-2xl font-bold py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-sky-500">
                </div>
                <div>
                    <label for="minutos" class="block text-xs font-semibold text-slate-300 mb-1 text-center">Minutos</label>
                    <input type="number" name="minutos" id="minutos" min="0" max="59"
                        value="<?= htmlspecialchars($_POST['minutos'] ?? $minutosPadrao) ?>"
                        class="w-full text-center font-timer text-2xl font-bold py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-sky-500">
                </div>
                <div>
                    <label for="segundos" class="block text-xs font-semibold text-slate-300 mb-1 text-center">Segundos</label>
                    <input type="number" name="segundos" id="segundos" min="0" max="59"
                        value="<?= htmlspecialchars($_POST['segundos'] ?? $segundosPadrao) ?>"
                        class="w-full text-center font-timer text-2xl font-bold py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-sky-500">
                </div>
            </div>

            <!-- Botões de Predefinição de Tempo (Presets Oficiais) -->
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="text-[11px] text-slate-400 mr-1">Predefinições rápidas:</span>
                <button type="button" onclick="definirTempo(4, 0, 0)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-sky-600/30 text-slate-300 hover:text-sky-300 border border-slate-700 transition-colors">
                    <i class="fa-solid fa-graduation-cap text-sky-400 mr-1"></i> 4 Horas (Vestibulinho/ENEM)
                </button>
                <button type="button" onclick="definirTempo(3, 0, 0)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-sky-600/30 text-slate-300 hover:text-sky-300 border border-slate-700 transition-colors">
                    3 Horas (Vestibular)
                </button>
                <button type="button" onclick="definirTempo(2, 0, 0)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-sky-600/30 text-slate-300 hover:text-sky-300 border border-slate-700 transition-colors">
                    2 Horas (Bimestral)
                </button>
                <button type="button" onclick="definirTempo(1, 0, 0)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-sky-600/30 text-slate-300 hover:text-sky-300 border border-slate-700 transition-colors">
                    1 Hora (Simulado)
                </button>
                <button type="button" onclick="definirTempo(0, 30, 0)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-sky-600/30 text-slate-300 hover:text-sky-300 border border-slate-700 transition-colors">
                    30 Min (Redação)
                </button>
            </div>
        </div>

        <!-- Seção 3: Configuração de Alertas (Som, Barulho ou Aviso) -->
        <div>
            <div class="flex items-center space-x-3 mb-4 pb-3 border-b border-slate-800">
                <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm">
                    3
                </div>
                <div>
                    <h2 class="text-base font-bold text-white">Alertas e Avisos Configuráveis</h2>
                    <p class="text-xs text-slate-400">Defina se emitirá som/barulho, avisos na tela e intervalo entre alertas</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Intervalo entre alertas -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                    <div>
                        <label for="intervalo_alerta_minutos" class="block text-xs font-bold text-slate-200 mb-1">
                            <i class="fa-solid fa-repeat text-indigo-400 mr-1.5"></i> Intervalo Entre Cada Alerta
                        </label>
                        <p class="text-[11px] text-slate-400 mb-3">
                            O sistema emitirá avisos periódicos aos alunos a cada intervalo escolhido.
                        </p>
                    </div>
                    <select name="intervalo_alerta_minutos" id="intervalo_alerta_minutos" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-sky-500">
                        <option value="60" <?= $intervaloPadraoMinutos == 60 ? 'selected' : '' ?>>A cada 1 Hora (60 minutos)</option>
                        <option value="30" <?= $intervaloPadraoMinutos == 30 ? 'selected' : '' ?>>A cada 30 Minutos (Padrão Vestibulinho)</option>
                        <option value="15" <?= $intervaloPadraoMinutos == 15 ? 'selected' : '' ?>>A cada 15 Minutos</option>
                        <option value="10" <?= $intervaloPadraoMinutos == 10 ? 'selected' : '' ?>>A cada 10 Minutos</option>
                        <option value="0"  <?= $intervaloPadraoMinutos == 0 ? 'selected' : '' ?>>Apenas nos minutos finais (30m, 15m, 5m, 1m)</option>
                    </select>
                </div>

                <!-- Emissão de Som / Barulho -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-white block">
                                <i class="fa-solid fa-volume-high text-sky-400 mr-1.5"></i> Emitir Som / Barulho
                            </span>
                            <span class="text-[11px] text-slate-400">Alerta sonoro ao atingir cada tempo marcado</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="alerta_som" value="1" <?= (!$prova || $prova['alerta_som']) ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-500"></div>
                        </label>
                    </div>

                    <!-- Tipo de Som -->
                    <div class="pt-2 border-t border-slate-800">
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1.5">Tipo de Toque Sonoro:</label>
                        <div class="grid grid-cols-3 gap-2">
                            <?php $somSel = $prova['tipo_som'] ?? 'beep'; ?>
                            <label class="flex items-center justify-center p-2 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer text-xs font-medium text-slate-300 hover:border-sky-500">
                                <input type="radio" name="tipo_som" value="beep" <?= $somSel === 'beep' ? 'checked' : '' ?> class="mr-1.5 text-sky-500">
                                Beep Duplo
                            </label>
                            <label class="flex items-center justify-center p-2 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer text-xs font-medium text-slate-300 hover:border-sky-500">
                                <input type="radio" name="tipo_som" value="sino" <?= $somSel === 'sino' ? 'checked' : '' ?> class="mr-1.5 text-sky-500">
                                Sino
                            </label>
                            <label class="flex items-center justify-center p-2 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer text-xs font-medium text-slate-300 hover:border-sky-500">
                                <input type="radio" name="tipo_som" value="alarme" <?= $somSel === 'alarme' ? 'checked' : '' ?> class="mr-1.5 text-sky-500">
                                Alarme
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Aviso Visual na Tela -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-white block">
                            <i class="fa-solid fa-display text-amber-400 mr-1.5"></i> Aviso Visual em Tela (Banner da Sala)
                        </span>
                        <span class="text-[11px] text-slate-400">Exibe caixa de mensagem visual na tela com o tempo restante</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="alerta_aviso_tela" value="1" <?= (!$prova || $prova['alerta_aviso_tela']) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                </div>

                <!-- Aviso por Voz Sintetizada -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-white block">
                            <i class="fa-solid fa-comment-dots text-emerald-400 mr-1.5"></i> Leitura por Voz Sintetizada (Opcional)
                        </span>
                        <span class="text-[11px] text-slate-400">Lê o aviso em português para a sala ("Atenção: restam 15 minutos")</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="alerta_voz" value="1" <?= ($prova && $prova['alerta_voz']) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Botões de Ação Final -->
        <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-3">
            <a href="index.php" class="w-full sm:w-auto px-5 py-3 rounded-xl text-center text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                Cancelar
            </a>
            <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-sky-600 via-indigo-600 to-sky-600 hover:from-sky-500 hover:to-indigo-500 shadow-xl shadow-sky-500/25 transition-all flex items-center justify-center">
                <i class="fa-solid fa-check mr-2"></i> Salvar e Abrir Timer da Prova
            </button>
        </div>
    </form>
</div>

<script>
function definirTempo(h, m, s) {
    document.getElementById('horas').value = h;
    document.getElementById('minutos').value = m;
    document.getElementById('segundos').value = s;
    mostrarToast('info', `Duração configurada para ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}.`);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
