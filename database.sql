-- ==========================================================
-- Banco de Dados: timer_provas_db
-- Sistema de Timer de Provas com Alertas Configuráveis
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `timer_provas_db` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `timer_provas_db`;

-- 1. Tabela de Coordenadores / Usuários
CREATE TABLE IF NOT EXISTS `coordenadores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `cpf` VARCHAR(14) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `cargo` VARCHAR(80) DEFAULT 'Coordenador de Provas',
  `email_confirmado` TINYINT(1) DEFAULT 1,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela de Provas e Configuração de Timers
CREATE TABLE IF NOT EXISTS `provas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `coordenador_id` INT NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `sala` VARCHAR(60) NULL,
  `tempo_total_segundos` INT NOT NULL,
  `tempo_alerta_intervalo` INT DEFAULT 1800,
  `alerta_som` TINYINT(1) DEFAULT 1,
  `tipo_som` VARCHAR(30) DEFAULT 'beep',
  `alerta_aviso_tela` TINYINT(1) DEFAULT 1,
  `alerta_voz` TINYINT(1) DEFAULT 0,
  `instrucoes` TEXT NULL,
  `status` ENUM('configurada', 'em_andamento', 'pausada', 'concluida') DEFAULT 'configurada',
  `segundos_restantes` INT NULL,
  `iniciado_em` DATETIME NULL,
  `concluido_em` DATETIME NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_prova_coordenador` FOREIGN KEY (`coordenador_id`) REFERENCES `coordenadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de Logs e Auditoria
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `coordenador_id` INT NULL,
  `coordenador_nome` VARCHAR(120) NULL,
  `prova_id` INT NULL,
  `acao` VARCHAR(60) NOT NULL,
  `descricao` TEXT NOT NULL,
  `dados_anteriores` TEXT NULL,
  `dados_novos` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_prova` FOREIGN KEY (`prova_id`) REFERENCES `provas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coordenador Padrão (Senha: admin123)
INSERT INTO `coordenadores` (`nome`, `email`, `cpf`, `senha`, `cargo`, `email_confirmado`)
VALUES ('Prof. Coordenador Geral', 'coordenador@etec.sp.gov.br', '123.456.789-00', '$2y$10$w8W7hT4LqJ2Yf3g1k7h1e.j9Yt7qCgYdJvK9vX0uF6l5d3b6a9eOi', 'Coordenador de Exames', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;
