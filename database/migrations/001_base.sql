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
