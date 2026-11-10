-- SERVICONNECT: ATUALIZACAO DO BANCO EXISTENTE.
-- Exporte um backup atualizado pelo phpMyAdmin antes de importar.
-- Compativel com a estrutura do dump fornecido; preserva os registros.
-- Execute com o banco correto selecionado no phpMyAdmin, em UTF-8.
SET NAMES utf8mb4;

-- O dump original contém pedidos com status vazio (valor ENUM 0).
-- Preserva esse histórico e também NULL, sem inferir uma decisão comercial.
ALTER TABLE solicitacoes MODIFY status ENUM('aberta','em_negociacao','fechada','cancelada','orçada','respondida','recusada','') DEFAULT 'aberta';

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='arquivado_em'), 'ALTER TABLE usuarios ADD COLUMN arquivado_em DATETIME DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND INDEX_NAME='idx_usuarios_status'), 'CREATE INDEX idx_usuarios_status ON usuarios(status)', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND INDEX_NAME='idx_solicitacoes_destinatario_status'), 'CREATE INDEX idx_solicitacoes_destinatario_status ON solicitacoes(terceirizada_id,status)', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

CREATE TABLE IF NOT EXISTS login_tentativas (
    chave CHAR(64) NOT NULL PRIMARY KEY,
    tentativas INT NOT NULL DEFAULT 0,
    janela_inicio DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (versao VARCHAR(100) PRIMARY KEY, aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
INSERT IGNORE INTO schema_migrations (versao) VALUES ('001_base.sql'), ('002_integridade.sql'), ('003_preservar_status_legado.sql');


-- SERVICONNECT ETAPA 2: CPF/CNPJ, MEI E TERCEIRIZADA.
-- Para o banco existente, apos a atualizacao de seguranca da etapa 1.
-- Selecione seu banco no phpMyAdmin e exporte um backup antes de importar.
-- Nao apaga nem recria contas, perfis, senhas ou pedidos.
SET NAMES utf8mb4;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='tipo_pessoa'), 'ALTER TABLE usuarios ADD COLUMN tipo_pessoa ENUM(''fisica'',''juridica'') NOT NULL DEFAULT ''juridica''', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

ALTER TABLE usuarios MODIFY cpf_cnpj VARCHAR(14) NOT NULL COMMENT 'CPF (11 posicoes) ou CNPJ numerico/alfanumerico (14 posicoes), sem mascara';
ALTER TABLE empresas MODIFY tipo_empresa ENUM('contratante','terceirizada','mei') NOT NULL;

CREATE TABLE IF NOT EXISTS schema_migrations (versao VARCHAR(100) PRIMARY KEY, aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
INSERT IGNORE INTO schema_migrations (versao) VALUES ('004_cadastro_perfis.sql');
