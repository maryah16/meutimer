<?php
/**
 * Conexão com o Banco de Dados via PDO
 * Suporta MySQL nativo e fallback automático SQLite
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    private static function connect(): void {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // 1. Tenta MySQL
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $mysqlOptions = $options + [
                PDO::ATTR_TIMEOUT => 2,
            ];

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $mysqlOptions);
            self::$driver = 'mysql';
        } catch (PDOException $e) {
            // Se o banco não existe no MySQL, tenta criá-lo
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $rootDsn = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
                    $tmpPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                    $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                    self::$driver = 'mysql';
                    self::initializeSchema(self::$instance, 'mysql');
                    return;
                } catch (Exception $ex) {
                    // Segue para fallback
                }
            }

            // 2. Fallback para SQLite embutido
            try {
                $sqlitePath = __DIR__ . '/timer_provas.sqlite';
                self::$instance = new PDO('sqlite:' . $sqlitePath, null, null, $options);
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                self::$driver = 'sqlite';
            } catch (PDOException $sqliteEx) {
                die("Erro crítico de conexão com o banco de dados: " . $sqliteEx->getMessage());
            }
        }

        self::initializeSchema(self::$instance, self::$driver);
    }

    /**
     * Cria as tabelas do sistema se ainda não existirem
     */
    private static function initializeSchema(PDO $pdo, string $driver): void {
        if ($driver === 'mysql') {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS coordenadores (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(120) NOT NULL,
                    email VARCHAR(150) NOT NULL UNIQUE,
                    cpf VARCHAR(14) NOT NULL UNIQUE,
                    senha VARCHAR(255) NOT NULL,
                    cargo VARCHAR(80) DEFAULT 'Coordenador de Provas',
                    email_confirmado TINYINT(1) DEFAULT 1,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS provas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    coordenador_id INT NOT NULL,
                    titulo VARCHAR(150) NOT NULL,
                    sala VARCHAR(60) NULL,
                    tempo_total_segundos INT NOT NULL,
                    tempo_alerta_intervalo INT DEFAULT 1800,
                    alerta_som TINYINT(1) DEFAULT 1,
                    tipo_som VARCHAR(30) DEFAULT 'beep',
                    alerta_aviso_tela TINYINT(1) DEFAULT 1,
                    alerta_voz TINYINT(1) DEFAULT 0,
                    instrucoes TEXT NULL,
                    status ENUM('configurada', 'em_andamento', 'pausada', 'concluida') DEFAULT 'configurada',
                    segundos_restantes INT NULL,
                    iniciado_em DATETIME NULL,
                    concluido_em DATETIME NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (coordenador_id) REFERENCES coordenadores(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS logs_auditoria (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    coordenador_id INT NULL,
                    coordenador_nome VARCHAR(120) NULL,
                    prova_id INT NULL,
                    acao VARCHAR(60) NOT NULL,
                    descricao TEXT NOT NULL,
                    dados_anteriores TEXT NULL,
                    dados_novos TEXT NULL,
                    ip VARCHAR(45) NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (prova_id) REFERENCES provas(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            // SQLite
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS coordenadores (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome TEXT NOT NULL,
                    email TEXT NOT NULL UNIQUE,
                    cpf TEXT NOT NULL UNIQUE,
                    senha TEXT NOT NULL,
                    cargo TEXT DEFAULT 'Coordenador de Provas',
                    email_confirmado INTEGER DEFAULT 1,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS provas (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    coordenador_id INTEGER NOT NULL,
                    titulo TEXT NOT NULL,
                    sala TEXT,
                    tempo_total_segundos INTEGER NOT NULL,
                    tempo_alerta_intervalo INTEGER DEFAULT 1800,
                    alerta_som INTEGER DEFAULT 1,
                    tipo_som TEXT DEFAULT 'beep',
                    alerta_aviso_tela INTEGER DEFAULT 1,
                    alerta_voz INTEGER DEFAULT 0,
                    instrucoes TEXT,
                    status TEXT DEFAULT 'configurada',
                    segundos_restantes INTEGER,
                    iniciado_em DATETIME,
                    concluido_em DATETIME,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (coordenador_id) REFERENCES coordenadores(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS logs_auditoria (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    coordenador_id INTEGER,
                    coordenador_nome TEXT,
                    prova_id INTEGER,
                    acao TEXT NOT NULL,
                    descricao TEXT NOT NULL,
                    dados_anteriores TEXT,
                    dados_novos TEXT,
                    ip TEXT,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (prova_id) REFERENCES provas(id) ON DELETE CASCADE
                );
            ");
        }

        // Insere coordenador padrão se não houver registros
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM coordenadores");
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        if ($total === 0) {
            $senhaHash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("
                INSERT INTO coordenadores (nome, email, cpf, senha, cargo, email_confirmado)
                VALUES (:nome, :email, :cpf, :senha, :cargo, 1)
            ");
            $stmtInsert->execute([
                ':nome' => 'Prof. Coordenador Geral',
                ':email' => 'coordenador@etec.sp.gov.br',
                ':cpf' => '123.456.789-00',
                ':senha' => $senhaHash,
                ':cargo' => 'Coordenador de Exames'
            ]);
        }
    }
}

function getDB(): PDO {
    return Database::getConnection();
}
