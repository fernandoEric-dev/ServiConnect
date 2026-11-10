-- INSTALACAO NOVA. EXCLUSIVO PARA BANCO VAZIO. SEM USUARIOS DE DEMONSTRACAO.
SET NAMES utf8mb4;

-- Estrutura do banco atual; nenhum registro de produção foi copiado.
CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `login_cnpj` varchar(14) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Chave estrangeira para a tabela usuarios',
  `nome` varchar(150) NOT NULL,
  `tipo_empresa` enum('contratante','terceirizada') NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `regiao` varchar(255) DEFAULT NULL,
  `horario` varchar(50) DEFAULT NULL,
  `cep` varchar(8) NOT NULL,
  `logradouro` varchar(100) NOT NULL,
  `numero` varchar(10) NOT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `num_funcionarios` int(11) DEFAULT 0,
  `area_atuacao` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `solicitacoes` (
  `id` int(11) NOT NULL,
  `contratante_id` int(11) NOT NULL,
  `terceirizada_id` int(11) DEFAULT NULL,
  `descricao_servico` text NOT NULL,
  `localizacao_servico` varchar(255) DEFAULT NULL,
  `status` enum('aberta','em_negociacao','fechada','cancelada') DEFAULT 'aberta',
  `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `numero_funcionarios` int(11) DEFAULT NULL,
  `area_servico_solicitada` varchar(100) DEFAULT NULL,
  `valor_orcamento` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `cpf_cnpj` varchar(14) NOT NULL COMMENT 'CNPJ limpo (14 digitos)',
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_conta` enum('admin','empresa') NOT NULL DEFAULT 'empresa',
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'ativo',
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `usuarios_arquivados` (
  `id` int(11) NOT NULL,
  `id_original` int(11) NOT NULL,
  `cpf_cnpj` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_conta` varchar(50) NOT NULL,
  `data_arquivamento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login_cnpj` (`login_cnpj`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

ALTER TABLE `solicitacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contratante_id` (`contratante_id`),
  ADD KEY `terceirizada_id` (`terceirizada_id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `usuarios_arquivados`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `solicitacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios_arquivados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `empresas`
  ADD CONSTRAINT `empresas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `solicitacoes`
  ADD CONSTRAINT `solicitacoes_ibfk_1` FOREIGN KEY (`contratante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitacoes_ibfk_2` FOREIGN KEY (`terceirizada_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;


-- Mantém os estados anteriores e adiciona os utilizados pelas telas atuais.
ALTER TABLE solicitacoes MODIFY status ENUM('aberta','em_negociacao','fechada','cancelada','orçada','respondida','recusada') NOT NULL DEFAULT 'aberta';
ALTER TABLE usuarios ADD COLUMN arquivado_em DATETIME DEFAULT NULL;
CREATE INDEX idx_usuarios_status ON usuarios(status);
CREATE INDEX idx_solicitacoes_destinatario_status ON solicitacoes(terceirizada_id, status);
CREATE TABLE login_tentativas (
    chave CHAR(64) NOT NULL PRIMARY KEY,
    tentativas INT NOT NULL DEFAULT 0,
    janela_inicio DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- O dump original contém pedidos com status vazio (valor ENUM 0).
-- Preserva esse histórico e também NULL, sem inferir uma decisão comercial.
ALTER TABLE solicitacoes MODIFY status ENUM('aberta','em_negociacao','fechada','cancelada','orçada','respondida','recusada','') DEFAULT 'aberta';


-- Contas existentes permanecem pessoas jurídicas, com IDs e vínculos intactos.
ALTER TABLE usuarios
    ADD COLUMN tipo_pessoa ENUM('fisica','juridica') NOT NULL DEFAULT 'juridica',
    MODIFY cpf_cnpj VARCHAR(14) NOT NULL COMMENT 'CPF (11 posições) ou CNPJ numérico/alfanumérico (14 posições), sem máscara';
ALTER TABLE empresas MODIFY tipo_empresa ENUM('contratante','terceirizada','mei') NOT NULL;


CREATE TABLE IF NOT EXISTS schema_migrations(versao VARCHAR(100) PRIMARY KEY,aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
INSERT IGNORE INTO schema_migrations(versao) VALUES('001_base.sql'),('002_integridade.sql'),('003_preservar_status_legado.sql'),('004_cadastro_perfis.sql');

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
