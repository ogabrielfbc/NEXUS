
<?php

// 1. Inicia a sessão e conecta ao banco

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/config/database.php';


// 2. Prepara o carrinho e o token de segurança

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

if (empty($_SESSION['token_carrinho'])) {
    $_SESSION['token_carrinho'] = bin2hex(random_bytes(32));
}


// 3. Prepara as informações do produto

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$produto = null;
$erro = null;
$temModelo3D = false;
$imagensGaleria = [];


// 4. Busca o produto no banco

if ($id === false || $id === null || $id < 1) {

    http_response_code(400);

    $erro = 'Identificação de produto inválida.';

} else {

    $sql = "SELECT
                id,
                nome,
                descricao,
                preco,
                estoque,
                imagem,
                modelo_3d
            FROM produtos
            WHERE id = :id
            AND ativo = 1
            LIMIT 1";

    $consulta = $pdo->prepare($sql);

    $consulta->bindValue(':id', $id, PDO::PARAM_INT);

    $consulta->execute();

    $produto = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {

        http_response_code(404);

        $erro = 'Produto não encontrado.';

    }

}


// 5. Configura a galeria e o modelo 3D

if ($produto) {

    $temModelo3D = trim((string) $produto['modelo_3d']) !== '';

    $pastasGaleria = [
        'PlayStation 4' => 'ps4-imagem',
        'Sony PlayStation 2' => 'ps2-imagem',
        'ASUS ROG RTX 5070 OC' => '5070-imagem',
        'PlayStation 5 Slim' => 'ps5-imagem',
        'Xbox One' => 'xboxone-imagem',
        'NVIDIA GeForce RTX 5090 Founders Edition' => '5090-imagem',
        'NVIDIA GeForce RTX 3090' => '3090-imagem',
        'AMD Radeon RX 6700 XT' => '6700-imagem',
        'MacBook Pro 2021' => 'macbook-imagem',
        'Lenovo ThinkPad Laptop' => 'notebook lenovo tikinpad- imagem',
        'HP Laptop' => 'hp-imagem',
        'ASUS Notebook' => 'ASUS Laptop-imagem',
        'Samsung 24 Curved Display' => 'Samsung24-imagem',
        'Gigabyte GeForce GTX 1660' => '1660-imagem',
        'Acer K222HQL' => 'Monitoracer-imagem',
        'Redragon Lumia 2' => 'lumia2-imagem',
        'Razer Kraken Pro V2' => 'Krakenv2-imagem'
    ];

    $nomeProduto = $produto['nome'];

    if (isset($pastasGaleria[$nomeProduto])) {

        $pasta = $pastasGaleria[$nomeProduto];

        for ($i = 1; $i <= 5; $i++) {

            $caminho = "assets/img/$pasta/$i.png";

            if (file_exists(__DIR__ . '/' . $caminho)) {
                $imagensGaleria[] = $caminho;
            }

        }

    }

    // Usa a imagem principal caso não exista uma galeria

    if (empty($imagensGaleria) && !empty($produto['imagem'])) {
        $imagensGaleria[] = $produto['imagem'];
    }

}


// 6. Busca as avaliações do produto

$avaliacoes = [];

$mediaAvaliacoes = 0;

$totalAvaliacoes = 0;

if ($produto) {

    // Consulta a quantidade e a média das avaliações

    $sql = "SELECT
                COUNT(*) AS total,
                AVG(nota) AS media
            FROM avaliacoes
            WHERE produto_id = :produto_id
            AND aprovado = 1";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':produto_id' => $produto['id']
    ]);

    $resumoAvaliacoes = $consulta->fetch(PDO::FETCH_ASSOC);

    $totalAvaliacoes = (int) $resumoAvaliacoes['total'];

    $mediaAvaliacoes = (float) ($resumoAvaliacoes['media'] ?? 0);


    // Busca as avaliações aprovadas

    $sql = "SELECT
                a.nota,
                a.comentario,
                a.criado_em,
                u.nome AS nome_cliente
            FROM avaliacoes a
            INNER JOIN usuarios u
                ON a.usuario_id = u.id
            WHERE a.produto_id = :produto_id
            AND a.aprovado = 1
            ORDER BY a.criado_em DESC";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':produto_id' => $produto['id']
    ]);

    $avaliacoes = $consulta->fetchAll(PDO::FETCH_ASSOC);

}


// 7. Adiciona o produto ao carrinho

