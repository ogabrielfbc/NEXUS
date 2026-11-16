
-- ==========================================
-- NEXUS TECH - BANCO DE DADOS
-- ==========================================

CREATE DATABASE IF NOT EXISTS nexus_tech
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE nexus_tech;


-- 1. USUARIOS
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- 2. CATEGORIAS
CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;


-- 3. PRODUTOS
CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    categoria_id INT UNSIGNED NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT UNSIGNED NOT NULL DEFAULT 0,
    imagem VARCHAR(255),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (categoria_id)
    REFERENCES categorias(id)
    ON DELETE SET NULL,

    CHECK (preco >= 0)
) ENGINE=InnoDB;


-- 4. PEDIDOS
CREATE TABLE IF NOT EXISTS pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    forma_pagamento ENUM('pix', 'boleto', 'cartao') NOT NULL,
    status ENUM('pendente', 'confirmado', 'cancelado')
    NOT NULL DEFAULT 'pendente',
    total DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE RESTRICT,

    CHECK (total >= 0)
) ENGINE=InnoDB;


-- 5. ITENS DO PEDIDO
CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NULL,
    nome_produto VARCHAR(150) NOT NULL,
    quantidade INT UNSIGNED NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (pedido_id)
    REFERENCES pedidos(id)
    ON DELETE CASCADE,

    FOREIGN KEY (produto_id)
    REFERENCES produtos(id)
    ON DELETE SET NULL,

    CHECK (quantidade > 0),
    CHECK (preco_unitario >= 0)
) ENGINE=InnoDB;


-- ==========================================
-- CADASTRO DAS CATEGORIAS
-- ==========================================

INSERT IGNORE INTO categorias (nome, slug) VALUES
('Teclados', 'teclados'),
('Mouses', 'mouses'),
('Headsets', 'headsets'),
('Monitores', 'monitores');


-- ==========================================
-- CADASTRO DOS PRODUTOS DE TESTE
-- ==========================================

INSERT INTO produtos
(codigo, categoria_id, nome, descricao, preco, estoque, imagem, ativo)

SELECT
'TEC-001',
id,
'Teclado Mecanico Nexus K80',
'Teclado mecanico para setups e jogos.',
249.90,
15,
NULL,
1
FROM categorias
WHERE slug = 'teclados'
AND NOT EXISTS (
    SELECT 1 FROM produtos WHERE codigo = 'TEC-001'
);


INSERT INTO produtos
(codigo, categoria_id, nome, descricao, preco, estoque, imagem, ativo)

SELECT
'MOU-001',
id,
'Mouse Gamer Nexus M5',
'Mouse para uso diario e jogos.',
119.90,
20,
NULL,
1
FROM categorias
WHERE slug = 'mouses'
AND NOT EXISTS (
    SELECT 1 FROM produtos WHERE codigo = 'MOU-001'
);


INSERT INTO produtos
(codigo, categoria_id, nome, descricao, preco, estoque, imagem, ativo)

SELECT
'HEA-001',
id,
'Headset Nexus H7',
'Headset para jogos e comunicacao.',
189.90,
10,
NULL,
1
FROM categorias
WHERE slug = 'headsets'
AND NOT EXISTS (
    SELECT 1 FROM produtos WHERE codigo = 'HEA-001'
);


INSERT INTO produtos
(codigo, categoria_id, nome, descricao, preco, estoque, imagem, ativo)

SELECT
'MON-001',
id,
'Monitor Nexus Vision 24',
'Monitor para trabalho e entretenimento.',
699.90,
8,
NULL,
1
FROM categorias
WHERE slug = 'monitores'
AND NOT EXISTS (
    SELECT 1 FROM produtos WHERE codigo = 'MON-001'
);
