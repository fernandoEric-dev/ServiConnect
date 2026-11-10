-- SERVICONNECT ETAPAS 4 E 5: ORCAMENTOS, PERFIS E MAPA.
-- Atualizacao incremental. Nao apaga usuarios, perfis, senhas ou pedidos.
-- Exporte um backup atualizado e selecione o banco do site antes de executar.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS schema_migrations (versao VARCHAR(100) PRIMARY KEY, aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='experiencia_anos'), 'ALTER TABLE empresas ADD COLUMN experiencia_anos TINYINT UNSIGNED DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='website'), 'ALTER TABLE empresas ADD COLUMN website VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='pagamentos'), 'ALTER TABLE empresas ADD COLUMN pagamentos TEXT DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='modalidades'), 'ALTER TABLE empresas ADD COLUMN modalidades TEXT DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='dias_atendimento'), 'ALTER TABLE empresas ADD COLUMN dias_atendimento TEXT DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='horario_inicio'), 'ALTER TABLE empresas ADD COLUMN horario_inicio CHAR(5) DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='horario_fim'), 'ALTER TABLE empresas ADD COLUMN horario_fim CHAR(5) DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='atendimento_24h'), 'ALTER TABLE empresas ADD COLUMN atendimento_24h TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='emite_nota'), 'ALTER TABLE empresas ADD COLUMN emite_nota TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='cidades_atendidas'), 'ALTER TABLE empresas ADD COLUMN cidades_atendidas TEXT DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='municipio_ibge'), 'ALTER TABLE empresas ADD COLUMN municipio_ibge VARCHAR(7) DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='mapa_publico'), 'ALTER TABLE empresas ADD COLUMN mapa_publico TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='servico_id'), 'ALTER TABLE solicitacoes ADD COLUMN servico_id VARCHAR(60) DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='frequencia'), 'ALTER TABLE solicitacoes ADD COLUMN frequencia VARCHAR(30) DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='data_preferida'), 'ALTER TABLE solicitacoes ADD COLUMN data_preferida DATE DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='prazo_execucao'), 'ALTER TABLE solicitacoes ADD COLUMN prazo_execucao VARCHAR(150) DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='condicoes_pagamento'), 'ALTER TABLE solicitacoes ADD COLUMN condicoes_pagamento TEXT DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='itens_inclusos'), 'ALTER TABLE solicitacoes ADD COLUMN itens_inclusos TEXT DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='itens_exclusos'), 'ALTER TABLE solicitacoes ADD COLUMN itens_exclusos TEXT DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='mensagem_proposta'), 'ALTER TABLE solicitacoes ADD COLUMN mensagem_proposta TEXT DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='validade_proposta'), 'ALTER TABLE solicitacoes ADD COLUMN validade_proposta DATE DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='atualizada_em'), 'ALTER TABLE solicitacoes ADD COLUMN atualizada_em DATETIME DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='versao'), 'ALTER TABLE solicitacoes ADD COLUMN versao INT UNSIGNED NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND COLUMN_NAME='chave_criacao'), 'ALTER TABLE solicitacoes ADD COLUMN chave_criacao CHAR(64) DEFAULT NULL', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

ALTER TABLE solicitacoes MODIFY status ENUM('','aberta','em_negociacao','fechada','cancelada','orçada','respondida','recusada','aceita','em_execucao','aguardando_confirmacao','concluida','recusada_cliente') DEFAULT 'aberta';

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND INDEX_NAME='idx_solicitacao_chave'), 'CREATE UNIQUE INDEX idx_solicitacao_chave ON solicitacoes(chave_criacao)', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND INDEX_NAME='idx_solicitacao_contratante_status'), 'CREATE INDEX idx_solicitacao_contratante_status ON solicitacoes(contratante_id,status,id)', 'SELECT 1');
PREPARE serviconnect_stmt FROM @serviconnect_ddl;
EXECUTE serviconnect_stmt;
DEALLOCATE PREPARE serviconnect_stmt;

CREATE TABLE IF NOT EXISTS empresa_servicos (
 usuario_id INT NOT NULL,
 servico_id VARCHAR(60) NOT NULL,
 nome VARCHAR(100) NOT NULL,
 PRIMARY KEY(usuario_id,servico_id),
 KEY idx_empresa_servicos_tipo(servico_id,usuario_id),
 CONSTRAINT fk_empresa_servicos_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS solicitacoes_eventos (
 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 solicitacao_id INT NOT NULL,
 autor_id INT DEFAULT NULL,
 autor_nome VARCHAR(150) NOT NULL,
 papel VARCHAR(20) NOT NULL,
 tipo VARCHAR(40) NOT NULL,
 status_de VARCHAR(40) DEFAULT NULL,
 status_para VARCHAR(40) DEFAULT NULL,
 mensagem TEXT DEFAULT NULL,
 dados_json TEXT DEFAULT NULL,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_eventos_solicitacao(solicitacao_id,id),
 CONSTRAINT fk_evento_solicitacao FOREIGN KEY(solicitacao_id) REFERENCES solicitacoes(id) ON DELETE CASCADE,
 CONSTRAINT fk_evento_autor FOREIGN KEY(autor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

UPDATE empresas SET mapa_publico=1 WHERE tipo_empresa='terceirizada' AND latitude IS NOT NULL AND longitude IS NOT NULL AND NOT EXISTS(SELECT 1 FROM schema_migrations WHERE versao='005_orcamentos_perfis.sql');

INSERT INTO solicitacoes_eventos(solicitacao_id,autor_nome,papel,tipo,status_para,mensagem)
 SELECT s.id,'Sistema','sistema','historico_iniciado',s.status,'Historico ativado para um pedido anterior. Movimentos anteriores nao foram registrados.' FROM solicitacoes s WHERE NOT EXISTS(SELECT 1 FROM solicitacoes_eventos e WHERE e.solicitacao_id=s.id);

INSERT IGNORE INTO schema_migrations(versao) VALUES('005_orcamentos_perfis.sql');
