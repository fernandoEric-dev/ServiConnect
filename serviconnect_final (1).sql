-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 18/11/2025 às 11:11
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `serviconnect_final`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

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

--
-- Despejando dados para a tabela `empresas`
--

INSERT INTO `empresas` (`id`, `usuario_id`, `nome`, `tipo_empresa`, `telefone`, `responsavel`, `descricao`, `regiao`, `horario`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `foto_path`, `num_funcionarios`, `area_atuacao`, `latitude`, `longitude`) VALUES
(1, 1, 'ServiConnect', 'terceirizada', '(11) 96657-1276', 'Fernando', 'fff', 'Ferraz', 'Seg a Sex 8:00 as 19:00', '08504320', 'Rua Manuel Correa da Silva', '74', '', 'Jardim Yone', 'Ferraz de Vasconcelos', 'SP', NULL, 0, NULL, NULL, NULL),
(2, 2, 'Legal tercerizezada', 'contratante', '(11) 97749-8349', 'Fernando', 'segurança', 'Ferraz', 'Seg a Sex 8:00 as 19:00', '08504310', 'Rua José Conrado do Nascimento', '100', '', 'Jardim Yone', 'Ferraz de Vasconcelos', 'SP', NULL, 0, NULL, NULL, NULL),
(3, 4, 'ServiConnect', 'contratante', '(11) 45884-8458', 'Extra', 'Segurança', 'São paulo', 'Seg a Sex 8:00 as 19:00', '08504320', 'Rua Manuel Correa da Silva', '898', '', 'Jardim Yone', 'Ferraz de Vasconcelos', 'SP', NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes`
--

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

--
-- Despejando dados para a tabela `solicitacoes`
--

INSERT INTO `solicitacoes` (`id`, `contratante_id`, `terceirizada_id`, `descricao_servico`, `localizacao_servico`, `status`, `data_solicitacao`, `numero_funcionarios`, `area_servico_solicitada`, `valor_orcamento`) VALUES
(1, 4, 1, 'dahnjf', '08504320', '', '2025-11-18 09:48:05', 5, 'ti', 200.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `cpf_cnpj` varchar(14) NOT NULL COMMENT 'CNPJ limpo (14 digitos)',
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_conta` enum('admin','empresa') NOT NULL DEFAULT 'empresa',
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `cpf_cnpj`, `email`, `senha`, `tipo_conta`, `data_cadastro`) VALUES
(1, '05570714000159', 'fernando.eric74983@gmail.com', '$2y$10$5iV2Fe.TEaS2tU4oLWaifOPlW8Lz4xO8RUK./2AatsSp7QkUVgiBG', 'empresa', '2025-11-17 20:38:34'),
(2, '03361252000134', 'fernandoeric.feds8@gmail.com', '$2y$10$ZC3ColTVGWflFcZBaf3pmuXSwNXy1o9aqXrzj8vEHCwO2nPEkVgDu', 'empresa', '2025-11-17 20:44:07'),
(3, '99911199111111', 'admin@serviconnect.com', 'legalizado', 'admin', '2025-11-17 22:28:10'),
(4, '33041260065290', 'f@gmail.com', '$2y$10$j1U7gq86NqdeVjY.Jfd2MOYoZNJqtpfHYlyEOh8SV2B4fa2fxvSoK', 'empresa', '2025-11-18 09:10:30');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `solicitacoes`
--
ALTER TABLE `solicitacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contratante_id` (`contratante_id`),
  ADD KEY `terceirizada_id` (`terceirizada_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `solicitacoes`
--
ALTER TABLE `solicitacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `empresas`
--
ALTER TABLE `empresas`
  ADD CONSTRAINT `empresas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `solicitacoes`
--
ALTER TABLE `solicitacoes`
  ADD CONSTRAINT `solicitacoes_ibfk_1` FOREIGN KEY (`contratante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitacoes_ibfk_2` FOREIGN KEY (`terceirizada_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
