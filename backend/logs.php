<?php
/**
 * Módulo de Logs e Auditoria - Timer de Provas
 * Garante a rastreabilidade de todas as alterações feitas pelo coordenador
 */

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/helpers.php';

function registrarLog(
    string $acao,
    string $descricao,
    ?int $prova_id = null,
    $dados_anteriores = null,
    $dados_novos = null
): bool {
    try {
        $db = getDB();
        $usuario = getUsuarioAtual();
        $coordenador_id = $usuario['id'] ?? null;
        $coordenador_nome = $usuario['nome'] ?? 'Sistema / Visitante';

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        $antigoJson = $dados_anteriores !== null ? (is_string($dados_anteriores) ? $dados_anteriores : json_encode($dados_anteriores, JSON_UNESCAPED_UNICODE)) : null;
        $novoJson = $dados_novos !== null ? (is_string($dados_novos) ? $dados_novos : json_encode($dados_novos, JSON_UNESCAPED_UNICODE)) : null;

        $stmt = $db->prepare("
            INSERT INTO logs_auditoria (coordenador_id, coordenador_nome, prova_id, acao, descricao, dados_anteriores, dados_novos, ip, criado_em)
            VALUES (:cid, :cnome, :pid, :acao, :descricao, :antigo, :novo, :ip, :criado_em)
        ");

        return $stmt->execute([
            ':cid' => $coordenador_id,
            ':cnome' => $coordenador_nome,
            ':pid' => $prova_id,
            ':acao' => mb_strtoupper($acao, 'UTF-8'),
            ':descricao' => $descricao,
            ':antigo' => $antigoJson,
            ':novo' => $novoJson,
            ':ip' => substr($ip, 0, 45),
            ':criado_em' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Erro ao registrar log de auditoria: " . $e->getMessage());
        return false;
    }
}

function listarLogs(array $filtros = [], int $limite = 50, int $offset = 0): array {
    $db = getDB();

    $where = ["1=1"];
    $params = [];

    if (!empty($filtros['prova_id'])) {
        $where[] = "l.prova_id = :prova_id";
        $params[':prova_id'] = (int)$filtros['prova_id'];
    }

    if (!empty($filtros['acao'])) {
        $where[] = "l.acao = :acao";
        $params[':acao'] = $filtros['acao'];
    }

    if (!empty($filtros['busca'])) {
        $where[] = "(l.descricao LIKE :busca OR l.coordenador_nome LIKE :busca OR p.titulo LIKE :busca)";
        $params[':busca'] = '%' . $filtros['busca'] . '%';
    }

    $whereSql = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) FROM logs_auditoria l 
                 LEFT JOIN provas p ON l.prova_id = p.id 
                 WHERE $whereSql";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT l.*, p.titulo as prova_titulo 
            FROM logs_auditoria l 
            LEFT JOIN provas p ON l.prova_id = p.id 
            WHERE $whereSql 
            ORDER BY l.id DESC 
            LIMIT :limite OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'total' => $total,
        'logs' => $stmt->fetchAll()
    ];
}

function obterAcoesRegistradas(): array {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT acao FROM logs_auditoria ORDER BY acao ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
