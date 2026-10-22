<?php
/**
 * Endpoint de Exclusão de Prova
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/timer.php';

exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$provaId = isset($_POST['prova_id']) ? (int)$_POST['prova_id'] : 0;
$resultado = excluirProva($provaId);

if ($resultado['sucesso']) {
    adicionarToast('success', $resultado['mensagem']);
} else {
    adicionarToast('error', $resultado['mensagem']);
}

header('Location: index.php');
exit;