if (
    $produto &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        isset($_POST['adicionar_carrinho']) ||
        isset($_POST['comprar_agora'])
    )
) {

    $token = $_POST['token_carrinho'] ?? '';

    // Verifica o token de segurança

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['token_carrinho'], $token)
    ) {

        $_SESSION['mensagem_carrinho'] =
            'Solicitação inválida. Tente novamente.';

    } else {

        // Valida a quantidade informada

        $quantidade = filter_input(
            INPUT_POST,
            'quantidade',
            FILTER_VALIDATE_INT
        );

        if (
            $quantidade === false ||
            $quantidade === null ||
            $quantidade < 1
        ) {

            $_SESSION['mensagem_carrinho'] = 'Quantidade inválida.';

        } elseif ((int) $produto['estoque'] < 1) {

            $_SESSION['mensagem_carrinho'] =
                'Produto sem estoque disponível.';

        } else {

            // Calcula a quantidade no carrinho

            $idProduto = (int) $produto['id'];

            $quantidadeAtual = $_SESSION['carrinho'][$idProduto] ?? 0;

            $novaQuantidade = $quantidadeAtual + $quantidade;

            // Confere o limite do estoque

            if ($novaQuantidade > (int) $produto['estoque']) {

                $_SESSION['mensagem_carrinho'] =
                    'Quantidade maior que o estoque disponível.';

            } else {

                // Adiciona o produto

                $_SESSION['carrinho'][$idProduto] = $novaQuantidade;

                $_SESSION['mensagem_carrinho'] =
                    'Produto adicionado ao carrinho com sucesso!';

            }

        }

    }

    // Redireciona para o carrinho ou para o produto

    if (isset($_POST['comprar_agora'])) {

        header('Location: carrinho.php');

    } else {

        header('Location: produto.php?id=' . $id);

    }

    exit;

}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <!-- 8. Configurações da página -->

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $produto ? htmlspecialchars($produto['nome']) : 'Produto' ?>
        | NEXUS TECH
    </title>


    <!-- 9. Arquivos de estilo -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="style.css">


    <!-- 10. Estilo dos controles de quantidade -->

    <style>

        .controle-quantidade-produto {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .botao-quantidade-produto {
            width: 42px;
            height: 42px;
            border: 1px solid #29496e;
            background: #142640;
            color: #45b2ff;
            border-radius: 8px;
            font-size: 23px;
            font-weight: bold;
            cursor: pointer;
            transition: .2s;
        }

        .botao-quantidade-produto:hover {
            background: #1685d5;
            color: white;
        }

        .campo-quantidade-produto {
            width: 55px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: white;
            background: #101a2c;
            border: 1px solid #29496e;
            border-radius: 8px;
            padding: 8px 4px;
        }

        .campo-quantidade-produto:focus {
            outline: none;
            border-color: #35aaff;
        }

    </style>


    <!-- 11. Importações do Three.js -->

    <script type="importmap">
    {
        "imports": {
            "three": "https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js",
            "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.180.0/examples/jsm/"
        }
    }
    </script>

</head>

<body class="pagina-produto">

    <!-- 12. Conteúdo principal -->

    <main class="container product-details">


        <!-- Botão para voltar à loja -->

        <button
            type="button"
            class="btn-voltar-loja"
            onclick="window.location.href='index.php'"
        >
            <span aria-hidden="true"></span>
            Voltar para a loja
        </button>


        <?php if ($erro): ?>

            <!-- Mensagem de erro -->

            <h1>Ops!</h1>

            <p><?= htmlspecialchars($erro) ?></p>


        <?php else: ?>


            <div class="row mt-4">


                <!-- 13. Galeria de imagens -->

                <div class="col-md-6">

                    <div class="product-gallery">

                        <div class="product-gallery-grid">

                            <?php foreach ($imagensGaleria as $indice => $imagem): ?>

                                <button
                                    type="button"
                                    class="product-gallery-item"
                                    onclick="abrirGaleria(<?= $indice ?>)"
                                    aria-label="Ampliar imagem <?= $indice + 1 ?>"
                                >

                                    <img
                                        src="<?= htmlspecialchars($imagem) ?>"
                                        alt="<?= htmlspecialchars($produto['nome']) ?> - Imagem <?= $indice + 1 ?>"
                                    >

                                </button>

                            <?php endforeach; ?>

                        </div>


                        <!-- Botão de visualização 3D -->

                        <?php if ($temModelo3D): ?>

                            <div class="product-3d-cta">

                                <div class="product-3d-cta-text">

                                    <span class="product-3d-icon">◇</span>

                                    <div>

                                        <strong>
                                            Visualização interativa disponível
                                        </strong>

                                        <small>
                                            Explore o produto em todos os ângulos
                                        </small>

                                    </div>

                                </div>

                                <button
                                    type="button"
                                    class="btn-view-3d"
                                    id="abrir3D"
                                >
                                    <span aria-hidden="true">◈</span>
                                    Ver modelo em 3D
                                </button>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- 14. Informações do produto -->

                <div class="col-md-6 product-details-info">

                    <h1>
                        <?= htmlspecialchars($produto['nome']) ?>
                    </h1>

                    <p class="product-description">
                        <?= htmlspecialchars($produto['descricao'] ?? '') ?>
                    </p>

                    <h2 class="product-price">
                        R$
                        <?= number_format($produto['preco'], 2, ',', '.') ?>
                    </h2>


                    <!-- Disponibilidade do produto -->

                    <p class="product-stock <?= $produto['estoque'] > 0 ? 'available' : 'unavailable' ?>">

                        <?= $produto['estoque'] > 0 ? 'Produto disponível' : 'Produto esgotado' ?>

                        <?php if ($produto['estoque'] > 0): ?>

                            — <?= (int) $produto['estoque'] ?> unidades

                        <?php endif; ?>

                    </p>


                    <!-- 15. Mensagem do carrinho -->

                    <?php if (isset($_SESSION['mensagem_carrinho'])): ?>

                        <div class="alert alert-info mt-3">

                            <?= htmlspecialchars(
                                $_SESSION['mensagem_carrinho'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                        <?php unset($_SESSION['mensagem_carrinho']); ?>

                    <?php endif; ?>


                    <!-- 16. Formulário de compra -->

                    <?php if ($produto['estoque'] > 0): ?>

                        <form method="POST" class="mt-4">

                            <input
                                type="hidden"
                                name="token_carrinho"
                                value="<?= htmlspecialchars($_SESSION['token_carrinho']) ?>"
                            >


                            <!-- Controle de quantidade -->

                            <label class="form-label">
                                Quantidade
                            </label>

                            <div class="controle-quantidade-produto">

                                <button
                                    type="button"
                                    class="botao-quantidade-produto"
                                    onclick="alterarQuantidade(-1)"
                                >
                                    −
                                </button>

                                <input
                                    type="number"
                                    name="quantidade"
                                    id="quantidade"
                                    class="campo-quantidade-produto"
                                    value="1"
                                    min="1"
                                    max="<?= (int) $produto['estoque'] ?>"
                                    required
                                >

                                <button
                                    type="button"
                                    class="botao-quantidade-produto"
                                    onclick="alterarQuantidade(1)"
                                >
                                    +
                                </button>

                            </div>


                            <!-- Botão adicionar ao carrinho -->

                            <button
                                type="submit"
                                name="adicionar_carrinho"
                                class="botao-carrinho"
                            >
                                <i class="bi bi-cart-plus"></i>
                                Adicionar ao carrinho
                            </button>


                            <!-- Botão comprar agora -->

                            <button
                                type="submit"
                                name="comprar_agora"
                                class="botao-comprar"
                            >
                                <i class="bi bi-lightning-charge-fill"></i>
                                Comprar agora
                            </button>

                        </form>

                    <?php else: ?>

                        <button class="btn btn-secondary mt-3" disabled>
                            Produto esgotado
                        </button>

                    <?php endif; ?>

                </div>

            </div>


            <!-- 17. Avaliações dos clientes -->

            <section class="secao-avaliacoes">

                <h2>Avaliações dos clientes</h2>

                <p class="text-secondary">
                    Veja a opinião de quem já comprou este produto.
                </p>


                <?php if ($totalAvaliacoes > 0): ?>


                    <!-- Resumo das avaliações -->

                    <div class="resumo-avaliacoes">

                        <span class="numero-media">

                            <?= number_format($mediaAvaliacoes, 1, ',', '.') ?>

                        </span>

                        <span class="media-estrelas">

                            <?php

                            $estrelasCheias = (int) round($mediaAvaliacoes);

                            echo str_repeat('★', $estrelasCheias);

                            echo str_repeat('☆', 5 - $estrelasCheias);

                            ?>

                        </span>

                        <span class="total-avaliacoes">

                            <?= $totalAvaliacoes ?>

                            <?= $totalAvaliacoes === 1 ? 'avaliação' : 'avaliações' ?>

                        </span>

                    </div>


                    <!-- Lista de avaliações -->

                    <?php foreach ($avaliacoes as $avaliacao): ?>

                        <article class="avaliacao-cliente">

                            <div class="avaliacao-cliente-topo">

                                <div>

                                    <div class="avaliacao-cliente-nome">

                                        <?= htmlspecialchars(
                                            $avaliacao['nome_cliente'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                    <div class="avaliacao-cliente-data">

                                        <?= date(
                                            'd/m/Y',
                                            strtotime($avaliacao['criado_em'])
                                        ) ?>

                                    </div>

                                </div>

                                <div class="avaliacao-cliente-estrelas">

                                    <?= str_repeat('★', (int) $avaliacao['nota']) ?>

                                    <?= str_repeat('☆', 5 - (int) $avaliacao['nota']) ?>

                                </div>

                            </div>


                            <?php if (!empty($avaliacao['comentario'])): ?>

                                <p class="avaliacao-cliente-comentario">

                                    <?= htmlspecialchars(
                                        $avaliacao['comentario'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>


                <?php else: ?>


                    <!-- Mensagem quando não existem avaliações -->

                    <div class="sem-avaliacoes">

                        Este produto ainda não recebeu avaliações.
                        Seja o primeiro a compartilhar sua opinião!

                    </div>


                <?php endif; ?>

            </section>


        <?php endif; ?>

    </main>


    <!-- 18. Modal da galeria -->

    <?php if ($produto && !empty($imagensGaleria)): ?>

        <div class="gallery-modal" id="galleryModal">

            <div class="gallery-modal-content">

                <button
                    type="button"
                    class="gallery-modal-close"
                    onclick="fecharGaleria()"
                    aria-label="Fechar galeria"
                >
                    ×
                </button>


                <div class="gallery-modal-main">

                    <img
                        src=""
                        id="imagemAmpliada"
                        alt="Imagem ampliada do produto"
                    >

                </div>


                <!-- Miniaturas da galeria -->

                <div class="gallery-modal-thumbnails">

                    <?php foreach ($imagensGaleria as $indice => $imagem): ?>

                        <button
                            type="button"
                            class="gallery-modal-thumb <?= $indice === 0 ? 'active' : '' ?>"
                            onclick="selecionarImagem(<?= $indice ?>)"
                            aria-label="Selecionar imagem <?= $indice + 1 ?>"
                        >

                            <img
                                src="<?= htmlspecialchars($imagem) ?>"
                                alt="Miniatura <?= $indice + 1 ?>"
                            >

                        </button>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>


        <!-- Controle da galeria -->

        <script>

            const imagensProduto = <?= json_encode(
                $imagensGaleria,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            ) ?>;

            let imagemAtual = 0;

            const modalGaleria = document.getElementById('galleryModal');

            const imagemAmpliada = document.getElementById('imagemAmpliada');


            // Abre a galeria

            function abrirGaleria(indice) {

                imagemAtual = indice;

                atualizarGaleria();

                modalGaleria.classList.add('active');

                document.body.style.overflow = 'hidden';

            }


            // Fecha a galeria

            function fecharGaleria() {

                modalGaleria.classList.remove('active');

                const modal3D = document.getElementById('viewerModal3D');

                if (!modal3D?.classList.contains('active')) {
                    document.body.style.overflow = '';
                }

            }


            // Seleciona uma imagem

            function selecionarImagem(indice) {

                imagemAtual = indice;

                atualizarGaleria();

            }


            // Atualiza a imagem ampliada

            function atualizarGaleria() {

                imagemAmpliada.src = imagensProduto[imagemAtual];

                document.querySelectorAll('.gallery-modal-thumb').forEach(
                    function(miniatura, indice) {

                        miniatura.classList.toggle(
                            'active',
                            indice === imagemAtual
                        );

                    }
                );

            }


            // Fecha ao clicar fora da imagem

            modalGaleria.addEventListener('click', function(event) {

                if (event.target === modalGaleria) {
                    fecharGaleria();
                }

            });


            // Controla a galeria pelo teclado

            document.addEventListener('keydown', function(event) {

                if (!modalGaleria.classList.contains('active')) {
                    return;
                }

                if (event.key === 'Escape') {
                    fecharGaleria();
                }

                if (event.key === 'ArrowRight') {

                    imagemAtual = (imagemAtual + 1) % imagensProduto.length;

                    atualizarGaleria();

                }

                if (event.key === 'ArrowLeft') {

                    imagemAtual = (
                        imagemAtual - 1 + imagensProduto.length
                    ) % imagensProduto.length;

                    atualizarGaleria();

                }

            });

        </script>

    <?php endif; ?>


    <!-- 19. Modal do visualizador 3D -->

    <?php if ($temModelo3D): ?>

        <div
            class="gallery-modal viewer-modal-3d"
            id="viewerModal3D"
            aria-hidden="true"
            role="dialog"
            aria-modal="true"
            aria-label="Visualizador 3D do produto"
        >

            <div class="viewer-modal-content">

                <div class="viewer-modal-heading">

                    <div class="viewer-heading-info">

                        <span class="viewer-modal-kicker">
                            NEXUS TECH · 3D STUDIO
                        </span>

                        <h2>
                            <?= htmlspecialchars($produto['nome']) ?>
                        </h2>

                        <p>
                            Explore cada detalhe do produto em três dimensões.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="viewer-close"
                        id="fechar3D"
                        aria-label="Fechar visualização"
                    >
                        ×
                    </button>

                </div>


                <div class="viewer-3d" id="viewer3D">

                    <div class="viewer-loading" id="loading3D">
                        Preparando estúdio 3D...
                    </div>

                    <div class="viewer-info">
                        Arraste para girar
                        <span>•</span>
                        Use o scroll para aproximar
                    </div>

                </div>


                <div class="viewer-modal-footer">

                    <span>
                        <i class="viewer-control-icon">↔</i>
                        Arraste para girar
                    </span>

                    <span>
                        <i class="viewer-control-icon">⌕</i>
                        Scroll para aproximar
                    </span>

                </div>

            </div>

        </div>

    <?php endif; ?>


    <!-- 20. Visualizador Three.js -->

    <?php if ($temModelo3D): ?>

        <script type="module">

            import * as THREE from 'three';

            import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

            import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

            import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

            import { Reflector } from 'three/addons/objects/Reflector.js';

            import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';

            import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';

            import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';

            import { ShaderPass } from 'three/addons/postprocessing/ShaderPass.js';


            // Elementos do visualizador

            const areaVisualizador = document.getElementById('viewer3D');

            const carregamento = document.getElementById('loading3D');

            const botaoAbrir = document.getElementById('abrir3D');

            const botaoFechar = document.getElementById('fechar3D');

            const modal3D = document.getElementById('viewerModal3D');


            const modelo3DUrl = <?= json_encode(
                $produto['modelo_3d'],
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            ) ?>;


            if (
                !areaVisualizador ||
                !carregamento ||
                !botaoAbrir ||
                !botaoFechar ||
                !modal3D
            ) {

                throw new Error('Visualizador 3D não encontrado.');

            }


            // Variáveis de controle

            let carregamentoIniciado = false;

            let modeloCarregado = false;

            let animacaoAtiva = false;

            let quadroAnimacao = null;


            // Cena

            const cena = new THREE.Scene();

            const telaFundo = document.createElement('canvas');

            telaFundo.width = 512;

            telaFundo.height = 512;

            const contextoFundo = telaFundo.getContext('2d');

            const gradienteFundo = contextoFundo.createRadialGradient(
                256, 256, 10,
                256, 256, 440
            );

            gradienteFundo.addColorStop(0, '#777783');

            gradienteFundo.addColorStop(0.35, '#484852');

            gradienteFundo.addColorStop(0.7, '#32323c');

            gradienteFundo.addColorStop(1, '#292930');

            contextoFundo.fillStyle = gradienteFundo;

            contextoFundo.fillRect(0, 0, 512, 512);

            const texturaFundo = new THREE.CanvasTexture(telaFundo);

            texturaFundo.colorSpace = THREE.SRGBColorSpace;

            cena.background = texturaFundo;


            // Câmera

            const camera = new THREE.PerspectiveCamera(35, 1, 0.01, 100);


            // Renderizador

            const renderizador = new THREE.WebGLRenderer({

                antialias: true,

                alpha: false,

                powerPreference: 'high-performance'

            });

            renderizador.setPixelRatio(
                Math.min(window.devicePixelRatio || 1, 1.5)
            );

            renderizador.outputColorSpace = THREE.SRGBColorSpace;

            renderizador.toneMapping = THREE.NoToneMapping;

            renderizador.shadowMap.enabled = true;

            renderizador.shadowMap.type = THREE.PCFSoftShadowMap;

            renderizador.domElement.setAttribute(
                'aria-label',
                'Visualizador interativo do modelo 3D'
            );

            areaVisualizador.appendChild(renderizador.domElement);


            // Efeito de brilho

            const BLOOM_SCENE = 1;

            const cameraBloom = camera.clone();

            cameraBloom.layers.set(BLOOM_SCENE);

            const compositorBloom = new EffectComposer(renderizador);

            compositorBloom.addPass(
                new RenderPass(cena, cameraBloom)
            );

            const bloomPass = new UnrealBloomPass(
                new THREE.Vector2(1, 1),
                1.85,
                0.85,
                0.12
            );

            compositorBloom.addPass(bloomPass);

            const compositorFinal = new EffectComposer(renderizador);

            compositorFinal.addPass(
                new RenderPass(cena, camera)
            );

            const passeCombinacao = new ShaderPass({

                uniforms: {

                    baseTexture: {
                        value: null
                    },

                    bloomTexture: {
                        value: compositorBloom.renderTarget2.texture
                    }

                },

                vertexShader: `
                    varying vec2 vUv;

                    void main() {
                        vUv = uv;
                        gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                    }
                `,

                fragmentShader: `
                    uniform sampler2D baseTexture;
                    uniform sampler2D bloomTexture;

                    varying vec2 vUv;

                    void main() {
                        vec4 base = texture2D(baseTexture, vUv);
                        vec4 bloom = texture2D(bloomTexture, vUv);

                        gl_FragColor = vec4(base.rgb + bloom.rgb, base.a);
                    }
                `

            }, 'baseTexture');

            compositorFinal.addPass(passeCombinacao);


            // Ambiente

            const geradorAmbiente = new THREE.PMREMGenerator(renderizador);

            const ambienteSala = new RoomEnvironment();

            const ambienteProcessado = geradorAmbiente.fromScene(ambienteSala);

            cena.environment = ambienteProcessado.texture;

            cena.environmentIntensity = 0.55;

            ambienteSala.dispose();

            geradorAmbiente.dispose();


            // Iluminação

            const luzAmbiente = new THREE.HemisphereLight(
                0xffffff,
                0x34343d,
                1.05
            );

            cena.add(luzAmbiente);

            const luzPrincipal = new THREE.DirectionalLight(0xffffff, 2.2);

            luzPrincipal.position.set(4, 7, 5);

            luzPrincipal.castShadow = true;

            luzPrincipal.shadow.mapSize.set(2048, 2048);

            luzPrincipal.shadow.camera.near = 0.1;

            luzPrincipal.shadow.camera.far = 35;

            luzPrincipal.shadow.camera.left = -5;

            luzPrincipal.shadow.camera.right = 5;

            luzPrincipal.shadow.camera.top = 5;

            luzPrincipal.shadow.camera.bottom = -5;

            luzPrincipal.shadow.bias = -0.00008;

            luzPrincipal.shadow.normalBias = 0.025;

            luzPrincipal.shadow.radius = 5;

            cena.add(luzPrincipal);

            const luzPreenchimento = new THREE.DirectionalLight(
                0xe8edff,
                0.55
            );

            luzPreenchimento.position.set(-5, 3, 4);

            cena.add(luzPreenchimento);

            const luzSuperior = new THREE.DirectionalLight(0xffffff, 0.42);

            luzSuperior.position.set(0, 8, -1);

            cena.add(luzSuperior);


            // Chão

            const materialBasePiso = new THREE.MeshStandardMaterial({

                color: 0x92929E,

                roughness: 0.8,

                metalness: 0.05,

                side: THREE.DoubleSide

            });

            const basePiso = new THREE.Mesh(
                new THREE.PlaneGeometry(1, 1),
                materialBasePiso
            );

            basePiso.rotation.x = -Math.PI / 2;

            basePiso.position.set(0, -0.04, 0);

            cena.add(basePiso);

            const piso = new Reflector(
                new THREE.PlaneGeometry(2000, 2000),
                {

                    clipBias: 0.003,

                    textureWidth: 512,

                    textureHeight: 512,

                    color: 0xffffff

                }
            );

            piso.rotation.x = -Math.PI / 2;

            piso.position.set(0, -0.025, 0);

            cena.add(piso);


            // Iluminação de contorno

            const grupoMeiaLua = new THREE.Group();

            const raioMeiaLua = 2.8;

            const pontosMeiaLua = [];

            const segmentosMeiaLua = 120;

            for (let i = 0; i <= segmentosMeiaLua; i++) {

                const angulo = Math.PI - (Math.PI * i / segmentosMeiaLua);

                const x = raioMeiaLua * Math.cos(angulo);

                const z = -raioMeiaLua * Math.sin(angulo);

                pontosMeiaLua.push(new THREE.Vector3(x, 0, z));

            }

            const curvaMeiaLua = new THREE.CatmullRomCurve3(pontosMeiaLua);

            const telaAura = document.createElement('canvas');

            telaAura.width = 256;

            telaAura.height = 256;

            const contextoAura = telaAura.getContext('2d');

            const gradienteAura = contextoAura.createRadialGradient(
                128, 128, 5,
                128, 128, 128
            );

            gradienteAura.addColorStop(0, 'rgba(255,255,255,0.75)');

            gradienteAura.addColorStop(0.25, 'rgba(255,255,255,0.35)');

            gradienteAura.addColorStop(0.55, 'rgba(255,255,255,0.10)');

            gradienteAura.addColorStop(1, 'rgba(255,255,255,0)');

            contextoAura.fillStyle = gradienteAura;

            contextoAura.fillRect(0, 0, 256, 256);

            const texturaAura = new THREE.CanvasTexture(telaAura);

            texturaAura.colorSpace = THREE.SRGBColorSpace;


            // Halo externo

            const haloMeiaLua = new THREE.Mesh(

                new THREE.TubeGeometry(
                    curvaMeiaLua,
                    120,
                    0.22,
                    16,
                    false
                ),

                new THREE.MeshBasicMaterial({

                    color: 0xffffff,

                    map: texturaAura,

                    transparent: true,

                    opacity: 0.95,

                    blending: THREE.AdditiveBlending,

                    depthWrite: false,

                    toneMapped: false,

                    side: THREE.DoubleSide

                })

            );


            // Brilho intermediário

            const brilhoMeiaLua = new THREE.Mesh(

                new THREE.TubeGeometry(
                    curvaMeiaLua,
                    120,
                    0.065,
                    16,
                    false
                ),

                new THREE.MeshBasicMaterial({

                    color: 0xffffff,

                    transparent: true,

                    opacity: 0.72,

                    blending: THREE.AdditiveBlending,

                    depthWrite: false,

                    toneMapped: false

                })

            );


            // Núcleo de iluminação

            const nucleoMeiaLua = new THREE.Mesh(

                new THREE.TubeGeometry(
                    curvaMeiaLua,
                    120,
                    0.014,
                    12,
                    false
                ),

                new THREE.MeshBasicMaterial({

                    color: 0xffffff,

                    toneMapped: false

                })

            );

            grupoMeiaLua.add(haloMeiaLua);

            grupoMeiaLua.add(brilhoMeiaLua);

            grupoMeiaLua.add(nucleoMeiaLua);

            haloMeiaLua.layers.enable(BLOOM_SCENE);

            brilhoMeiaLua.layers.enable(BLOOM_SCENE);

            nucleoMeiaLua.layers.enable(BLOOM_SCENE);

            grupoMeiaLua.position.y = piso.position.y + 0.025;

            cena.add(grupoMeiaLua);


            // Controles do modelo

            const controles = new OrbitControls(
                camera,
                renderizador.domElement
            );

            controles.enableDamping = true;

            controles.dampingFactor = 0.055;

            controles.autoRotate = false;

            controles.enableRotate = true;

            controles.enableZoom = true;

            controles.enablePan = false;

            controles.minPolarAngle = 0.01;

            controles.maxPolarAngle = Math.PI - 0.01;

            controles.minDistance = 0.5;

            controles.maxDistance = 20;


            // Ajusta o tamanho do visualizador

            function ajustarTamanhoVisualizador() {

                const largura = areaVisualizador.clientWidth;

                const altura = areaVisualizador.clientHeight;

                if (largura <= 0 || altura <= 0) {
                    return;
                }

                camera.aspect = largura / altura;

                camera.updateProjectionMatrix();

                cameraBloom.copy(camera);

                cameraBloom.layers.set(BLOOM_SCENE);

                renderizador.setSize(largura, altura, false);

                compositorBloom.setSize(largura, altura);

                compositorFinal.setSize(largura, altura);

            }


            // Animação do visualizador

            function animarVisualizador() {

                if (!modal3D.classList.contains('active')) {

                    animacaoAtiva = false;

                    quadroAnimacao = null;

                    return;

                }

                quadroAnimacao = requestAnimationFrame(animarVisualizador);

                controles.update();

                cameraBloom.position.copy(camera.position);

                cameraBloom.quaternion.copy(camera.quaternion);

                cameraBloom.projectionMatrix.copy(camera.projectionMatrix);

                cameraBloom.projectionMatrixInverse.copy(
                    camera.projectionMatrixInverse
                );

                const fundoOriginal = cena.background;

                cena.background = null;

                try {

                    compositorBloom.render();

                } finally {

                    cena.background = fundoOriginal;

                }

                compositorFinal.render();

            }


            // Inicia a animação

            function iniciarAnimacao() {

                if (animacaoAtiva) {
                    return;
                }

                animacaoAtiva = true;

                quadroAnimacao = requestAnimationFrame(animarVisualizador);

            }


            // Interrompe a animação

            function pararAnimacao() {

                if (quadroAnimacao !== null) {

                    cancelAnimationFrame(quadroAnimacao);

                    quadroAnimacao = null;

                }

                animacaoAtiva = false;

            }


            // Abre o visualizador 3D

            function abrirVisualizador3D() {

                modal3D.classList.add('active');

                modal3D.setAttribute('aria-hidden', 'false');

                document.body.style.overflow = 'hidden';

                requestAnimationFrame(() => {

                    ajustarTamanhoVisualizador();

                    controles.update();

                    requestAnimationFrame(() => {

                        ajustarTamanhoVisualizador();

                        carregarModelo3D();

                        iniciarAnimacao();

                    });

                });

            }


            // Fecha o visualizador 3D

            function fecharVisualizador3D() {

                modal3D.classList.remove('active');

                modal3D.setAttribute('aria-hidden', 'true');

                pararAnimacao();

                const galeriaAberta = document.getElementById('galleryModal')
                    ?.classList.contains('active');

                if (!galeriaAberta) {
                    document.body.style.overflow = '';
                }

            }

            botaoAbrir.addEventListener(
                'click',
                abrirVisualizador3D
            );

            botaoFechar.addEventListener(
                'click',
                fecharVisualizador3D
            );


            // Fecha ao clicar fora do visualizador

            modal3D.addEventListener('click', function(event) {

                if (event.target === modal3D) {
                    fecharVisualizador3D();
                }

            });


            // Fecha pelo teclado

            document.addEventListener('keydown', function(event) {

                if (
                    event.key === 'Escape' &&
                    modal3D.classList.contains('active')
                ) {

                    fecharVisualizador3D();

                }

            });


            // Carregamento do modelo 3D

            const carregadorModelo = new GLTFLoader();

            function carregarModelo3D() {

                if (carregamentoIniciado || modeloCarregado) {
                    return;
                }

                carregamentoIniciado = true;

                carregadorModelo.load(

                    modelo3DUrl,


                    // Modelo carregado

                    function(gltf) {

                        const modelo = gltf.scene;

                        modelo.traverse(function(objeto) {

                            if (!objeto.isMesh) {
                                return;
                            }

                            objeto.castShadow = true;

                            objeto.receiveShadow = true;

                        });


                        // Calcula o tamanho original

                        modelo.updateMatrixWorld(true);

                        const caixaOriginal = new THREE.Box3().setFromObject(modelo);

                        const tamanhoOriginal = caixaOriginal.getSize(
                            new THREE.Vector3()
                        );

                        const maiorDimensao = Math.max(
                            tamanhoOriginal.x,
                            tamanhoOriginal.y,
                            tamanhoOriginal.z
                        );

                        if (
                            !Number.isFinite(maiorDimensao) ||
                            maiorDimensao <= 0
                        ) {

                            carregamento.textContent = 'Modelo 3D inválido.';

                            carregamentoIniciado = false;

                            return;

                        }


                        // Ajusta a escala

                        const escala = 2.2 / maiorDimensao;

                        modelo.scale.setScalar(escala);

                        modelo.updateMatrixWorld(true);


                        // Centraliza o modelo

                        const caixaEscalada = new THREE.Box3().setFromObject(modelo);

                        const centroEscalado = caixaEscalada.getCenter(
                            new THREE.Vector3()
                        );

                        modelo.position.set(
                            -centroEscalado.x,
                            -caixaEscalada.min.y,
                            -centroEscalado.z
                        );

                        cena.add(modelo);

                        modelo.updateMatrixWorld(true);


                        // Calcula as dimensões finais

                        const caixaFinal = new THREE.Box3().setFromObject(modelo);

                        const centroFinal = caixaFinal.getCenter(
                            new THREE.Vector3()
                        );

                        const esferaModelo = caixaFinal.getBoundingSphere(
                            new THREE.Sphere()
                        );

                        const raioModelo = Math.max(
                            esferaModelo.radius,
                            0.1
                        );


                        // Posiciona a câmera

                        const campoVertical = THREE.MathUtils.degToRad(camera.fov);

                        const campoHorizontal = 2 * Math.atan(
                            Math.tan(campoVertical / 2) * camera.aspect
                        );

                        const campoLimitante = Math.min(
                            campoVertical,
                            campoHorizontal
                        );

                        const distanciaCamera =
                            (raioModelo / Math.sin(campoLimitante / 2)) * 1.18;

                        controles.target.copy(centroFinal);

                        camera.position.set(
                            centroFinal.x + distanciaCamera * 0.07,
                            centroFinal.y + distanciaCamera * 0.045,
                            centroFinal.z + distanciaCamera
                        );

                        camera.near = 0.1;

                        camera.far = 3000;

                        camera.updateProjectionMatrix();

                        controles.minDistance = Math.max(
                            0.25,
                            distanciaCamera * 0.35
                        );

                        controles.maxDistance = distanciaCamera * 3.2;

                        controles.update();


                        // Posiciona o cenário

                        const alturaChao = caixaFinal.min.y;

                        basePiso.position.y = alturaChao - 0.04;

                        piso.position.y = alturaChao - 0.025;

                        grupoMeiaLua.position.y = alturaChao + 0.002;


                        // Finaliza o carregamento

                        modeloCarregado = true;

                        carregamento.style.display = 'none';

                        ajustarTamanhoVisualizador();

                        console.log('Modelo 3D carregado com sucesso.');

                    },


                    // Atualiza o progresso

                    function(progresso) {

                        if (progresso.total > 0) {

                            const porcentagem = Math.round(
                                (progresso.loaded / progresso.total) * 100
                            );

                            carregamento.textContent =
                                'Carregando modelo 3D... ' + porcentagem + '%';

                        }

                    },


                    // Trata erros no carregamento

                    function(erro) {

                        console.error('Erro ao carregar modelo:', erro);

                        carregamentoIniciado = false;

                        carregamento.textContent =
                            'Não foi possível carregar o modelo 3D. Verifique o arquivo do produto.';

                    }

                );

            }


            // Monitora o tamanho do visualizador

            const observadorTamanho = new ResizeObserver(function() {

                ajustarTamanhoVisualizador();

            });

            observadorTamanho.observe(areaVisualizador);

            window.addEventListener(
                'resize',
                ajustarTamanhoVisualizador
            );

        </script>

    <?php endif; ?>


    <!-- 21. Controle da quantidade -->

    <script>

        function alterarQuantidade(valor) {

            const campo = document.getElementById('quantidade');

            if (!campo) {
                return;
            }

            const quantidadeAtual = parseInt(campo.value) || 1;

            const quantidadeMinima = parseInt(campo.min);

            const quantidadeMaxima = parseInt(campo.max);

            let novaQuantidade = quantidadeAtual + valor;

            if (novaQuantidade < quantidadeMinima) {
                novaQuantidade = quantidadeMinima;
            }

            if (novaQuantidade > quantidadeMaxima) {
                novaQuantidade = quantidadeMaxima;
            }

            campo.value = novaQuantidade;

        }

    </script>

</body>
</html>
