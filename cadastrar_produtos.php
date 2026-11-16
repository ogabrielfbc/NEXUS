
<?php

$pdo = require __DIR__ . '/config/database.php';

// Categorias esperadas no banco
$categorias = [
    'Mouse' => ['mouse'],
    'Teclado' => ['teclado'],
    'Headset' => ['headset', 'fone'],
    'Monitor' => ['monitor'],
    'Notebook' => ['notebook', 'laptop'],
    'Placa de vídeo' => ['placa', 'vídeo', 'video'],
    'Console' => ['console', 'videogame']
];


// Produtos do catálogo
$produtos = [

    // MOUSES
    [
        'codigo' => 'MOU-002',
        'categoria' => 'Mouse',
        'nome' => 'Dell Black Single Tone',
        'descricao' => 'Mouse Dell de acabamento preto e visual discreto, indicado para produtividade, estudos e uso diário. Especificações exatas dependem da variante.',
        'preco' => 89.90,
        'estoque' => 10,
        'imagem' => 'assets/img/dell-black-single-tone.png',
        'modelo' => 'assets/models/mouse/Dell_Black_Single_Tone.glb',
        'specs' => [
            'Marca' => 'Dell',
            'Cor' => 'Preto',
            'Conexão' => 'A confirmar',
            'Sensor' => 'A confirmar'
        ]
    ],

    [
        'codigo' => 'MOU-003',
        'categoria' => 'Mouse',
        'nome' => 'Ice Claw Mouse',
        'descricao' => 'Mouse com design gamer e formato voltado ao uso em computador. Características específicas dependem do modelo comercial.',
        'preco' => 119.90,
        'estoque' => 10,
        'imagem' => 'assets/img/ice-claw-mouse.png',
        'modelo' => 'assets/models/mouse/ice_claw_mouse.glb',
        'specs' => [
            'Modelo' => 'Ice Claw',
            'Tipo' => 'Mouse',
            'DPI' => 'A confirmar',
            'Conexão' => 'A confirmar'
        ]
    ],

    [
        'codigo' => 'MOU-004',
        'categoria' => 'Mouse',
        'nome' => 'Razer Naga Trinity',
        'descricao' => 'Mouse gamer modular da Razer, com placas laterais intercambiáveis para diferentes configurações de botões. Possui iluminação RGB e conexão USB.',
        'preco' => 499.90,
        'estoque' => 8,
        'imagem' => 'assets/img/razer-naga-trinity.png',
        'modelo' => 'assets/models/mouse/Razer_Naga_Trinity.glb',
        'specs' => [
            'Marca' => 'Razer',
            'Sensor' => 'Óptico 5G',
            'Sensibilidade' => 'Até 16.000 DPI',
            'Botões laterais' => 'Placas intercambiáveis de 2, 7 e 12 botões',
            'Iluminação' => 'Razer Chroma RGB',
            'Conexão' => 'USB com fio'
        ]
    ],

    // TECLADOS
    [
        'codigo' => 'TEC-001',
        'categoria' => 'Teclado',
        'nome' => 'Gigabyte Aorus K1 RGB',
        'descricao' => 'Teclado gamer da linha AORUS K1, com iluminação RGB e construção voltada para jogos e digitação.',
        'preco' => 499.90,
        'estoque' => 8,
        'imagem' => 'assets/img/gigabyte-aorus-k1-rgb.png',
        'modelo' => 'assets/models/teclado/Gigabyte_Aorus_K1_RGB.glb',
        'specs' => [
            'Marca' => 'Gigabyte AORUS',
            'Tipo' => 'Mecânico',
            'Iluminação' => 'RGB',
            'Layout' => 'A confirmar'
        ]
    ],

    [
        'codigo' => 'TEC-002',
        'categoria' => 'Teclado',
        'nome' => 'HyperX Alloy FPS Pro',
        'descricao' => 'Teclado mecânico compacto da HyperX, projetado para jogos e para ocupar menos espaço na mesa.',
        'preco' => 399.90,
        'estoque' => 8,
        'imagem' => 'assets/img/hyperx-alloy-fps-pro.png',
        'modelo' => 'assets/models/teclado/HyperX+Alloy+Origins+60 (2).glb',
        'specs' => [
            'Marca' => 'HyperX',
            'Formato' => 'Tenkeyless',
            'Conexão' => 'USB com fio',
            'Switch' => 'Cherry MX, conforme versão'
        ]
    ],


    [
        'codigo' => 'TEC-004',
        'categoria' => 'Teclado',
        'nome' => 'Razer Huntsman V2 TKL',
        'descricao' => 'Teclado gamer tenkeyless da Razer, sem teclado numérico, com iluminação Chroma RGB.',
        'preco' => 799.90,
        'estoque' => 6,
        'imagem' => 'assets/img/razer-huntsman-v2-tkl.png',
        'modelo' => 'assets/models/teclado/Razer_Huntsman_V2_TKL.glb',
        'specs' => [
            'Marca' => 'Razer',
            'Formato' => 'Tenkeyless',
            'Iluminação' => 'Razer Chroma RGB',
            'Switch' => 'Razer Optical, conforme versão'
        ]
    ],

    // HEADSETS
    [
        'codigo' => 'HEA-001',
        'categoria' => 'Headset',
        'nome' => 'Razer Kraken Pro V2',
        'descricao' => 'Headset gamer Razer Kraken Pro V2, com estrutura over-ear e microfone integrado para jogos e comunicação.',
        'preco' => 399.90,
        'estoque' => 8,
        'imagem' => 'assets/img/razer-kraken-pro-v2.png',
        'modelo' => 'assets/models/fones/Razer_Kraken_Pro_V2.glb',
        'specs' => [
            'Marca' => 'Razer',
            'Formato' => 'Over-ear',
            'Microfone' => 'Integrado',
            'Conexão' => 'P2, conforme versão'
        ]
    ],

    [
        'codigo' => 'HEA-002',
        'categoria' => 'Headset',
        'nome' => 'Redragon Headset',
        'descricao' => 'Headset gamer Redragon com almofadas over-ear e microfone integrado. Modelo comercial exato não identificado.',
        'preco' => 179.90,
        'estoque' => 10,
        'imagem' => 'assets/img/redragon-headset.png',
        'modelo' => 'assets/models/fones/red_dragon_headset.glb',
        'specs' => [
            'Marca' => 'Redragon',
            'Formato' => 'Over-ear, estimado',
            'Microfone' => 'Integrado, estimado',
            'Modelo exato' => 'A confirmar'
        ]
    ],

 

    // MONITORES
    [
        'codigo' => 'MON-001',
        'categoria' => 'Monitor',
        'nome' => 'Acer K222HQL',
        'descricao' => 'Monitor Acer da família K222HQL, com tela de aproximadamente 21,5 polegadas e resolução Full HD nas versões compatíveis.',
        'preco' => 499.90,
        'estoque' => 6,
        'imagem' => 'assets/img/acer-k222hql.png',
        'modelo' => 'assets/models/Monitor/Acer_K222HQ.glb',
        'specs' => [
            'Marca' => 'Acer',
            'Tamanho' => '21,5 polegadas',
            'Resolução' => 'Full HD 1920 x 1080, conforme versão',
            'Conexões' => 'A confirmar'
        ]
    ],

    [
        'codigo' => 'MON-002',
        'categoria' => 'Monitor',
        'nome' => 'Samsung 24 Curved Display',
        'descricao' => 'Monitor Samsung curvo de 24 polegadas, indicado para entretenimento, produtividade e setups domésticos.',
        'preco' => 899.90,
        'estoque' => 6,
        'imagem' => 'assets/img/samsung-24-curved-display.png',
        'modelo' => 'assets/models/Monitor/Samsung_24_Curved_Display.glb',
        'specs' => [
            'Marca' => 'Samsung',
            'Tamanho' => '24 polegadas',
            'Tela' => 'Curva',
            'Resolução' => 'A confirmar'
        ]
    ],

    // NOTEBOOKS
    [
        'codigo' => 'NOT-001',
        'categoria' => 'Notebook',
        'nome' => 'ASUS Notebook',
        'descricao' => 'Notebook ASUS para estudos, produtividade e tarefas diárias. Configuração exata não identificada pelo arquivo 3D.',
        'preco' => 3299.90,
        'estoque' => 4,
        'imagem' => 'assets/img/asus-notebook.png',
        'modelo' => 'assets/models/notebook/asus_notebook.glb',
        'specs' => [
            'Marca' => 'ASUS',
            'Categoria' => 'Notebook',
            'Processador' => 'A confirmar',
            'Memória RAM' => 'A confirmar',
            'Armazenamento' => 'A confirmar'
        ]
    ],

    [
        'codigo' => 'NOT-002',
        'categoria' => 'Notebook',
        'nome' => 'HP Laptop',
        'descricao' => 'Notebook HP para estudos, navegação e atividades de escritório. Configuração específica não identificada.',
        'preco' => 2899.90,
        'estoque' => 4,
        'imagem' => 'assets/img/hp-laptop.png',
        'modelo' => 'assets/models/notebook/hp_laptop.glb',
        'specs' => [
            'Marca' => 'HP',
            'Categoria' => 'Notebook',
            'Processador' => 'A confirmar',
            'Memória RAM' => 'A confirmar',
            'Armazenamento' => 'A confirmar'
        ]
    ],

    [
        'codigo' => 'NOT-003',
        'categoria' => 'Notebook',
        'nome' => 'Lenovo ThinkPad Laptop',
        'descricao' => 'Notebook da linha Lenovo ThinkPad, voltado à produtividade e ao uso profissional.',
        'preco' => 4499.90,
        'estoque' => 4,
        'imagem' => 'assets/img/lenovo-thinkpad-laptop.png',
        'modelo' => 'assets/models/notebook/lenovo_thinkpad_laptop.glb',
        'specs' => [
            'Marca' => 'Lenovo',
            'Linha' => 'ThinkPad',
            'Processador' => 'A confirmar',
            'Memória RAM' => 'A confirmar',
            'Armazenamento' => 'A confirmar'
        ]
    ],

    [
        'codigo' => 'NOT-004',
        'categoria' => 'Notebook',
        'nome' => 'MacBook Pro 2021',
        'descricao' => 'Apple MacBook Pro de 2021, disponível em configurações com chips M1 Pro ou M1 Max.',
        'preco' => 8999.90,
        'estoque' => 3,
        'imagem' => 'assets/img/macbook-pro-2021.png',
        'modelo' => 'assets/models/notebook/macbook2021.glb',
        'specs' => [
            'Marca' => 'Apple',
            'Ano' => '2021',
            'Chip' => 'M1 Pro ou M1 Max, conforme configuração',
            'Tela' => '14 ou 16 polegadas'
        ]
    ],

    // PLACAS DE VÍDEO
    [
        'codigo' => 'GPU-001',
        'categoria' => 'Placa de vídeo',
        'nome' => 'ASUS ROG RTX 5070 OC',
        'descricao' => 'Placa de vídeo ASUS ROG GeForce RTX 5070 com perfil de overclock de fábrica, voltada a jogos e criação de conteúdo.',
        'preco' => 6499.90,
        'estoque' => 3,
        'imagem' => 'assets/img/asus-rog-rtx-5070-oc.png',
        'modelo' => 'assets/models/placadevideo/asus_rog_rtx_5070_oc_pbr.glb',
        'specs' => [
            'Marca' => 'ASUS ROG',
            'GPU' => 'GeForce RTX 5070',
            'Memória' => '12 GB GDDR7',
            'Perfil' => 'Overclock de fábrica, conforme versão'
        ]
    ],

    [
        'codigo' => 'GPU-002',
        'categoria' => 'Placa de vídeo',
        'nome' => 'Gigabyte GeForce GTX 1660',
        'descricao' => 'Placa de vídeo Gigabyte GeForce GTX 1660, indicada para jogos em Full HD e uso geral.',
        'preco' => 1099.90,
        'estoque' => 4,
        'imagem' => 'assets/img/gigabyte-gtx-1660.png',
        'modelo' => 'assets/models/placadevideo/geforce_gtx_1660_gigabyte.glb',
        'specs' => [
            'Marca' => 'Gigabyte',
            'GPU' => 'GeForce GTX 1660',
            'Memória' => '6 GB GDDR5',
            'Arquitetura' => 'Turing'
        ]
    ],

    [
        'codigo' => 'GPU-003',
        'categoria' => 'Placa de vídeo',
        'nome' => 'AMD Radeon RX 6700 XT',
        'descricao' => 'Placa de vídeo AMD Radeon RX 6700 XT com 12 GB de memória GDDR6, voltada principalmente para jogos em 1440p.',
        'preco' => 2399.90,
        'estoque' => 4,
        'imagem' => 'assets/img/amd-rx-6700-xt.png',
        'modelo' => 'assets/models/placadevideo/gpu_amd_rx_6700_xt.glb',
        'specs' => [
            'Marca' => 'AMD',
            'GPU' => 'Radeon RX 6700 XT',
            'Memória' => '12 GB GDDR6',
            'Arquitetura' => 'RDNA 2'
        ]
    ],

    [
        'codigo' => 'GPU-004',
        'categoria' => 'Placa de vídeo',
        'nome' => 'NVIDIA GeForce RTX 3090',
        'descricao' => 'Placa de vídeo NVIDIA GeForce RTX 3090 com 24 GB de memória GDDR6X, voltada a jogos e aplicações profissionais.',
        'preco' => 5999.90,
        'estoque' => 2,
        'imagem' => 'assets/img/nvidia-rtx-3090.png',
        'modelo' => 'assets/models/placadevideo/nvidia_geforce_rtx_3090.glb',
        'specs' => [
            'Marca' => 'NVIDIA',
            'GPU' => 'GeForce RTX 3090',
            'Memória' => '24 GB GDDR6X',
            'Arquitetura' => 'Ampere'
        ]
    ],

    [
        'codigo' => 'GPU-005',
        'categoria' => 'Placa de vídeo',
        'nome' => 'NVIDIA GeForce RTX 5090 Founders Edition',
        'descricao' => 'Placa de vídeo NVIDIA GeForce RTX 5090 Founders Edition com 32 GB de memória GDDR7, voltada ao segmento entusiasta.',
        'preco' => 19999.90,
        'estoque' => 1,
        'imagem' => 'assets/img/nvidia-rtx-5090-founders-edition.png',
        'modelo' => null,
        'modelo_prefixo' => 'nvidia_rtx_5090_founders_edition',
        'pasta_modelo' => 'assets/models/placadevideo/',
        'specs' => [
            'Marca' => 'NVIDIA Founders Edition',
            'GPU' => 'GeForce RTX 5090',
            'Memória' => '32 GB GDDR7',
            'Arquitetura' => 'Blackwell'
        ]
    ],

    // CONSOLES
    [
        'codigo' => 'CON-001',
        'categoria' => 'Console',
        'nome' => 'PlayStation 4',
        'descricao' => 'Console Sony PlayStation 4 para jogos e entretenimento. Armazenamento e acessórios dependem da unidade.',
        'preco' => 1499.90,
        'estoque' => 3,
        'imagem' => 'assets\img\ps4-imagem/1.png',
        'modelo' => 'assets/models/Consoles/playstation_4.glb',
        'specs' => [
            'Marca' => 'Sony',
            'Modelo' => 'PlayStation 4',
            'Armazenamento' => 'A confirmar',
            'Acessórios' => 'A confirmar'
        ]
    ],

    [
        'codigo' => 'CON-002',
        'categoria' => 'Console',
        'nome' => 'PlayStation 5 Slim',
        'descricao' => 'Console Sony PlayStation 5 Slim, versão de dimensões reduzidas da família PS5. Confirme se a unidade possui leitor de disco.',
        'preco' => 3799.90,
        'estoque' => 3,
        'imagem' => 'assets/img/playstation-5-slim.png',
        'modelo' => 'assets/models/Consoles/playstation5_slim.glb',
        'specs' => [
            'Marca' => 'Sony',
            'Modelo' => 'PlayStation 5 Slim',
            'Armazenamento' => 'SSD interno',
            'Edição' => 'Com leitor ou Digital, conforme versão'
        ]
    ],

    [
        'codigo' => 'CON-003',
        'categoria' => 'Console',
        'nome' => 'Sony PlayStation 2',
        'descricao' => 'Console Sony PlayStation 2, clássico da história dos videogames. Acompanha acessórios conforme a unidade anunciada.',
        'preco' => 499.90,
        'estoque' => 3,
        'imagem' => 'assets/img/sony-playstation-2.png',
        'modelo' => 'assets/models/Consoles/sony_playstation_2.glb',
        'specs' => [
            'Marca' => 'Sony',
            'Modelo' => 'PlayStation 2',
            'Mídia' => 'DVD e CD compatíveis',
            'Acessórios' => 'A confirmar'
        ]
    ],

    [
        'codigo' => 'CON-004',
        'categoria' => 'Console',
        'nome' => 'Xbox One',
        'descricao' => 'Console Microsoft Xbox One para jogos e entretenimento. Armazenamento e acessórios variam conforme a unidade.',
        'preco' => 999.90,
        'estoque' => 3,
        'imagem' => 'assets\img\xboxone-imagem/1.png',
        'modelo' => 'assets/models/Consoles/xbox_one.glb',
        'specs' => [
            'Marca' => 'Microsoft',
            'Modelo' => 'Xbox One',
            'Armazenamento' => 'A confirmar',
            'Mídia' => 'Física e digital, conforme versão'
        ]
    ]

];

