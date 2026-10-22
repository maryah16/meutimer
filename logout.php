<?php
/**
 * Script de Logout do Coordenador
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/auth.php';

encerrarSessao();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

adicionarToast('info', 'Sua sessão foi encerrada com segurança.');
header('Location: login.php');
exit;
