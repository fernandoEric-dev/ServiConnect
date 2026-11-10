-- Alterações aditivas; mantém contas, pedidos, valores e estados legados.
ALTER TABLE empresas
 ADD COLUMN experiencia_anos TINYINT UNSIGNED DEFAULT NULL,
 ADD COLUMN website VARCHAR(255) DEFAULT NULL,
 ADD COLUMN pagamentos TEXT DEFAULT NULL,
 ADD COLUMN modalidades TEXT DEFAULT NULL,
 ADD COLUMN dias_atendimento TEXT DEFAULT NULL,
 ADD COLUMN horario_inicio CHAR(5) DEFAULT NULL,
 ADD COLUMN horario_fim CHAR(5) DEFAULT NULL,
 ADD COLUMN atendimento_24h TINYINT(1) NOT NULL DEFAULT 0,
 ADD COLUMN emite_nota TINYINT(1) NOT NULL DEFAULT 0,
 ADD COLUMN cidades_atendidas TEXT DEFAULT NULL,
 ADD COLUMN municipio_ibge VARCHAR(7) DEFAULT NULL,
 ADD COLUMN mapa_publico TINYINT(1) NOT NULL DEFAULT 0;
UPDATE empresas SET mapa_publico=1 WHERE tipo_empresa='terceirizada' AND latitude IS NOT NULL AND longitude IS NOT NULL;

CREATE TABLE empresa_servicos (
 usuario_id INT NOT NULL,
 servico_id VARCHAR(60) NOT NULL,
 nome VARCHAR(100) NOT NULL,
 PRIMARY KEY(usuario_id,servico_id),
 KEY idx_empresa_servicos_tipo(servico_id,usuario_id),
 CONSTRAINT fk_empresa_servicos_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE solicitacoes
 MODIFY status ENUM('','aberta','em_negociacao','fechada','cancelada','orçada','respondida','recusada','aceita','em_execucao','aguardando_confirmacao','concluida','recusada_cliente') DEFAULT 'aberta',
 ADD COLUMN servico_id VARCHAR(60) DEFAULT NULL,
 ADD COLUMN frequencia VARCHAR(30) DEFAULT NULL,
 ADD COLUMN data_preferida DATE DEFAULT NULL,
 ADD COLUMN prazo_execucao VARCHAR(150) DEFAULT NULL,
 ADD COLUMN condicoes_pagamento TEXT DEFAULT NULL,
 ADD COLUMN itens_inclusos TEXT DEFAULT NULL,
 ADD COLUMN itens_exclusos TEXT DEFAULT NULL,
 ADD COLUMN mensagem_proposta TEXT DEFAULT NULL,
 ADD COLUMN validade_proposta DATE DEFAULT NULL,
 ADD COLUMN atualizada_em DATETIME DEFAULT NULL,
 ADD COLUMN versao INT UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN chave_criacao CHAR(64) DEFAULT NULL,
 ADD UNIQUE KEY idx_solicitacao_chave(chave_criacao),
 ADD KEY idx_solicitacao_contratante_status(contratante_id,status,id);

CREATE TABLE solicitacoes_eventos (
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
INSERT INTO solicitacoes_eventos(solicitacao_id,autor_nome,papel,tipo,status_para,mensagem)
 SELECT id,'Sistema','sistema','historico_iniciado',status,'Histórico ativado para um pedido anterior. Movimentos anteriores não foram registrados.' FROM solicitacoes;
