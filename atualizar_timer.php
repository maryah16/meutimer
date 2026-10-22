<?php
/**
 * Endpoint AJAX para Sincronização e Ações do Timer de Prova
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/timer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(false, 'Método de requisição inválido.');
}

if (!usuarioLogado()) {
    responderJson(false, 'Acesso não autorizado.');
}

$provaId = isset($_POST['prova_id']) ? (int)$_POST['prova_id'] : 0;
$acao = isset($_POST['acao']) ? limparInput($_POST['acao']) : '';
$segundosRestantes = isset($_POST['segundos_restantes']) ? (int)$_POST['segundos_restantes'] : null;
$detalhe = isset($_POST['detalhe']) ? limparInput($_POST['detalhe']) : null;

if ($provaId <= 0 || empty($acao)) {
    responderJson(false, 'Parâmetros insuficientes.');
}

// Ação de sincronização leve periódica
if ($acao === 'sincronizar') {
    $db = getDB();
    $stmt = $db->prepare("UPDATE provas SET segundos_restantes = :seg WHERE id = :id");
    $stmt->execute([':seg' => $segundosRestantes, ':id' => $provaId]);
    responderJson(true, 'Sincronizado');
}

$resultado = atualizarAcaoTimer($provaId, $acao, $segundosRestantes, $detalhe);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
exit;
