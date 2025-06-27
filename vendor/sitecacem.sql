-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 26-Jun-2025 às 15:22
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sitecacem`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`) VALUES
(1, 'Infantis'),
(2, 'Iniciados'),
(3, 'Juvenis'),
(4, 'Juniores'),
(5, 'Seniores');

-- --------------------------------------------------------

--
-- Estrutura da tabela `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `data_compra` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `dashboard_info`
--

CREATE TABLE `dashboard_info` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `estatisticas` text DEFAULT NULL,
  `notificacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `documentos`
--

CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `nome_ficheiro_original` varchar(255) NOT NULL,
  `caminho_ficheiro` varchar(255) NOT NULL,
  `tipo_ficheiro` varchar(100) DEFAULT NULL,
  `tamanho_ficheiro` int(11) DEFAULT NULL,
  `data_upload` datetime DEFAULT current_timestamp(),
  `escalao_id` int(11) DEFAULT NULL,
  `modalidade_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `documentos`
--

INSERT INTO `documentos` (`id`, `user_id`, `titulo`, `descricao`, `nome_ficheiro_original`, `caminho_ficheiro`, `tipo_ficheiro`, `tamanho_ficheiro`, `data_upload`, `escalao_id`, `modalidade_id`) VALUES
(1, 16, 'Convocatória para Sábado', 'Convocatória', 'Captura de ecrã 2024-06-28 183913.png', '../uploads/documentos/6851638d40a7c.png', 'image/png', 66277, '2025-06-17 13:46:05', 7, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `documentos_medicos`
--

CREATE TABLE `documentos_medicos` (
  `id` int(11) NOT NULL,
  `atleta_id` int(11) NOT NULL,
  `tipo` enum('exame','seguro') NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `validade` date DEFAULT NULL,
  `apolice` varchar(50) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `documentos_medicos`
--

INSERT INTO `documentos_medicos` (`id`, `atleta_id`, `tipo`, `nome_arquivo`, `validade`, `apolice`, `criado_em`) VALUES
(1, 17, 'exame', 'doc_68596553d4a961.14451741.png', '2026-06-23', 'MediCare', '2025-06-23 15:31:47'),
(2, 26, 'exame', 'doc_685b3516bc6737.14798544.png', '2026-06-25', 'MediCare', '2025-06-25 00:30:30');

-- --------------------------------------------------------

--
-- Estrutura da tabela `equipamentos_atribuidos`
--

CREATE TABLE `equipamentos_atribuidos` (
  `id` int(11) NOT NULL,
  `atleta_id` int(11) NOT NULL,
  `equipamento_descricao` varchar(255) NOT NULL,
  `data_atribuicao` date NOT NULL,
  `data_devolucao` date DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `escaloes`
--

CREATE TABLE `escaloes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `modalidade_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `idade_min` int(11) NOT NULL DEFAULT 0,
  `idade_max` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `escaloes`
--

INSERT INTO `escaloes` (`id`, `nome`, `modalidade_id`, `staff_id`, `user_id`, `idade_min`, `idade_max`, `ativo`) VALUES
(1, 'Bambis', 1, 16, NULL, 6, 10, 1),
(2, 'Minis', 1, 16, NULL, 10, 12, 1),
(3, 'Infantis', 1, 16, NULL, 12, 14, 1),
(4, 'Iniciados', 1, 16, NULL, 14, 16, 1),
(5, 'Juvenis', 1, 16, NULL, 16, 18, 1),
(6, 'Juniores', 1, 16, NULL, 18, 20, 1),
(7, 'Seniores', 1, 16, NULL, 20, 35, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `escaloes_categorias`
--

CREATE TABLE `escaloes_categorias` (
  `id` int(11) NOT NULL,
  `escalao_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `escaloes_utilizadores`
--

