<?php
/**
 * Módulo de Gerenciamento do Timer e Provas
 * Configurações de tempo, alertas (som, aviso, voz) e controle da contagem
 */

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logs.php';

/**
 * Cria ou salva a configuração de uma prova e seu timer
 */
function salvarConfiguracaoProva(array $dados): array {
    if (!usuarioLogado()) {
        return ['sucesso' => false, 'mensagem' => 'Acesso negado. Faça login como coordenador.'];
    }

    $titulo = limparInput($dados['titulo'] ?? '');
    $sala = limparInput($dados['sala'] ?? '');
    $instrucoes = limparInput($dados['instrucoes'] ?? '');

    $horas = max(0, (int)($dados['horas'] ?? 0));
    $minutos = max(0, (int)($dados['minutos'] ?? 0));
    $segundos = max(0, (int)($dados['segundos'] ?? 0));

    $tempoTotalSegundos = ($horas * 3600) + ($minutos * 60) + $segundos;

    if (empty($titulo)) {
        return ['sucesso' => false, 'mensagem' => 'Informe o título ou identificação da prova.'];
    }

    if ($tempoTotalSegundos < 60) {
        return ['sucesso' => false, 'mensagem' => 'O tempo total da prova deve ser de no mínimo 1 minuto (60 segundos).'];
    }

    // Configurações de Alerta
    $intervaloMinutos = max(0, (int)($dados['intervalo_alerta_minutos'] ?? 30));
    $tempoAlertaIntervalo = $intervaloMinutos * 60;

    $alertaSom = !empty($dados['alerta_som']) ? 1 : 0;
    $tipoSom = in_array($dados['tipo_som'] ?? '', ['beep', 'sino', 'alarme']) ? $dados['tipo_som'] : 'beep';
    $alertaAvisoTela = !empty($dados['alerta_aviso_tela']) ? 1 : 0;
    $alertaVoz = !empty($dados['alerta_voz']) ? 1 : 0;

    $db = getDB();
    $usuario = getUsuarioAtual();
    $provaId = !empty($dados['prova_id']) ? (int)$dados['prova_id'] : 0;

    try {
        if ($provaId > 0) {
            // Atualização
            $stmt = $db->prepare("
                UPDATE provas 
                SET titulo = :titulo,
                    sala = :sala,
                    tempo_total_segundos = :tempo,
                    tempo_alerta_intervalo = :intervalo,
                    alerta_som = :som,
                    tipo_som = :tipo_som,
                    alerta_aviso_tela = :aviso,
                    alerta_voz = :voz,
                    instrucoes = :instrucoes,
                    segundos_restantes = :seg_rest
                WHERE id = :id AND coordenador_id = :cid
            ");

            $stmt->execute([
                ':titulo' => $titulo,
                ':sala' => $sala,
                ':tempo' => $tempoTotalSegundos,
                ':intervalo' => $tempoAlertaIntervalo,
                ':som' => $alertaSom,
                ':tipo_som' => $tipoSom,
                ':aviso' => $alertaAvisoTela,
                ':voz' => $alertaVoz,
                ':instrucoes' => $instrucoes,
                ':seg_rest' => $tempoTotalSegundos,
                ':id' => $provaId,
                ':cid' => $usuario['id']
            ]);

            registrarLog(
                'CONFIGURAR_TIMER',
                "Timer da prova \"$titulo\" atualizado: " . formatarSegundos($tempoTotalSegundos) . " com alertas a cada $intervaloMinutos min.",
                $provaId,
                null,
                ['tempo_total' => $tempoTotalSegundos, 'alerta_som' => $alertaSom, 'alerta_aviso' => $alertaAvisoTela]
            );

            return [
                'sucesso' => true,
                'mensagem' => 'Configurações do timer salvas com sucesso!',
                'prova_id' => $provaId
            ];
        } else {
            // Novo Registro
            $stmt = $db->prepare("
                INSERT INTO provas (coordenador_id, titulo, sala, tempo_total_segundos, tempo_alerta_intervalo, alerta_som, tipo_som, alerta_aviso_tela, alerta_voz, instrucoes, status, segundos_restantes, criado_em)
                VALUES (:cid, :titulo, :sala, :tempo, :intervalo, :som, :tipo_som, :aviso, :voz, :instrucoes, 'configurada', :seg_rest, :criado_em)
            ");

            $stmt->execute([
                ':cid' => $usuario['id'],
                ':titulo' => $titulo,
                ':sala' => $sala,
                ':tempo' => $tempoTotalSegundos,
                ':intervalo' => $tempoAlertaIntervalo,
                ':som' => $alertaSom,
                ':tipo_som' => $tipoSom,
                ':aviso' => $alertaAvisoTela,
                ':voz' => $alertaVoz,
                ':instrucoes' => $instrucoes,
                ':seg_rest' => $tempoTotalSegundos,
                ':criado_em' => date('Y-m-d H:i:s')
            ]);

            $novoId = (int)$db->lastInsertId();

            registrarLog(
                'CRIAR_TIMER',
                "Nova prova \"$titulo\" cadastrada com timer de " . formatarSegundos($tempoTotalSegundos) . " (Alertas a cada $intervaloMinutos min, Som: " . ($alertaSom ? 'Sim' : 'Não') . ", Aviso: " . ($alertaAvisoTela ? 'Sim' : 'Não') . ").",
                $novoId
            );

            return [
                'sucesso' => true,
                'mensagem' => 'Prova e Timer configurados com sucesso! Pronto para iniciar.',
                'prova_id' => $novoId
            ];
        }
    } catch (Exception $e) {
        error_log("Erro ao salvar timer: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro interno ao salvar configurações: ' . $e->getMessage()];
    }
}

/**
 * Consulta detalhes da prova
 */
function obterProva(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, c.nome as coordenador_nome, c.email as coordenador_email
        FROM provas p
        JOIN coordenadores c ON p.coordenador_id = c.id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $prova = $stmt->fetch();
    return $prova ?: null;
}

/**
 * Lista provas do coordenador ou de todo o sistema
 */
function listarProvas(?int $coordenadorId = null): array {
    $db = getDB();
    if ($coordenadorId) {
        $stmt = $db->prepare("
            SELECT p.*, c.nome as coordenador_nome 
            FROM provas p
            JOIN coordenadores c ON p.coordenador_id = c.id
            WHERE p.coordenador_id = :cid
            ORDER BY p.id DESC
        ");
        $stmt->execute([':cid' => $coordenadorId]);
    } else {
        $stmt = $db->query("
            SELECT p.*, c.nome as coordenador_nome 
            FROM provas p
            JOIN coordenadores c ON p.coordenador_id = c.id
            ORDER BY p.id DESC
        ");
    }
    return $stmt->fetchAll();
}

/**
 * Atualiza o status e ações de controle do timer (iniciar, pausar, prorrogação, etc.)
 */
function atualizarAcaoTimer(int $provaId, string $acao, ?int $segundosRestantes = null, ?string $detalhe = null): array {
    if (!usuarioLogado()) {
        return ['sucesso' => false, 'mensagem' => 'Acesso negado.'];
    }

    $db = getDB();
    $prova = obterProva($provaId);

    if (!$prova) {
        return ['sucesso' => false, 'mensagem' => 'Prova não encontrada.'];
    }

    $usuario = getUsuarioAtual();

    switch ($acao) {
        case 'iniciar':
            $stmt = $db->prepare("
                UPDATE provas 
                SET status = 'em_andamento', 
                    iniciado_em = COALESCE(iniciado_em, :agora),
                    segundos_restantes = :seg
                WHERE id = :id
            ");
            $stmt->execute([
                ':agora' => date('Y-m-d H:i:s'),
                ':seg' => $segundosRestantes ?? $prova['tempo_total_segundos'],
                ':id' => $provaId
            ]);

            registrarLog('INICIAR_PROVA', "Contagem iniciada para a prova \"{$prova['titulo']}\". Tempo inicial: " . formatarSegundos($prova['tempo_total_segundos']), $provaId);
            return ['sucesso' => true, 'mensagem' => 'Contagem regressiva iniciada!'];

        case 'pausar':
            $stmt = $db->prepare("UPDATE provas SET status = 'pausada', segundos_restantes = :seg WHERE id = :id");
            $stmt->execute([':seg' => $segundosRestantes, ':id' => $provaId]);

            $motivoTxt = $detalhe ? " Motivo: $detalhe" : "";
            registrarLog('PAUSAR_PROVA', "Contagem pausada na prova \"{$prova['titulo']}\". Tempo restante: " . formatarSegundos((int)$segundosRestantes) . ".$motivoTxt", $provaId);
            return ['sucesso' => true, 'mensagem' => 'Timer pausado.'];

        case 'retomar':
            $stmt = $db->prepare("UPDATE provas SET status = 'em_andamento', segundos_restantes = :seg WHERE id = :id");
            $stmt->execute([':seg' => $segundosRestantes, ':id' => $provaId]);

            registrarLog('RETOMAR_PROVA', "Contagem retomada na prova \"{$prova['titulo']}\". Tempo restante: " . formatarSegundos((int)$segundosRestantes), $provaId);
            return ['sucesso' => true, 'mensagem' => 'Timer retomado.'];

        case 'acrescimo':
            $minutosAdd = max(1, (int)$detalhe);
            $segundosAdd = $minutosAdd * 60;
            $novoTotalRestante = ((int)$segundosRestantes) + $segundosAdd;

            $stmt = $db->prepare("UPDATE provas SET segundos_restantes = :seg WHERE id = :id");
            $stmt->execute([':seg' => $novoTotalRestante, ':id' => $provaId]);

            registrarLog(
                'ACRESCIMO_TEMPO',
                "Acréscimo de $minutosAdd minuto(s) concedido para a prova \"{$prova['titulo']}\". Novo tempo restante: " . formatarSegundos($novoTotalRestante),
                $provaId,
                ['tempo_anterior' => $segundosRestantes],
                ['tempo_novo' => $novoTotalRestante]
            );
            return [
                'sucesso' => true, 
                'mensagem' => "+$minutosAdd minutos adicionados à contagem!",
                'novo_tempo_restante' => $novoTotalRestante
            ];

        case 'alerta_disparado':
            $msgAlerta = $detalhe ?: "Alerta acionado no tempo restante de " . formatarSegundos((int)$segundosRestantes);
            registrarLog('DISPARO_ALERTA', "Alerta disparado na prova \"{$prova['titulo']}\": $msgAlerta", $provaId);
            return ['sucesso' => true, 'mensagem' => 'Alerta registrado.'];

        case 'concluir':
            $stmt = $db->prepare("UPDATE provas SET status = 'concluida', segundos_restantes = 0, concluido_em = :agora WHERE id = :id");
            $stmt->execute([':agora' => date('Y-m-d H:i:s'), ':id' => $provaId]);

            registrarLog('CONCLUSAO_PROVA', "Prova \"{$prova['titulo']}\" oficialmente encerrada pelo coordenador {$usuario['nome']}.", $provaId);
            return ['sucesso' => true, 'mensagem' => 'Prova concluída e encerrada com sucesso!'];

        case 'reiniciar':
            $stmt = $db->prepare("UPDATE provas SET status = 'configurada', segundos_restantes = tempo_total_segundos, iniciado_em = NULL, concluido_em = NULL WHERE id = :id");
            $stmt->execute([':id' => $provaId]);

            registrarLog('REINICIAR_PROVA', "Timer da prova \"{$prova['titulo']}\" reiniciado para o tempo total de " . formatarSegundos($prova['tempo_total_segundos']), $provaId);
            return ['sucesso' => true, 'mensagem' => 'Timer reiniciado para o tempo padrão.'];

        default:
            return ['sucesso' => false, 'mensagem' => 'Ação de timer desconhecida.'];
    }
}

/**
 * Exclui uma prova e seus timers
 */
function excluirProva(int $id): array {
    if (!usuarioLogado()) {
        return ['sucesso' => false, 'mensagem' => 'Acesso negado.'];
    }

    $db = getDB();
    $prova = obterProva($id);

    if (!$prova) {
        return ['sucesso' => false, 'mensagem' => 'Prova não encontrada.'];
    }

    $stmt = $db->prepare("DELETE FROM provas WHERE id = :id");
    $stmt->execute([':id' => $id]);

    registrarLog('EXCLUSAO_PROVA', "Prova \"{$prova['titulo']}\" e seu timer foram excluídos.");

    return ['sucesso' => true, 'mensagem' => 'Prova excluída com sucesso.'];
}
