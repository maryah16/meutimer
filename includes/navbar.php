<?php
/**
 * Barra de Navegação Responsiva - Timer de Provas
 */
require_once __DIR__ . '/../backend/helpers.php';
$usuario = getUsuarioAtual();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-slate-900/90 backdrop-blur-md border-b border-slate-800 sticky top-0 z-40 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo & Marca -->
            <div class="flex items-center space-x-3">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 via-indigo-600 to-brand-500 flex items-center justify-center text-white shadow-lg shadow-sky-500/25 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-stopwatch text-lg"></i>
                    </div>
                    <div>
                        <span class="text-lg font-black font-['Inter'] tracking-tight bg-gradient-to-r from-white via-slate-200 to-sky-400 bg-clip-text text-transparent">
                            Timer<span class="text-sky-400">Provas</span>
                        </span>
                        <span class="hidden sm:inline-block text-[10px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded bg-sky-950/80 text-sky-400 border border-sky-800/50 ml-1.5">
                            Coordenador
                        </span>
                    </div>
                </a>
            </div>

            <!-- Links Desktop -->
            <div class="hidden md:flex items-center space-x-1 lg:space-x-2">
                <a href="index.php" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'index.php' ? 'text-white bg-slate-800 border border-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                    <i class="fa-solid fa-clock mr-1.5 text-xs text-sky-400"></i> Painel de Timers
                </a>
                <a href="configurar_timer.php" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'configurar_timer.php' ? 'text-white bg-sky-600 shadow-md shadow-sky-500/20' : 'text-sky-400 hover:text-sky-300 hover:bg-sky-950/40' ?>">
                    <i class="fa-solid fa-plus-circle mr-1.5 text-xs"></i> Configurar Novo Timer
                </a>
                <a href="historico.php" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'historico.php' ? 'text-white bg-slate-800 border border-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                    <i class="fa-solid fa-shield-halved mr-1.5 text-xs text-emerald-400"></i> Histórico & Auditoria
                </a>
            </div>

            <!-- Ações do Coordenador (Desktop) -->
            <div class="hidden md:flex items-center space-x-3">
                <?php if ($usuario): ?>
                    <div class="flex items-center space-x-3 bg-slate-800/60 border border-slate-700/60 rounded-xl px-3 py-1.5">
                        <div class="w-8 h-8 rounded-full bg-sky-600/30 border border-sky-500/30 flex items-center justify-center text-sky-300 text-sm font-bold">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <div class="text-left text-xs">
                            <div class="font-semibold text-slate-200"><?= htmlspecialchars($usuario['nome']) ?></div>
                            <div class="text-[10px] text-slate-400 flex items-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1"></span>
                                <?= htmlspecialchars($usuario['cargo']) ?>
                            </div>
                        </div>
                        <a href="logout.php" title="Encerrar Sessão" class="text-slate-400 hover:text-rose-400 p-1.5 rounded-lg hover:bg-rose-500/10 transition-colors ml-1">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-medium text-slate-300 hover:text-white px-3 py-2 rounded-lg hover:bg-slate-800 transition-colors">
                        <i class="fa-solid fa-arrow-right-to-bracket mr-1"></i> Entrar
                    </a>
                    <a href="registro.php" class="text-sm font-medium text-white bg-sky-600 hover:bg-sky-500 px-4 py-2 rounded-lg shadow-md shadow-sky-600/25 transition-all">
                        <i class="fa-solid fa-user-plus mr-1"></i> Cadastrar Coordenador
                    </a>
                <?php endif; ?>
            </div>

            <!-- Botão Menu Mobile -->
            <div class="flex md:hidden items-center space-x-2">
                <button type="button" id="mobile-menu-btn" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none" aria-label="Abrir Menu">
                    <i class="fa-solid fa-bars text-xl" id="menu-icon-bars"></i>
                    <i class="fa-solid fa-xmark text-xl hidden" id="menu-icon-close"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- Dropdown Mobile -->
    <div class="hidden md:hidden border-t border-slate-800 bg-slate-900 px-4 pt-3 pb-5 space-y-2" id="mobile-menu">
        <a href="index.php" class="flex items-center px-3 py-2.5 rounded-lg text-base font-medium <?= $currentPage === 'index.php' ? 'text-white bg-slate-800' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-clock w-6 text-sky-400"></i> Painel de Timers
        </a>
        <a href="configurar_timer.php" class="flex items-center px-3 py-2.5 rounded-lg text-base font-medium text-sky-400 bg-sky-950/40 border border-sky-800/40">
            <i class="fa-solid fa-plus-circle w-6"></i> Configurar Novo Timer
        </a>
        <a href="historico.php" class="flex items-center px-3 py-2.5 rounded-lg text-base font-medium <?= $currentPage === 'historico.php' ? 'text-white bg-slate-800' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-shield-halved w-6 text-emerald-400"></i> Histórico & Auditoria
        </a>

        <div class="border-t border-slate-800 pt-3 mt-3">
            <?php if ($usuario): ?>
                <div class="flex items-center justify-between px-3 py-2 bg-slate-800/50 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-full bg-sky-600/30 flex items-center justify-center text-sky-300 font-semibold text-sm">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-white"><?= htmlspecialchars($usuario['nome']) ?></div>
                            <div class="text-xs text-slate-400"><?= htmlspecialchars($usuario['cargo']) ?></div>
                        </div>
                    </div>
                    <a href="logout.php" class="text-rose-400 hover:text-rose-300 text-sm font-semibold px-2 py-1">
                        Sair
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <a href="login.php" class="text-center py-2 px-3 rounded-lg text-sm font-medium bg-slate-800 text-slate-200">
                        Entrar
                    </a>
                    <a href="registro.php" class="text-center py-2 px-3 rounded-lg text-sm font-medium bg-sky-600 text-white">
                        Cadastrar
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
