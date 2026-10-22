<?php
/**
 * Módulo de Autenticação do Coordenador
 * Suporte a Login por E-mail OU CPF e Hash Bcrypt
 */

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logs.php';

/**
 * Autentica o coordenador usando E-mail OU CPF + Senha
 */
function autenticarCoordenador(string $login, string $senha): array {
    $login = trim($login);

    if (empty($login) || empty($senha)) {
        return ['sucesso' => false, 'mensagem' => 'Informe seu E-mail ou CPF e sua senha.'];
    }

    $db = getDB();
    $loginNumerico = preg_replace('/[^0-9]/', '', $login);

    // Consulta por e-mail ou por CPF (com ou sem pontuação)
    $stmt = $db->prepare("
        SELECT * FROM coordenadores 
        WHERE email = :login 
           OR cpf = :login 
           OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = :login_num
        LIMIT 1
    ");

    $stmt->execute([
        ':login' => $login,
        ':login_num' => $loginNumerico
    ]);

    $coordenador = $stmt->fetch();

    if (!$coordenador || !password_verify($senha, $coordenador['senha'])) {
        registrarLog('LOGIN_FALHA', "Tentativa de login malsucedida para: $login");
        return ['sucesso' => false, 'mensagem' => 'E-mail/CPF ou senha incorretos.'];
    }

    // Configura sessão do coordenador
    $_SESSION['coordenador_id'] = (int)$coordenador['id'];
    $_SESSION['coordenador_nome'] = $coordenador['nome'];
    $_SESSION['coordenador_email'] = $coordenador['email'];
    $_SESSION['coordenador_cpf'] = $coordenador['cpf'];
    $_SESSION['coordenador_cargo'] = $coordenador['cargo'];

    registrarLog('LOGIN', "Coordenador {$coordenador['nome']} ({$coordenador['email']}) realizou login.");

    return [
        'sucesso' => true,
        'mensagem' => "Bem-vindo(a), Prof. {$coordenador['nome']}!",
        'coordenador' => $coordenador
    ];
}

/**
 * Cadastra um novo coordenador com Bcrypt e envio de e-mail de confirmação
 */
function cadastrarCoordenador(
    string $nome,
    string $email,
    string $cpf,
    string $senha,
    string $cargo = 'Coordenador de Provas'
): array {
    $nome = limparInput($nome);
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    $cpf = trim($cpf);
    $cargo = limparInput($cargo) ?: 'Coordenador de Provas';

    if (empty($nome) || strlen($nome) < 3) {
        return ['sucesso' => false, 'mensagem' => 'O nome deve ter no mínimo 3 caracteres.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'mensagem' => 'Informe um endereço de e-mail válido.'];
    }

    if (!validarCPF($cpf)) {
        return ['sucesso' => false, 'mensagem' => 'O CPF informado é inválido. Verifique os dígitos digitados.'];
    }

    $cpfFormatado = formatarCPF($cpf);

    if (strlen($senha) < 6) {
        return ['sucesso' => false, 'mensagem' => 'A senha deve conter no mínimo 6 caracteres.'];
    }

    $db = getDB();

    // Verifica se e-mail já existe
    $stmtCheckEmail = $db->prepare("SELECT id FROM coordenadores WHERE email = :email LIMIT 1");
    $stmtCheckEmail->execute([':email' => $email]);
    if ($stmtCheckEmail->fetch()) {
        return ['sucesso' => false, 'mensagem' => 'Este e-mail já está cadastrado no sistema.'];
    }

    // Verifica se CPF já existe
    $cpfPuro = preg_replace('/[^0-9]/', '', $cpf);
    $stmtCheckCpf = $db->prepare("
        SELECT id FROM coordenadores 
        WHERE cpf = :cpf_fmt OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf_puro 
        LIMIT 1
    ");
    $stmtCheckCpf->execute([':cpf_fmt' => $cpfFormatado, ':cpf_puro' => $cpfPuro]);
    if ($stmtCheckCpf->fetch()) {
        return ['sucesso' => false, 'mensagem' => 'Este CPF já está cadastrado no sistema.'];
    }

    // Criptografia com algoritmo Bcrypt
    $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);

    $stmtInsert = $db->prepare("
        INSERT INTO coordenadores (nome, email, cpf, senha, cargo, email_confirmado, criado_em)
        VALUES (:nome, :email, :cpf, :senha, :cargo, 1, :criado_em)
    ");

    $sucesso = $stmtInsert->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':cpf' => $cpfFormatado,
        ':senha' => $senhaHash,
        ':cargo' => $cargo,
        ':criado_em' => date('Y-m-d H:i:s')
    ]);

    if ($sucesso) {
        $novoId = (int)$db->lastInsertId();

        // Simulação e envio da mensagem de confirmação por e-mail
        enviarEmailConfirmacao($nome, $email, $cpfFormatado);

        registrarLog(
            'CADASTRO_COORDENADOR',
            "Novo coordenador cadastrado: $nome ($email | CPF: $cpfFormatado). E-mail de confirmação enviado."
        );

        // Autentica automaticamente
        $_SESSION['coordenador_id'] = $novoId;
        $_SESSION['coordenador_nome'] = $nome;
        $_SESSION['coordenador_email'] = $email;
        $_SESSION['coordenador_cpf'] = $cpfFormatado;
        $_SESSION['coordenador_cargo'] = $cargo;

        return [
            'sucesso' => true,
            'mensagem' => 'Cadastro realizado com sucesso! Mensagem de confirmação enviada para seu e-mail.'
        ];
    }

    return ['sucesso' => false, 'mensagem' => 'Falha ao registrar coordenador. Tente novamente.'];
}

/**
 * Envia e registra a mensagem de confirmação de cadastro por e-mail
 */
function enviarEmailConfirmacao(string $nome, string $email, string $cpf): void {
    $assunto = "Confirmação de Registro - Sistema Timer de Provas";
    $mensagemTexto = "Prezado(a) $nome,\n\nSeu cadastro no Sistema Timer de Provas foi concluído com sucesso.\nDados de Acesso:\nLogin: $email ou CPF $cpf\n\nVocê já pode iniciar e configurar seus timers de exame.";

    // Tenta envio nativo com headers padrão se disponível
    $headers = "From: no-reply@etec.sp.gov.br\r\n" .
               "Reply-To: no-reply@etec.sp.gov.br\r\n" .
               "X-Mailer: PHP/" . phpversion();

    @mail($email, $assunto, $mensagemTexto, $headers);

    // Registra na sessão para notificação transparente ao usuário
    $_SESSION['ultimo_email_confirmacao'] = [
        'destinatario' => $email,
        'nome' => $nome,
        'assunto' => $assunto,
        'data_hora' => date('d/m/Y H:i:s')
    ];
}

function encerrarSessao(): void {
    if (usuarioLogado()) {
        registrarLog('LOGOUT', "Coordenador {$_SESSION['coordenador_nome']} encerrou a sessão.");
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
