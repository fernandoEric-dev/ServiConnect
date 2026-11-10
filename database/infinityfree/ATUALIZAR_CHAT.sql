-- SERVICONNECT CHAT: CONVERSAS E MENSAGENS.
-- Selecione o banco atual do site antes de importar.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS schema_migrations (versao VARCHAR(100) PRIMARY KEY, aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

CREATE TABLE IF NOT EXISTS chat_conversas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    contratante_id INT NOT NULL,
    prestador_id INT NOT NULL,
    ultima_mensagem_id BIGINT UNSIGNED DEFAULT NULL,
    UNIQUE KEY uq_chat_participantes (contratante_id, prestador_id),
    KEY idx_chat_contratante (contratante_id, ultima_mensagem_id),
    KEY idx_chat_prestador (prestador_id, ultima_mensagem_id),
    CONSTRAINT fk_chat_contratante FOREIGN KEY (contratante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_mensagens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    conversa_id INT UNSIGNED NOT NULL,
    autor_id INT NOT NULL,
    mensagem VARCHAR(2000) NOT NULL,
    solicitacao_id INT DEFAULT NULL,
    chave_envio CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_chat_envio (conversa_id, autor_id, chave_envio),
    KEY idx_chat_mensagens (conversa_id, id),
    KEY idx_chat_frequencia (conversa_id, autor_id, criado_em),
    CONSTRAINT fk_chat_mensagem FOREIGN KEY (conversa_id) REFERENCES chat_conversas(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_proposta FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO schema_migrations(versao) VALUES('006_chat.sql');