CREATE TABLE `escaloes_utilizadores` (
  `id` int(11) NOT NULL,
  `escalao_id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `escaloes_utilizadores`
--

INSERT INTO `escaloes_utilizadores` (`id`, `escalao_id`, `utilizador_id`) VALUES
(1, 7, 21),
(2, 7, 17);

-- --------------------------------------------------------

--
-- Estrutura da tabela `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `data` datetime NOT NULL,
  `local` varchar(255) DEFAULT NULL,
  `tipo` enum('treino','jogo') NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `jogos`
--

CREATE TABLE `jogos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `escalao_id` int(11) NOT NULL,
  `data_jogo` datetime NOT NULL,
  `local` varchar(255) NOT NULL,
  `adversario` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('agendado','concluido','cancelado') DEFAULT 'agendado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pontuacao_acc` int(11) DEFAULT NULL,
  `pontuacao_adversario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `jogos`
--

INSERT INTO `jogos` (`id`, `titulo`, `escalao_id`, `data_jogo`, `local`, `adversario`, `descricao`, `user_id`, `status`, `created_at`, `updated_at`, `pontuacao_acc`, `pontuacao_adversario`) VALUES
(1, 'Campeonato 3 ºDivisão', 7, '2025-06-24 16:30:00', 'Escola Secundária Gama Barros', 'Boa-Hora', 'Concentração: 19:00h', 16, 'concluido', '2025-06-13 23:04:21', '2025-06-23 14:12:11', 41, 33);

-- --------------------------------------------------------

--
-- Estrutura da tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `atleta_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `destinatario_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `conteudo` text NOT NULL,
  `data_envio` datetime DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `mensagens`
--

INSERT INTO `mensagens` (`id`, `atleta_id`, `remetente_id`, `destinatario_id`, `titulo`, `conteudo`, `data_envio`, `lida`) VALUES
(0, 17, 16, 17, 'Convocatória para Sábado', 'Infelizmente não vais ser convocado porque só fazes fitas que estás com febre.', '2025-05-27 17:23:24', 1),
(0, 17, 16, 17, 'Convocatória para Sábado', 'Sem problema algum vai a merda', '2025-05-27 17:24:01', 1),
(0, 19, 16, 19, '56jhdtyj', 'kdtkhdg', '2025-06-10 00:37:43', 1),
(0, 19, 16, 19, 'efhgfrsdjsfgj', 'stkjsdfghkdgsk', '2025-06-10 00:38:03', 1),
(0, 0, 16, 0, 'Re: efhgfrsdjsfgj', 'benficaaaaaaa', '2025-06-10 00:40:01', 1),
(0, 0, 16, 0, 'Re: efhgfrsdjsfgj', 'benfica vs porto', '2025-06-10 00:41:29', 1),
(0, 21, 16, 21, 'Convocatória para Sábado', 'achas que consigo ser convocado?', '2025-06-17 13:03:18', 1),
(0, 0, 16, 0, 'Re: Convocatória para Sábado', 'sim vais ser', '2025-06-17 13:04:18', 1),
(0, 21, 16, 21, 'fdbsbfsdg', 'nsfgdnfgsnfsgnsfg', '2025-06-17 13:10:02', 1),
(0, 21, 16, 21, 'Re: fdbsbfsdg', 'bheathbnqatqqrtnrtqnarnqrn', '2025-06-17 13:10:21', 1),
(0, 21, 16, 21, '0', 'benfica', '2025-06-17 13:19:45', 1),
(0, 21, 16, 21, '0', 'sporting', '2025-06-17 13:20:50', 1),
(0, 21, 16, NULL, 'Re: teste', 'teste', '2025-06-17 13:21:35', 1),
(0, 17, 16, 17, 'teste ', 'teste', '2025-06-23 14:05:47', 1),
(0, 17, 16, 16, '0', 'teste PI RES', '2025-06-23 14:06:07', 1),
(0, 17, 16, 17, 'teste 2', 'tesatgaewsgdasdg', '2025-06-23 14:06:31', 1),
(0, 17, 16, 16, '0', 'teste 3', '2025-06-23 14:06:58', 1),
(0, 17, 17, 16, 'teste 5', 'teste5', '2025-06-23 14:21:16', 1),
(0, 0, 16, 0, 'Re: teste 5', 'teste helder para pires', '2025-06-23 14:21:35', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `mensalidades`
--

CREATE TABLE `mensalidades` (
  `id` int(11) NOT NULL,
  `atleta_id` int(11) NOT NULL,
  `mes` int(11) NOT NULL CHECK (`mes` >= 1 and `mes` <= 12),
  `ano` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `data_pagamento` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `mensalidades`
--

INSERT INTO `mensalidades` (`id`, `atleta_id`, `mes`, `ano`, `valor`, `status`, `data_pagamento`) VALUES
(3, 17, 1, 2024, 30.00, 'Paga', '2025-05-27'),
(4, 17, 2, 2024, 30.00, 'Paga', '2025-05-27'),
(6, 17, 4, 2024, 30.00, 'Paga', '2025-05-27'),
(7, 17, 12, 2023, 30.00, 'Paga', '2023-12-05'),
(8, 17, 11, 2025, 34.00, 'Paga', '2025-05-27'),
(9, 17, 1, 2026, 35.00, 'Pendente', NULL),
(10, 17, 2, 2026, 35.00, 'Paga', '2025-05-27'),
(11, 17, 3, 2026, 35.00, 'Pendente', NULL),
(12, 17, 4, 2026, 35.00, 'Paga', '2025-05-27'),
(13, 17, 5, 2026, 35.00, 'Pendente', NULL),
(14, 17, 6, 2026, 35.00, 'Paga', '2025-05-27'),
(15, 17, 7, 2026, 35.00, 'Paga', '2025-05-27'),
(16, 17, 8, 2026, 35.00, 'Paga', '2025-05-27'),
(17, 17, 9, 2026, 35.00, 'Paga', '2025-05-27'),
(18, 17, 10, 2026, 35.00, 'Pendente', NULL),
(19, 17, 11, 2026, 35.00, 'Paga', '2025-05-27'),
(20, 17, 12, 2026, 35.00, 'Paga', '2025-05-27'),
(21, 18, 2, 2025, 45.00, 'Pendente', NULL),
(22, 17, 7, 2024, 45.00, 'Paga', '2025-05-27'),
(26, 17, 2, 2025, 35.00, 'Paga', '2025-05-27'),
(27, 18, 6, 2026, 35.00, 'Pendente', NULL),
(28, 19, 4, 2025, 35.00, 'Pendente', NULL),
(29, 21, 2, 2025, 25.00, 'Pendente', NULL),
(30, 21, 5, 2025, 25.00, 'Pendente', NULL),
(31, 21, 6, 2025, 25.00, 'Pendente', NULL),
(32, 24, 1, 2027, 25.00, 'Pendente', NULL),
(33, 24, 2, 2027, 25.00, 'Pendente', NULL),
(34, 24, 3, 2027, 25.00, 'Pendente', NULL),
(35, 24, 4, 2027, 25.00, 'Pendente', NULL),
(36, 24, 5, 2027, 25.00, 'Pendente', NULL),
(37, 24, 6, 2027, 25.00, 'Pendente', NULL),
(38, 24, 7, 2027, 25.00, 'Pendente', NULL),
(39, 24, 8, 2027, 25.00, 'Pendente', NULL),
(40, 24, 9, 2027, 25.00, 'Pendente', NULL),
(41, 24, 10, 2027, 25.00, 'Pendente', NULL),
(42, 24, 11, 2027, 25.00, 'Pendente', NULL),
(43, 24, 12, 2027, 25.00, 'Pendente', NULL),
(44, 26, 1, 2025, 35.00, 'Pendente', NULL),
(45, 26, 3, 2025, 35.00, 'Pendente', NULL),
(46, 26, 4, 2025, 35.00, 'Paga', '2025-06-25'),
(47, 26, 5, 2025, 35.00, 'Pendente', NULL),
(48, 26, 6, 2026, 35.00, 'Pendente', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `modalidades`
--

CREATE TABLE `modalidades` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `modalidades`
--

INSERT INTO `modalidades` (`id`, `nome`, `descricao`, `ativo`) VALUES
(1, 'Andebol', 'Competição', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `atleta_id` int(11) NOT NULL,
  `data_pagamento` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`) VALUES
