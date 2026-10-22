<?php
/**
 * Script de Demonstração / Seed - Timer de Provas
 * Popula exames e logs de auditoria realistas
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/timer.php';
require_once __DIR__ . '/backend/logs.php';

$_SESSION['coordenador_id'] = 1;
$_SESSION['coordenador_nome'] = 'Prof. Coordenador Geral';
$_SESSION['coordenador_email'] = 'coordenador@etec.sp.gov.br';
$_SESSION['coordenador_cpf'] = '123.456.789-00';
$_SESSION['coordenador_cargo'] = 'Coordenador de Exames';

echo "Inicializando banco e gerando dados de demonstração...\n";

// Prova 1: Vestibulinho ETEC 2026 (4 horas)
$res1 = salvarConfiguracaoProva([
    'titulo' => 'Vestibulinho ETEC 2026 - 1º Módulo Técnico',
    'sala' => 'Auditório Central - Bloco C',
    'horas' => 4,
    'minutos' => 0,
    'segundos' => 0,
    'intervalo_alerta_minutos' => 30,
    'alerta_som' => 1,
    'tipo_som' => 'sino',
    'alerta_aviso_tela' => 1,
    'alerta_voz' => 1,
    'instrucoes' => "• Tempo total de prova: 4 horas (50 questões de múltipla escolha).\n• Saída permitida apenas após 1 hora do início oficial.\n• Preencham o cartão de respostas com caneta esferográfica azul ou preta.\n• Proibido uso de celulares, relógios inteligentes e bonés."
]);

if ($res1['sucesso']) {
    $p1Id = $res1['prova_id'];
    echo "Prova 1 criada! ID: $p1Id\n";

    // Inicia a prova e simula 2 horas e 15 minutos decorridos
    atualizarAcaoTimer($p1Id, 'iniciar', 14400);
    // Simula tempo restante de 6300 segundos (1h 45min restantes)
    $db = getDB();
    $db->exec("UPDATE provas SET segundos_restantes = 6300 WHERE id = $p1Id");

    // Registra alguns alertas no log
    registrarLog('DISPARO_ALERTA', 'Alerta disparado na prova "Vestibulinho ETEC 2026": Atenção: Restam 2 horas e 30 minutos de prova.', $p1Id);
    registrarLog('DISPARO_ALERTA', 'Alerta disparado na prova "Vestibulinho ETEC 2026": Atenção: Restam 2 horas para o término.', $p1Id);
}

// Prova 2: Avaliação de Recuperação (1 hora)
$res2 = salvarConfiguracaoProva([
    'titulo' => 'Avaliação Especial de Matemática e Algoritmos',
    'sala' => 'Laboratório 02 - Informática',
    'horas' => 1,
    'minutos' => 0,
    'segundos' => 0,
    'intervalo_alerta_minutos' => 15,
    'alerta_som' => 1,
    'tipo_som' => 'beep',
    'alerta_aviso_tela' => 1,
    'alerta_voz' => 0,
    'instrucoes' => "• Prova individual sem consulta a materiais externos.\n• Celulares mantidos desligados nas mochilas."
]);

if ($res2['sucesso']) {
    echo "Prova 2 criada! ID: {$res2['prova_id']}\n";
}

echo "Seed concluído com sucesso!\n";
