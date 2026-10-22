<?php
/**
 * Tela de Cadastro do Coordenador com Hash Bcrypt e Envio de Confirmação por E-mail
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/auth.php';

if (usuarioLogado()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Cadastro do Coordenador';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $cargo = $_POST['cargo'] ?? 'Coordenador de Provas';
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if ($senha !== $confirmarSenha) {
        adicionarToast('error', 'A confirmação de senha não confere com a senha informada.');
    } else {
        $resultado = cadastrarCoordenador($nome, $email, $cpf, $senha, $cargo);
        if ($resultado['sucesso']) {
            adicionarToast('success', $resultado['mensagem']);
            header('Location: configurar_timer.php');
            exit;
        } else {
            adicionarToast('error', $resultado['mensagem']);
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-lg mx-auto my-6 sm:my-12">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500"></div>

        <div class="text-center mb-6">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-xl mb-3 shadow-lg shadow-emerald-500/10">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Cadastro do Coordenador</h1>
            <p class="text-xs text-slate-400 mt-1">Preencha seus dados para gerenciar timers de avaliações e exames</p>
        </div>

        <form method="POST" action="registro.php" class="space-y-4">
            <div>
                <label for="nome" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Nome Completo <span class="text-sky-400">*</span>
                </label>
                <input type="text" name="nome" id="nome" required
                    value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                    placeholder="Ex: Prof. Carlos Eduardo Souza"
                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        E-mail Institucional <span class="text-sky-400">*</span>
                    </label>
                    <input type="email" name="email" id="email" required autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="coordenador@etec.sp.gov.br"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                </div>

                <div>
                    <label for="cpf" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        CPF <span class="text-sky-400">*</span>
                    </label>
                    <input type="text" name="cpf" id="cpf" required maxlength="14"
                        value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>"
                        placeholder="000.000.000-00"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                </div>
            </div>

            <div>
                <label for="cargo" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Cargo / Função
                </label>
                <input type="text" name="cargo" id="cargo"
                    value="<?= htmlspecialchars($_POST['cargo'] ?? 'Coordenador de Provas') ?>"
                    placeholder="Ex: Coordenador Pedagógico / Fiscal Geral"
                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="senha" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Senha de Acesso <span class="text-sky-400">*</span>
                    </label>
                    <input type="password" name="senha" id="senha" required minlength="6"
                        placeholder="Mínimo 6 chars"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                </div>

                <div>
                    <label for="confirmar_senha" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Confirmar Senha <span class="text-sky-400">*</span>
                    </label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="6"
                        placeholder="Repita a senha"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                </div>
            </div>

            <div class="p-3 bg-slate-950/70 border border-slate-800 rounded-xl space-y-1 text-[11px] text-slate-400">
                <div class="flex items-center text-slate-300 font-semibold">
                    <i class="fa-solid fa-envelope-circle-check text-sky-400 mr-1.5"></i> Confirmação por E-mail
                </div>
                <p>Após salvar os dados, o sistema confirmará seu registro e registrará o envio da mensagem de confirmação para o endereço fornecido.</p>
                <div class="flex items-center text-emerald-400 pt-1">
                    <i class="fa-solid fa-lock mr-1.5"></i> Senha protegida com criptografia Bcrypt.
                </div>
            </div>

            <button type="submit" class="w-full mt-2 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-sky-600 hover:from-emerald-500 hover:to-sky-500 shadow-lg shadow-emerald-500/25 transition-all flex items-center justify-center">
                <i class="fa-solid fa-user-check mr-2"></i> Concluir Registro do Coordenador
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
            Já possui cadastro? 
            <a href="login.php" class="text-sky-400 hover:text-sky-300 font-semibold ml-1">
                Fazer Login
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
