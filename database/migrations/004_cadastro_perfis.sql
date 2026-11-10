-- Contas existentes permanecem pessoas jurídicas, com IDs e vínculos intactos.
ALTER TABLE usuarios
    ADD COLUMN tipo_pessoa ENUM('fisica','juridica') NOT NULL DEFAULT 'juridica',
    MODIFY cpf_cnpj VARCHAR(14) NOT NULL COMMENT 'CPF (11 posições) ou CNPJ numérico/alfanumérico (14 posições), sem máscara';
ALTER TABLE empresas MODIFY tipo_empresa ENUM('contratante','terceirizada','mei') NOT NULL;
