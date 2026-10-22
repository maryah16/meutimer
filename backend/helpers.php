<?php
/**
 * Funções Auxiliares e Utilitários Gerais - Timer de Provas
 */

require_once __DIR__ . '/../config/config.php';

function adicionarToast(string $tipo, string $mensagem, string $titulo = ''): void {
    if (!isset($_SESSION['toasts'])) {
        $_SESSION['toasts'] = [];
    }
    $_SESSION['toasts'][] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem,
        'titulo' => $titulo ?: ($tipo === 'success' ? 'Sucesso' : ($tipo === 'error' ? 'Erro' : 'Aviso')),
        'id' => uniqid('toast_')
    ];
}

function obterToasts(): array {
    $toasts = $_SESSION['toasts'] ?? [];
    $_SESSION['toasts'] = [];
    return $toasts;
}

function limparInput($data): string {
    if (is_array($data)) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validação rigorosa de CPF brasileiro (11 dígitos com cálculo verificador)
 */
function validarCPF(string $cpf): bool {
    // Remove pontuações
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) !== 11) {
        return false;
    }

    // Bloqueia CPFs com dígitos repetidos inválidos (ex: 111.111.111-11)
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // Primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += ((int)$cpf[$i]) * (10 - $i);
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : 11 - $resto;
    if ($dv1 !== (int)$cpf[9]) {
        return false;
    }

    // Segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += ((int)$cpf[$i]) * (11 - $i);
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : 11 - $resto;
    if ($dv2 !== (int)$cpf[10]) {
        return false;
    }

    return true;
}

/**
 * Formata CPF para o padrão 000.000.000-00
 */
function formatarCPF(string $cpf): string {
    $nums = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($nums) !== 11) return $cpf;
    return vsprintf('%s%s%s.%s%s%s.%s%s%s-%s%s', str_split($nums));
}

/**
 * Formata segundos em HH:MM:SS
 */
function formatarSegundos(int $segundos): string {
    if ($segundos < 0) $segundos = 0;
    $horas = floor($segundos / 3600);
    $minutos = floor(($segundos % 3600) / 60);
    $segs = $segundos % 60;
    return sprintf('%02d:%02d:%02d', $horas, $minutos, $segs);
}

function getUsuarioAtual(): ?array {
    if (!empty($_SESSION['coordenador_id'])) {
        return [
            'id' => $_SESSION['coordenador_id'],
            'nome' => $_SESSION['coordenador_nome'] ?? 'Coordenador',
            'email' => $_SESSION['coordenador_email'] ?? '',
            'cpf' => $_SESSION['coordenador_cpf'] ?? '',
            'cargo' => $_SESSION['coordenador_cargo'] ?? 'Coordenador'
        ];
    }
    return null;
}

function usuarioLogado(): bool {
    return !empty($_SESSION['coordenador_id']);
}

function exigirLogin(): void {
    if (!usuarioLogado()) {
        adicionarToast('warning', 'Faça login para acessar o painel do timer.');
        header('Location: login.php');
        exit;
    }
}

function responderJson(bool $sucesso, string $mensagem, array $dados = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
        'dados' => $dados
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function formatarDataHora(?string $data): string {
    if (!$data) return '-';
    $timestamp = strtotime($data);
    if (!$timestamp) return $data;
    return date('d/m/Y H:i:s', $timestamp);
}

function formatarDataSimples(?string $data): string {
    if (!$data) return '-';
    $timestamp = strtotime($data);
    if (!$timestamp) return $data;
    return date('d/m/Y', $timestamp);
}
