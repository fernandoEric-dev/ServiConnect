-- O dump original contém pedidos com status vazio (valor ENUM 0).
-- Preserva esse histórico e também NULL, sem inferir uma decisão comercial.
ALTER TABLE solicitacoes MODIFY status ENUM('aberta','em_negociacao','fechada','cancelada','orçada','respondida','recusada','') DEFAULT 'aberta';
