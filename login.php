<?php
/**
 * Tela de Login do Coordenador (Por E-mail OU CPF)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/auth.php';

if (usuarioLogado()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Login do Coordenador';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $resultado = autenticarCoordenador($login, $senha);

    if ($resultado['sucesso']) {
        adicionarToast('success', $resultado['mensagem']);
        // Redireciona para o timer ou painel
        header('Location: index.php');
        exit;
    } else {
        adicionarToast('error', $resultado['mensagem']);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto my-8 sm:my-16">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-sky-500 via-indigo-500 to-brand-500"></div>

        <div class="text-center mb-6">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-sky-600/20 text-sky-400 border border-sky-500/30 flex items-center justify-center text-xl mb-3 shadow-lg shadow-sky-500/10">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Login do Coordenador</h1>
            <p class="text-xs text-slate-400 mt-1">Informe seu E-mail ou CPF para acessar o timer de provas</p>
        </div>

        <form method="POST" action="login.php" class="space-y-4">
            <div>
                <label for="login" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    E-mail ou CPF
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-sm">
                        <i class="fa-solid fa-id-card"></i>
                    </span>
                    <input type="text" name="login" id="login" required autocomplete="username"
                        value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                        placeholder="seu.email@exemplo.com ou 123.456.789-00"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                </div>
            </div>

            <div>
                <label for="senha" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Senha de Acesso
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-sm">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="senha" id="senha" required autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                </div>
            </div>

            <button type="submit" class="w-full mt-2 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 shadow-lg shadow-sky-500/25 transition-all flex items-center justify-center">
                <i class="fa-solid fa-arrow-right-to-bracket mr-2"></i> Acessar Sistema de Provas
            </button>
        </form>

        <!-- Atalho Rápido para Avaliação -->
        <div class="mt-6 pt-5 border-t border-slate-800/80">
            <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 text-xs text-slate-400">
                <div class="font-semibold text-slate-300 flex items-center justify-between mb-1.5">
                    <span><i class="fa-solid fa-user-check text-sky-400 mr-1.5"></i> Coordenador Padrão (Demonstração):</span>
                    <button type="button" onclick="preencherCoordenador()" class="text-[11px] text-sky-400 hover:text-sky-300 font-bold underline">
                        Usar dados
                    </button>
                </div>
                <div class="space-y-0.5 text-[11px]">
                    <div>Login (E-mail ou CPF): <code class="text-sky-300">coordenador@etec.sp.gov.br</code> ou <code class="text-sky-300">123.456.789-00</code></div>
                    <div>Senha: <code class="text-sky-300">admin123</code></div>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            Ainda não possui cadastro? 
            <a href="registro.php" class="text-sky-400 hover:text-sky-300 font-semibold ml-1">
                Cadastre-se como Coordenador
            </a>
        </div>
    </div>
</div>

<script>
function preencherCoordenador() {
    document.getElementById('login').value = 'coordenador@etec.sp.gov.br';
    document.getElementById('senha').value = 'admin123';
    mostrarToast('info', 'Credenciais do coordenador preenchidas. Clique em Acessar.');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