(1, 16, 'a6d53139c24b80b11574132c7c86f031c485c74be0ccb9d333f08e9c77e431f8', '2025-06-23 15:39:33', 0),
(2, 16, '53ae8b483f35ddb48925b7c0d5f9893e66f6e61936e89993d93dca4b919ab853', '2025-06-23 15:40:35', 0),
(3, 16, '97bcf081b23bdea60733da4098d06763517669424ba718a809eca4c6fbdc7ae4', '2025-06-23 15:42:56', 0),
(4, 16, '36cc1bc03c23b0db0763c46c655a1fe66c96911b1a4bbf6782c4430a67a49bac', '2025-06-23 15:54:18', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `permissoes`
--

CREATE TABLE `permissoes` (
  `id` int(11) NOT NULL,
  `tipo` enum('atleta','treinador','dirigente','socio','admin') NOT NULL,
  `acesso_dashboard` tinyint(1) DEFAULT 0,
  `acesso_perfil` tinyint(1) DEFAULT 1,
  `acesso_compras` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `permissoes`
--

INSERT INTO `permissoes` (`id`, `tipo`, `acesso_dashboard`, `acesso_perfil`, `acesso_compras`) VALUES
(1, 'atleta', 1, 0, 0),
(2, 'treinador', 1, 0, 0),
(3, 'dirigente', 1, 0, 0),
(4, 'admin', 1, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `socios_info`
--

CREATE TABLE `socios_info` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `historico_compras` text DEFAULT NULL,
  `configuracoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `treinos`
--

CREATE TABLE `treinos` (
  `id` int(11) NOT NULL,
  `modalidade_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `local` varchar(255) NOT NULL,
  `treinador` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `anexo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `treinos`
--

INSERT INTO `treinos` (`id`, `modalidade_id`, `data`, `hora_inicio`, `hora_fim`, `local`, `treinador`, `descricao`, `anexo`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-06-27', '21:30:00', '23:00:00', 'Gama Barros', 'Helder Moutinho', 'Seniores - Masculinos\r\nTreino: \r\n10 minutos de aquecimento espelho.', 'anexo_6834ac166bd8e.png', '2025-05-26 17:10:01', '2025-05-27 16:48:30');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `tipo` varchar(20) DEFAULT NULL,
  `data_registo` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_nascimento` date DEFAULT NULL,
  `genero` enum('M','F') DEFAULT NULL,
  `telefone` varchar(9) DEFAULT NULL,
  `nif` varchar(9) DEFAULT NULL,
  `morada` text DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `escalao` varchar(50) DEFAULT NULL,
  `posicao` varchar(50) DEFAULT NULL,
  `numero` int(11) DEFAULT NULL,
  `pe_dominante` varchar(20) DEFAULT NULL,
  `exame_medico_data` date DEFAULT NULL,
  `seguro_desportivo` varchar(50) DEFAULT NULL,
  `numero_socio` int(11) DEFAULT NULL,
  `cip` varchar(20) DEFAULT NULL,
  `modalidade_id` int(11) DEFAULT NULL,
  `escalao_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `nome`, `email`, `password_hash`, `tipo`, `data_registo`, `status`, `data_registro`, `data_nascimento`, `genero`, `telefone`, `nif`, `morada`, `foto_perfil`, `escalao`, `posicao`, `numero`, `pe_dominante`, `exame_medico_data`, `seguro_desportivo`, `numero_socio`, `cip`, `modalidade_id`, `escalao_id`) VALUES
(15, 'Diogo Cardoso Antunes', 'antunesdiogo06@gmail.com', '$2y$10$ZNeNOz2Hiy1q4dAnMGGQWOOx3iQzjzQ5HEEHrVPHqMy/aouOWa/M2', 'admin', '2025-05-19 19:08:05', 'aprovado', '2025-05-19 19:08:05', '2006-04-28', NULL, '912203131', '205680500', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '53', NULL, NULL),
(16, 'Helder Moutinho', 'diogo_antunes06@hotmail.com', '$2y$10$1d1tKrpzv8pqglP77VZYnunHhF074NdlaWPZgulCR4vTxSGf/IH16', 'treinador', '2025-05-19 19:09:52', 'aprovado', '2025-05-19 19:09:52', '2424-04-24', NULL, '912203131', '242423421', NULL, '../uploads/perfil_16_1748439716.png', NULL, NULL, NULL, NULL, NULL, NULL, 1, '	92866', NULL, 7),
(17, 'Pedro Pires', 'pedropires@gmail.com', '$2y$10$MmP2MVjQ79Vp/uiHbqT7QOCFcmG3NKHIUgHjH4bLSrHfHY/IerQGe', 'atleta', '2025-05-23 14:48:59', 'aprovado', '2025-05-23 14:48:59', '2000-03-04', 'M', '920420291', '534652562', 'Rua da Paz nº10 1ESQ', '../uploads/perfil_17_1748286590.png', 'Seniores', 'Guarda-Redes', 1, 'Esquerdo', NULL, NULL, NULL, '235915', 1, 7),
(18, 'Joana Silva', 'joana@gmail.com', '$2y$10$33GJQpHbSeAQ8prcIlzxfuC7P4nv.XZnOsZi1.5NjnnX.TZ/Q6MDi', 'atleta', '2025-05-26 18:47:13', 'aprovado', '2025-05-26 18:47:13', '2000-02-22', NULL, '920420291', '645357324', NULL, '../uploads/perfil_18_1748995585.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '34241', 1, 4),
(19, 'jonas benfica', 'jonas@gmail.com', '$2y$10$assuE.BEf5VG7UXy.RU9fu5vq4Axon1wH5S8uDKEWnajMPbdHxdf2', 'atleta', '2025-05-27 00:37:03', 'aprovado', '2025-05-27 00:37:03', '1990-02-14', NULL, '910240242', '464327257', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '53253256', 1, 6),
(21, 'João Tinoco', 'joaotinoco@gmail.com', '$2y$10$baruPD1Idj7BcASnS55jCewouKkyyzB6ghgnb9ow34pCWdQNaSOk2', 'atleta', '2025-06-12 15:06:14', 'aprovado', '2025-06-12 15:06:14', '1999-02-14', NULL, '902304903', '514514515', 'Rua da Paz nº10 1ESQ', 'uploads/perfil_21_684af7d69153b.jpg', 'Seniores', 'Pivot', 27, 'Direito', NULL, NULL, NULL, '875221', 1, 7),
(24, 'Miguel Silvério', 'miguelsilverio@gmail.com', '$2y$10$LUjkbIIda0Bppt.s4wzcg.jC7Zt4z.2djSVSc6OD4jOHtrtIxPm6y', 'atleta', '2025-06-23 12:20:10', 'aprovado', '2025-06-23 12:20:10', '1904-02-04', NULL, '412414124', '414214124', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '325252', 1, 5),
(26, 'Francisco Nobre', 'franciscokiko.nobre@gmail.com', '$2y$10$Lg1tw5Feg/1cAkzVSwNnROZHcc0Ls98lqpL8wVdteTS3IZrA5icdy', 'atleta', '2025-06-24 23:27:18', 'aprovado', '2025-06-24 23:27:18', '2000-02-24', NULL, '215252315', '452642572', NULL, 'uploads/perfil_26_685b359316fa5.png', 'Juniores', 'Central', 45, 'Direito', NULL, NULL, NULL, '41541', 1, 6);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Índices para tabela `dashboard_info`
--
ALTER TABLE `dashboard_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- Índices para tabela `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `escalao_id` (`escalao_id`),
  ADD KEY `modalidade_id` (`modalidade_id`);

--
-- Índices para tabela `documentos_medicos`
--
ALTER TABLE `documentos_medicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `atleta_id` (`atleta_id`);

--
-- Índices para tabela `equipamentos_atribuidos`
--
ALTER TABLE `equipamentos_atribuidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `atleta_id` (`atleta_id`);

--
-- Índices para tabela `escaloes`
--
ALTER TABLE `escaloes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modalidade_id` (`modalidade_id`);

--
-- Índices para tabela `escaloes_categorias`
--
ALTER TABLE `escaloes_categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escalao_id` (`escalao_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices para tabela `escaloes_utilizadores`
--
ALTER TABLE `escaloes_utilizadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escalao_id` (`escalao_id`),
  ADD KEY `utilizador_id` (`utilizador_id`);

--
-- Índices para tabela `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `jogos`
--
ALTER TABLE `jogos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escalao_id` (`escalao_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices para tabela `mensalidades`
--
ALTER TABLE `mensalidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mensalidade` (`atleta_id`,`mes`,`ano`);

--
-- Índices para tabela `modalidades`
--
ALTER TABLE `modalidades`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `atleta_id` (`atleta_id`);

--
-- Índices para tabela `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- Índices para tabela `permissoes`
--
ALTER TABLE `permissoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `socios_info`
--
ALTER TABLE `socios_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- Índices para tabela `treinos`
--
ALTER TABLE `treinos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modalidade_id` (`modalidade_id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `documentos_medicos`
--
ALTER TABLE `documentos_medicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `equipamentos_atribuidos`
--
ALTER TABLE `equipamentos_atribuidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `escaloes`
--
ALTER TABLE `escaloes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `escaloes_categorias`
--
ALTER TABLE `escaloes_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `escaloes_utilizadores`
--
ALTER TABLE `escaloes_utilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `jogos`
--
ALTER TABLE `jogos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `mensalidades`
--
ALTER TABLE `mensalidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `permissoes`
--
ALTER TABLE `permissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `treinos`
--
ALTER TABLE `treinos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `dashboard_info`
--
ALTER TABLE `dashboard_info`
  ADD CONSTRAINT `dashboard_info_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `documentos_ibfk_2` FOREIGN KEY (`escalao_id`) REFERENCES `escaloes` (`id`),
  ADD CONSTRAINT `documentos_ibfk_3` FOREIGN KEY (`modalidade_id`) REFERENCES `modalidades` (`id`);

--
-- Limitadores para a tabela `documentos_medicos`
--
ALTER TABLE `documentos_medicos`
  ADD CONSTRAINT `documentos_medicos_ibfk_1` FOREIGN KEY (`atleta_id`) REFERENCES `users` (`id`);

--
-- Limitadores para a tabela `equipamentos_atribuidos`
--
ALTER TABLE `equipamentos_atribuidos`
  ADD CONSTRAINT `equipamentos_atribuidos_ibfk_1` FOREIGN KEY (`atleta_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `escaloes`
--
ALTER TABLE `escaloes`
  ADD CONSTRAINT `escaloes_ibfk_1` FOREIGN KEY (`modalidade_id`) REFERENCES `modalidades` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `escaloes_categorias`
--
ALTER TABLE `escaloes_categorias`
  ADD CONSTRAINT `escaloes_categorias_ibfk_1` FOREIGN KEY (`escalao_id`) REFERENCES `escaloes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escaloes_categorias_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `escaloes_utilizadores`
--
ALTER TABLE `escaloes_utilizadores`
  ADD CONSTRAINT `escaloes_utilizadores_ibfk_1` FOREIGN KEY (`escalao_id`) REFERENCES `escaloes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escaloes_utilizadores_ibfk_2` FOREIGN KEY (`utilizador_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `jogos`
--
ALTER TABLE `jogos`
  ADD CONSTRAINT `jogos_ibfk_1` FOREIGN KEY (`escalao_id`) REFERENCES `escaloes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jogos_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `mensalidades`
--
ALTER TABLE `mensalidades`
  ADD CONSTRAINT `mensalidades_ibfk_1` FOREIGN KEY (`atleta_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`atleta_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `socios_info`
--
ALTER TABLE `socios_info`
  ADD CONSTRAINT `socios_info_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `treinos`
--
ALTER TABLE `treinos`
  ADD CONSTRAINT `treinos_ibfk_1` FOREIGN KEY (`modalidade_id`) REFERENCES `modalidades` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
