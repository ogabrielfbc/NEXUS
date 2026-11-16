-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/11/2026 às 04:45
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `nexus_tech`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `slug`) VALUES
(1, 'Teclados', 'teclados'),
(2, 'Mouses', 'mouses'),
(3, 'Headsets', 'headsets'),
(4, 'Monitores', 'monitores'),
(5, 'Consoles', 'consoles'),
(6, 'Placas de vídeo', 'placas-de-video'),
(7, 'Notebooks', 'notebooks');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_pedido`
--

CREATE TABLE `itens_pedido` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `produto_id` int(10) UNSIGNED DEFAULT NULL,
  `nome_produto` varchar(150) NOT NULL,
  `quantidade` int(10) UNSIGNED NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `data_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `forma_pagamento` enum('pix','boleto','cartao') NOT NULL,
  `status` enum('pendente','confirmado','cancelado') NOT NULL DEFAULT 'pendente',
  `total` decimal(10,2) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(40) NOT NULL,
  `categoria_id` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `estoque` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `imagem` varchar(255) DEFAULT NULL,
  `modelo_3d` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `codigo`, `categoria_id`, `nome`, `descricao`, `preco`, `estoque`, `imagem`, `modelo_3d`, `ativo`, `criado_em`) VALUES
(2, 'MOU-001', 2, 'Logitech G203 LIGHTSYNC', 'Mouse gamer Logitech G203 LIGHTSYNC com sensor óptico para jogos, iluminação RGB personalizável e seis botões programáveis. Possui conexão USB com fio e configuração pelo Logitech G HUB.', 129.90, 20, 'assets/img/logitech-g203.png', 'assets/models/mouse/logitech-g203.glb', 1, '2026-11-12 00:23:06'),
(17, 'MOU-002', 2, 'Dell Black Single Tone', 'Mouse Dell de acabamento preto e visual discreto, indicado para produtividade, estudos e uso diário. Especificações exatas dependem da variante.', 89.90, 10, 'assets/img/dell-black-single-tone.png', 'assets/models/mouse/Dell_Black_Single_Tone.glb', 1, '2026-11-12 05:29:36'),
(18, 'MOU-003', 2, 'Ice Claw Mouse', 'Mouse com design gamer e formato voltado ao uso em computador. Características específicas dependem do modelo comercial.', 119.90, 10, 'assets/img/ice-claw-mouse.png', 'assets/models/mouse/ice_claw_mouse.glb', 1, '2026-11-12 05:29:36'),
(19, 'MOU-004', 2, 'Razer Naga Trinity', 'Mouse gamer modular da Razer, com placas laterais intercambiáveis para diferentes configurações de botões. Possui iluminação RGB e conexão USB.', 499.90, 8, 'assets/img/razer-naga-trinity.png', 'assets/models/mouse/Razer_Naga_Trinity.glb', 1, '2026-11-12 05:29:36'),
(20, 'TEC-001', 1, 'Gigabyte Aorus K1 RGB', 'Teclado gamer da linha AORUS K1, com iluminação RGB e construção voltada para jogos e digitação.', 499.90, 8, 'assets/img/gigabyte-aorus-k1-rgb.png', 'assets/models/teclado/Gigabyte_Aorus_K1_RGB.glb', 1, '2026-11-12 05:29:36'),
(21, 'TEC-002', 1, 'HyperX Alloy FPS Pro', 'Teclado mecânico compacto da HyperX, projetado para jogos e para ocupar menos espaço na mesa.', 399.90, 8, 'assets/img/hyperx-alloy-fps-pro.png', 'assets/models/teclado/HyperX_Alloy_FPS_Pro.glb', 1, '2026-11-12 05:29:36'),
(23, 'TEC-004', 1, 'Razer Huntsman V2 TKL', 'Teclado gamer tenkeyless da Razer, sem teclado numérico, com iluminação Chroma RGB.', 799.90, 6, 'assets/img/razer-huntsman-v2-tkl.png', 'assets/models/teclado/Razer_Huntsman_V2_TKL.glb', 1, '2026-11-12 05:29:36'),
(24, 'HEA-001', 3, 'Razer Kraken Pro V2', 'Headset gamer Razer Kraken Pro V2, com estrutura over-ear e microfone integrado para jogos e comunicação.', 399.90, 8, 'assets/img/razer-kraken-pro-v2.png', 'assets/models/fones/Razer_Kraken_Pro_V2.glb', 1, '2026-11-12 05:29:36'),
(25, 'HEA-002', 3, 'Redragon Lumia 2', 'Headset gamer Redragon com almofadas over-ear e microfone integrado. Modelo comercial exato não identificado.', 179.90, 10, 'assets/img/redragon-headset.png', 'assets/models/fones/red_dragon_headset.glb', 1, '2026-11-12 05:29:36'),
(27, 'MON-001', 4, 'Acer K222HQL', 'Monitor Acer da família K222HQL, com tela de aproximadamente 21,5 polegadas e resolução Full HD nas versões compatíveis.', 499.90, 6, 'assets/img/Monitoracer-imagem/2.png', 'assets/models/Monitor/Acer_K222HQ.glb', 1, '2026-11-12 05:29:36'),
(28, 'MON-002', 4, 'Samsung 24 Curved Display', 'Monitor Samsung curvo de 24 polegadas, indicado para entretenimento, produtividade e setups domésticos.', 899.90, 6, 'assets/img/Samsung24-imagem/2.png', 'assets/models/Monitor/Samsung24.glb', 1, '2026-11-12 05:29:36'),
(29, 'NOT-001', 7, 'ASUS Notebook', 'Notebook ASUS para estudos, produtividade e tarefas diárias. Configuração exata não identificada pelo arquivo 3D.', 3299.90, 4, 'assets/img/ASUS Laptop-imagem/1.png', 'assets/models/notebook/asus_notebook.glb', 1, '2026-11-12 05:29:36'),
(30, 'NOT-002', 7, 'HP Laptop', 'Notebook HP para estudos, navegação e atividades de escritório. Configuração específica não identificada.', 2899.90, 4, 'assets/img/hp-imagem/4.png', 'assets/models/notebook/hp_laptop.glb', 1, '2026-11-12 05:29:36'),
(31, 'NOT-003', 7, 'Lenovo ThinkPad Laptop', 'Notebook da linha Lenovo ThinkPad, voltado à produtividade e ao uso profissional.', 4499.90, 4, 'assets/img/notebook lenovo tikinpad- imagem/1.png', 'assets/models/notebook/lenovo_thinkpad_laptop.glb', 1, '2026-11-12 05:29:36'),
(32, 'NOT-004', 7, 'MacBook Pro 2021', 'Apple MacBook Pro de 2021, disponível em configurações com chips M1 Pro ou M1 Max.', 8999.90, 3, 'assets/img/macbook-imagem/1.png', 'assets/models/notebook/macbook2021.glb', 1, '2026-11-12 05:29:36'),
(33, 'GPU-001', 6, 'ASUS ROG RTX 5070 OC', 'Placa de vídeo ASUS ROG GeForce RTX 5070 com perfil de overclock de fábrica, voltada a jogos e criação de conteúdo.', 6499.90, 3, 'assets/img/5070-imagem/1.png\r\n', 'assets/models/placadevideo/asus_rog_rtx_5070_oc_pbr.glb', 1, '2026-11-12 05:29:36'),
(34, 'GPU-002', 6, 'Gigabyte GeForce GTX 1660', 'Placa de vídeo Gigabyte GeForce GTX 1660, indicada para jogos em Full HD e uso geral.', 1099.90, 4, 'assets/img/1660-imagem/2.png', 'assets/models/placadevideo/geforce_gtx_1660_gigabyte.glb', 1, '2026-11-12 05:29:36'),
(35, 'GPU-003', 6, 'AMD Radeon RX 6700 XT', 'Placa de vídeo AMD Radeon RX 6700 XT com 12 GB de memória GDDR6, voltada principalmente para jogos em 1440p.', 2399.90, 4, 'assets/img/6700-imagem/3.png', 'assets/models/placadevideo/gpu_amd_rx_6700_xt.glb', 1, '2026-11-12 05:29:36'),
(36, 'GPU-004', 6, 'NVIDIA GeForce RTX 3090', 'Placa de vídeo NVIDIA GeForce RTX 3090 com 24 GB de memória GDDR6X, voltada a jogos e aplicações profissionais.', 5999.90, 2, 'assets/img/3090-imagem/2.png', 'assets/models/placadevideo/nvidia_geforce_rtx_3090.glb', 1, '2026-11-12 05:29:36'),
(37, 'GPU-005', 6, 'NVIDIA GeForce RTX 5090 Founders Edition', 'Placa de vídeo NVIDIA GeForce RTX 5090 Founders Edition com 32 GB de memória GDDR7, voltada ao segmento entusiasta.', 19999.90, 1, 'assets/img/5090-imagem/1.png', 'assets/models/placadevideo/nvidia_rtx_5090_founders_edition_-_free_download.glb', 1, '2026-11-12 05:29:36'),
(38, 'CON-001', 5, 'PlayStation 4', 'Console Sony PlayStation 4 para jogos e entretenimento. Armazenamento e acessórios dependem da unidade.', 1499.90, 0, 'assets\\img\\ps4-imagem/1.png', 'assets/models/Consoles/playstation_4.glb', 1, '2026-11-12 05:29:36'),
(39, 'CON-002', 5, 'PlayStation 5 Slim', 'Console Sony PlayStation 5 Slim, versão de dimensões reduzidas da família PS5. Confirme se a unidade possui leitor de disco.', 3799.90, 3, 'assets\\img\\ps5-imagem/1.png', 'assets/models/Consoles/playstation5_slim.glb', 1, '2026-11-12 05:29:36'),
(40, 'CON-003', 5, 'Sony PlayStation 2', 'Console Sony PlayStation 2, clássico da história dos videogames. Acompanha acessórios conforme a unidade anunciada.', 499.90, 3, 'assets\\img\\ps2-imagem/1.png', 'assets/models/Consoles/sony_playstation_2.glb', 1, '2026-11-12 05:29:36'),
(41, 'CON-004', 5, 'Xbox One', 'Console Microsoft Xbox One para jogos e entretenimento. Armazenamento e acessórios variam conforme a unidade.', 999.90, 3, 'assets\\img\\xboxone-imagem/2.png', 'assets/models/Consoles/xbox_one.glb', 1, '2026-11-12 05:29:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_especificacoes`
--

CREATE TABLE `produto_especificacoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `produto_id` int(10) UNSIGNED NOT NULL,
  `caracteristica` varchar(100) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produto_especificacoes`
--

INSERT INTO `produto_especificacoes` (`id`, `produto_id`, `caracteristica`, `valor`, `ordem`) VALUES
(1, 2, 'Marca', 'Logitech G', 1),
(2, 2, 'Modelo', 'G203 LIGHTSYNC', 2),
(3, 2, 'Categoria', 'Mouse gamer', 3),
(4, 2, 'Sensor', 'Óptico para jogos', 4),
(5, 2, 'Sensibilidade', '200 a 8.000 DPI', 5),
(6, 2, 'Botões', '6 botões programáveis', 6),
(7, 2, 'Conexão', 'USB com fio', 7),
(8, 2, 'Iluminação', 'RGB LIGHTSYNC', 8),
(9, 2, 'Personalização RGB', 'Aproximadamente 16,8 milhões de cores', 9),
(10, 2, 'Software', 'Logitech G HUB', 10),
(11, 2, 'Predefinições de DPI', 'Até 5 configurações', 11),
(58, 17, 'Marca', 'Dell', 1),
(59, 17, 'Cor', 'Preto', 2),
(60, 17, 'Conexão', 'A confirmar', 3),
(61, 17, 'Sensor', 'A confirmar', 4),
(62, 18, 'Modelo', 'Ice Claw', 1),
(63, 18, 'Tipo', 'Mouse', 2),
(64, 18, 'DPI', 'A confirmar', 3),
(65, 18, 'Conexão', 'A confirmar', 4),
(66, 19, 'Marca', 'Razer', 1),
(67, 19, 'Sensor', 'Óptico 5G', 2),
(68, 19, 'Sensibilidade', 'Até 16.000 DPI', 3),
(69, 19, 'Botões laterais', 'Placas intercambiáveis de 2, 7 e 12 botões', 4),
(70, 19, 'Iluminação', 'Razer Chroma RGB', 5),
(71, 19, 'Conexão', 'USB com fio', 6),
(72, 20, 'Marca', 'Gigabyte AORUS', 1),
(73, 20, 'Tipo', 'Mecânico', 2),
(74, 20, 'Iluminação', 'RGB', 3),
(75, 20, 'Layout', 'A confirmar', 4),
(76, 21, 'Marca', 'HyperX', 1),
(77, 21, 'Formato', 'Tenkeyless', 2),
(78, 21, 'Conexão', 'USB com fio', 3),
(79, 21, 'Switch', 'Cherry MX, conforme versão', 4),
(84, 23, 'Marca', 'Razer', 1),
(85, 23, 'Formato', 'Tenkeyless', 2),
(86, 23, 'Iluminação', 'Razer Chroma RGB', 3),
(87, 23, 'Switch', 'Razer Optical, conforme versão', 4),
(88, 24, 'Marca', 'Razer', 1),
(89, 24, 'Formato', 'Over-ear', 2),
(90, 24, 'Microfone', 'Integrado', 3),
(91, 24, 'Conexão', 'P2, conforme versão', 4),
(92, 25, 'Marca', 'Redragon', 1),
(93, 25, 'Formato', 'Over-ear, estimado', 2),
(94, 25, 'Microfone', 'Integrado, estimado', 3),
(95, 25, 'Modelo exato', 'A confirmar', 4),
(100, 27, 'Marca', 'Acer', 1),
(101, 27, 'Tamanho', '21,5 polegadas', 2),
(102, 27, 'Resolução', 'Full HD 1920 x 1080, conforme versão', 3),
(103, 27, 'Conexões', 'A confirmar', 4),
(104, 28, 'Marca', 'Samsung', 1),
(105, 28, 'Tamanho', '24 polegadas', 2),
(106, 28, 'Tela', 'Curva', 3),
(107, 28, 'Resolução', 'A confirmar', 4),
(108, 29, 'Marca', 'ASUS', 1),
(109, 29, 'Categoria', 'Notebook', 2),
(110, 29, 'Processador', 'A confirmar', 3),
(111, 29, 'Memória RAM', 'A confirmar', 4),
(112, 29, 'Armazenamento', 'A confirmar', 5),
(113, 30, 'Marca', 'HP', 1),
(114, 30, 'Categoria', 'Notebook', 2),
(115, 30, 'Processador', 'A confirmar', 3),
(116, 30, 'Memória RAM', 'A confirmar', 4),
(117, 30, 'Armazenamento', 'A confirmar', 5),
(118, 31, 'Marca', 'Lenovo', 1),
(119, 31, 'Linha', 'ThinkPad', 2),
(120, 31, 'Processador', 'A confirmar', 3),
(121, 31, 'Memória RAM', 'A confirmar', 4),
(122, 31, 'Armazenamento', 'A confirmar', 5),
(123, 32, 'Marca', 'Apple', 1),
(124, 32, 'Ano', '2021', 2),
(125, 32, 'Chip', 'M1 Pro ou M1 Max, conforme configuração', 3),
(126, 32, 'Tela', '14 ou 16 polegadas', 4),
(127, 33, 'Marca', 'ASUS ROG', 1),
(128, 33, 'GPU', 'GeForce RTX 5070', 2),
(129, 33, 'Memória', '12 GB GDDR7', 3),
(130, 33, 'Perfil', 'Overclock de fábrica, conforme versão', 4),
(131, 34, 'Marca', 'Gigabyte', 1),
(132, 34, 'GPU', 'GeForce GTX 1660', 2),
(133, 34, 'Memória', '6 GB GDDR5', 3),
(134, 34, 'Arquitetura', 'Turing', 4),
(135, 35, 'Marca', 'AMD', 1),
(136, 35, 'GPU', 'Radeon RX 6700 XT', 2),
(137, 35, 'Memória', '12 GB GDDR6', 3),
(138, 35, 'Arquitetura', 'RDNA 2', 4),
(139, 36, 'Marca', 'NVIDIA', 1),
(140, 36, 'GPU', 'GeForce RTX 3090', 2),
(141, 36, 'Memória', '24 GB GDDR6X', 3),
(142, 36, 'Arquitetura', 'Ampere', 4),
(143, 37, 'Marca', 'NVIDIA Founders Edition', 1),
(144, 37, 'GPU', 'GeForce RTX 5090', 2),
(145, 37, 'Memória', '32 GB GDDR7', 3),
(146, 37, 'Arquitetura', 'Blackwell', 4),
(147, 38, 'Marca', 'Sony', 1),
(148, 38, 'Modelo', 'PlayStation 4', 2),
(149, 38, 'Armazenamento', 'A confirmar', 3),
(150, 38, 'Acessórios', 'A confirmar', 4),
(151, 39, 'Marca', 'Sony', 1),
(152, 39, 'Modelo', 'PlayStation 5 Slim', 2),
(153, 39, 'Armazenamento', 'SSD interno', 3),
(154, 39, 'Edição', 'Com leitor ou Digital, conforme versão', 4),
(155, 40, 'Marca', 'Sony', 1),
(156, 40, 'Modelo', 'PlayStation 2', 2),
(157, 40, 'Mídia', 'DVD e CD compatíveis', 3),
(158, 40, 'Acessórios', 'A confirmar', 4),
(159, 41, 'Marca', 'Microsoft', 1),
(160, 41, 'Modelo', 'Xbox One', 2),
(161, 41, 'Armazenamento', 'A confirmar', 3),
(162, 41, 'Mídia', 'Física e digital, conforme versão', 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('cliente','admin') NOT NULL DEFAULT 'cliente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Índices de tabela `itens_pedido`
--
ALTER TABLE `itens_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices de tabela `produto_especificacoes`
--
ALTER TABLE `produto_especificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produto_especificacoes` (`produto_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `itens_pedido`
--
ALTER TABLE `itens_pedido`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produto_especificacoes`
--
ALTER TABLE `produto_especificacoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `itens_pedido`
--
ALTER TABLE `itens_pedido`
  ADD CONSTRAINT `itens_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itens_pedido_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `produto_especificacoes`
--
ALTER TABLE `produto_especificacoes`
  ADD CONSTRAINT `fk_produto_especificacoes` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
