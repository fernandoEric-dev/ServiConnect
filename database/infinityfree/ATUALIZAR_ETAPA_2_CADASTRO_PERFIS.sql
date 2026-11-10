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