$adicionados = 0;
$atualizados = 0;
$erros = [];

try {

    $pdo->beginTransaction();

    // Busca categorias existentes
    $idsCategorias = [];

    foreach ($categorias as $nomeCategoria => $termos) {

        $idsCategorias[$nomeCategoria] = null;

        foreach ($termos as $termo) {

            $sqlCategoria = "
                SELECT id
                FROM categorias
                WHERE LOWER(nome) LIKE :termo
                LIMIT 1
            ";

            $stmtCategoria = $pdo->prepare($sqlCategoria);
            $stmtCategoria->execute([
                ':termo' => '%' . strtolower($termo) . '%'
            ]);

            $categoriaEncontrada = $stmtCategoria->fetch();

            if ($categoriaEncontrada) {
                $idsCategorias[$nomeCategoria] = $categoriaEncontrada['id'];
                break;
            }
        }

        if (!$idsCategorias[$nomeCategoria]) {
            throw new Exception(
                "Categoria não encontrada: " . $nomeCategoria
            );
        }
    }

    // Atualiza o Logitech G203 existente
    $sqlG203 = "
        UPDATE produtos
        SET descricao = :descricao,
            preco = :preco,
            modelo_3d = :modelo,
            ativo = 1
        WHERE nome = :nome
    ";

    $stmtG203 = $pdo->prepare($sqlG203);

    $stmtG203->execute([
        ':descricao' => 'Mouse gamer Logitech G203 LIGHTSYNC com sensor óptico para jogos, iluminação RGB personalizável e seis botões programáveis. Possui conexão USB com fio e configuração pelo Logitech G HUB.',
        ':preco' => 129.90,
        ':modelo' => 'assets/models/mouse/logitech-g203.glb',
        ':nome' => 'Logitech G203 LIGHTSYNC'
    ]);

    if ($stmtG203->rowCount() > 0) {
        $atualizados++;
    }

    // Cadastro dos demais produtos
    foreach ($produtos as $produto) {

        $categoriaId = $idsCategorias[$produto['categoria']];

        $modelo = $produto['modelo'];

        // Localiza automaticamente o arquivo RTX 5090
        if (!empty($produto['modelo_prefixo'])) {

            $arquivos = glob(
                __DIR__ . '/' .
                $produto['pasta_modelo'] .
                $produto['modelo_prefixo'] . '*.glb'
            );

            if (!empty($arquivos)) {
                $modelo = $produto['pasta_modelo'] .
                    basename($arquivos[0]);
            }
        }

        // Verifica se já existe pelo nome
        $sqlExiste = "
            SELECT id
            FROM produtos
            WHERE nome = :nome
            LIMIT 1
        ";

        $stmtExiste = $pdo->prepare($sqlExiste);
        $stmtExiste->execute([
            ':nome' => $produto['nome']
        ]);

        $existente = $stmtExiste->fetch();

        if ($existente) {

            $sqlUpdate = "
                UPDATE produtos
                SET categoria_id = :categoria_id,
                    descricao = :descricao,
                    preco = :preco,
                    estoque = :estoque,
                    imagem = :imagem,
                    modelo_3d = :modelo,
                    ativo = 1
                WHERE id = :id
            ";

            $stmtProduto = $pdo->prepare($sqlUpdate);

            $stmtProduto->execute([
                ':categoria_id' => $categoriaId,
                ':descricao' => $produto['descricao'],
                ':preco' => $produto['preco'],
                ':estoque' => $produto['estoque'],
                ':imagem' => $produto['imagem'],
                ':modelo' => $modelo,
                ':id' => $existente['id']
            ]);

            $produtoId = $existente['id'];
            $atualizados++;

        } else {

            $sqlInsert = "
                INSERT INTO produtos
                (
                    codigo,
                    categoria_id,
                    nome,
                    descricao,
                    preco,
                    estoque,
                    imagem,
                    modelo_3d,
                    ativo
                )
                VALUES
                (
                    :codigo,
                    :categoria_id,
                    :nome,
                    :descricao,
                    :preco,
                    :estoque,
                    :imagem,
                    :modelo,
                    1
                )
            ";

            $stmtProduto = $pdo->prepare($sqlInsert);

            $stmtProduto->execute([
                ':codigo' => $produto['codigo'],
                ':categoria_id' => $categoriaId,
                ':nome' => $produto['nome'],
                ':descricao' => $produto['descricao'],
                ':preco' => $produto['preco'],
                ':estoque' => $produto['estoque'],
                ':imagem' => $produto['imagem'],
                ':modelo' => $modelo
            ]);

            $produtoId = $pdo->lastInsertId();
            $adicionados++;
        }

        // Atualiza as especificações do produto
        $sqlDeleteSpecs = "
            DELETE FROM produto_especificacoes
            WHERE produto_id = :produto_id
        ";

        $stmtDeleteSpecs = $pdo->prepare($sqlDeleteSpecs);
        $stmtDeleteSpecs->execute([
            ':produto_id' => $produtoId
        ]);

        $sqlSpec = "
            INSERT INTO produto_especificacoes
            (produto_id, caracteristica, valor, ordem)
            VALUES
            (:produto_id, :caracteristica, :valor, :ordem)
        ";

        $stmtSpec = $pdo->prepare($sqlSpec);

        $ordem = 1;

        foreach ($produto['specs'] as $caracteristica => $valor) {

            $stmtSpec->execute([
                ':produto_id' => $produtoId,
                ':caracteristica' => $caracteristica,
                ':valor' => $valor,
                ':ordem' => $ordem
            ]);

            $ordem++;
        }
    }

    $pdo->commit();

    echo '<h1>Catálogo processado!</h1>';
    echo '<p>Novos produtos: ' . $adicionados . '</p>';
    echo '<p>Produtos atualizados: ' . $atualizados . '</p>';
    echo '<p>Operação concluída.</p>';

} catch (Throwable $erro) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo '<h2>Não foi possível cadastrar o catálogo.</h2>';
    echo '<p>' . htmlspecialchars($erro->getMessage()) . '</p>';
}
