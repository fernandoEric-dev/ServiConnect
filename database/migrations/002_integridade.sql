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
