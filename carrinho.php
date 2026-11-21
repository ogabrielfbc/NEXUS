
<?php

// 1. Inicia a sessão e conecta ao banco

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/config/database.php';


// 2. Prepara o carrinho e os tokens

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

if (empty($_SESSION['token_finalizar_compra'])) {
    $_SESSION['token_finalizar_compra'] = bin2hex(random_bytes(32));
}

if (empty($_SESSION['token_carrinho'])) {
    $_SESSION['token_carrinho'] = bin2hex(random_bytes(32));
}


// 3. Processa as ações do carrinho

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token_carrinho'] ?? '';

    if (!hash_equals($_SESSION['token_carrinho'], $token)) {

        $_SESSION['mensagem_carrinho'] = 'Solicitação inválida.';

    } else {

        $idProduto = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);


        // Aumenta a quantidade

        if (isset($_POST['aumentar_quantidade'])) {

            if ($idProduto && isset($_SESSION['carrinho'][$idProduto])) {

                $consulta = $pdo->prepare("
                    SELECT estoque
                    FROM produtos
                    WHERE id = ?
                    AND ativo = 1
                ");

                $consulta->execute([$idProduto]);

                $produto = $consulta->fetch(PDO::FETCH_ASSOC);

                if (!$produto) {

                    unset($_SESSION['carrinho'][$idProduto]);

                    $_SESSION['mensagem_carrinho'] = 'Produto indisponível e removido do carrinho.';

                } elseif ($_SESSION['carrinho'][$idProduto] < $produto['estoque']) {

                    $_SESSION['carrinho'][$idProduto]++;

                    $_SESSION['mensagem_carrinho'] = 'Quantidade atualizada.';

                } else {

                    $_SESSION['mensagem_carrinho'] = 'Você atingiu o limite de estoque disponível.';

                }
            }


        // Diminui a quantidade

        } elseif (isset($_POST['diminuir_quantidade'])) {

            if ($idProduto && isset($_SESSION['carrinho'][$idProduto])) {

                $_SESSION['carrinho'][$idProduto]--;

                if ($_SESSION['carrinho'][$idProduto] <= 0) {

                    unset($_SESSION['carrinho'][$idProduto]);

                    $_SESSION['mensagem_carrinho'] = 'Produto removido do carrinho.';

                } else {

                    $_SESSION['mensagem_carrinho'] = 'Quantidade atualizada.';

                }
            }


        // Remove um produto

        } elseif (isset($_POST['remover_produto'])) {

            if ($idProduto && isset($_SESSION['carrinho'][$idProduto])) {

                unset($_SESSION['carrinho'][$idProduto]);

                $_SESSION['mensagem_carrinho'] = 'Produto removido do carrinho.';

            }


        // Aplica um cupom

        } elseif (isset($_POST['aplicar_cupom'])) {

            $codigoCupom = strtoupper(trim($_POST['codigo_cupom'] ?? ''));

            if ($codigoCupom === '') {

                unset($_SESSION['cupom_aplicado']);

                $_SESSION['mensagem_carrinho'] = 'Digite um código de cupom.';

            } else {

                $sql = "SELECT
                            id,
                            codigo,
                            tipo_desconto,
                            valor_desconto,
                            validade_inicio,
                            validade_fim,
                            limite_utilizacoes,
                            utilizacoes,
                            ativo,
                            (NOW() BETWEEN validade_inicio AND validade_fim) AS dentro_validade
                        FROM cupons
                        WHERE codigo = :codigo
                        LIMIT 1";

                $consulta = $pdo->prepare($sql);

                $consulta->execute([
                    ':codigo' => $codigoCupom
                ]);

                $cupom = $consulta->fetch(PDO::FETCH_ASSOC);

                if (!$cupom) {

                    unset($_SESSION['cupom_aplicado']);

                    $_SESSION['mensagem_carrinho'] = 'Cupom não encontrado.';

                } elseif (!(int) $cupom['ativo']) {

                    unset($_SESSION['cupom_aplicado']);

                    $_SESSION['mensagem_carrinho'] = 'Este cupom está desativado.';

                } elseif (!(int) $cupom['dentro_validade']) {

                    unset($_SESSION['cupom_aplicado']);

                    $_SESSION['mensagem_carrinho'] = 'Este cupom está fora do período de validade.';

                } elseif (
                    $cupom['limite_utilizacoes'] !== null &&
                    (int) $cupom['utilizacoes'] >= (int) $cupom['limite_utilizacoes']
                ) {

                    unset($_SESSION['cupom_aplicado']);

                    $_SESSION['mensagem_carrinho'] = 'Este cupom atingiu o limite de utilizações.';

                } else {

                    $_SESSION['cupom_aplicado'] = [
                        'id' => (int) $cupom['id'],
                        'codigo' => $cupom['codigo']
                    ];

                    $_SESSION['mensagem_carrinho'] = 'Cupom ' . $cupom['codigo'] . ' aplicado com sucesso!';

                }
            }


        // Esvazia o carrinho

        } elseif (isset($_POST['limpar_carrinho'])) {

            $_SESSION['carrinho'] = [];

            unset($_SESSION['cupom_aplicado']);

            $_SESSION['mensagem_carrinho'] = 'Carrinho esvaziado.';

        }
    }

    header('Location: carrinho.php');
    exit;
}


// 4. Busca os produtos do carrinho

$produtosCarrinho = [];
$totalCarrinho = 0;

if (!empty($_SESSION['carrinho'])) {

    $idsProdutos = array_keys($_SESSION['carrinho']);

    $marcadores = implode(',', array_fill(0, count($idsProdutos), '?'));

    $sql = "SELECT
                id,
                nome,
                preco,
                imagem,
                estoque
            FROM produtos
            WHERE id IN ($marcadores)
            AND ativo = 1";

    $consulta = $pdo->prepare($sql);

    $consulta->execute($idsProdutos);

    $produtosCarrinho = $consulta->fetchAll(PDO::FETCH_ASSOC);

    foreach ($produtosCarrinho as &$produto) {

        $quantidade = $_SESSION['carrinho'][$produto['id']];

        $produto['quantidade'] = $quantidade;

        $produto['subtotal'] = $produto['preco'] * $quantidade;

        $totalCarrinho += $produto['subtotal'];
    }

    unset($produto);
}


// 5. Calcula o desconto do cupom

$valorDesconto = 0;
$totalComDesconto = $totalCarrinho;
$cupomAtual = null;

if (!empty($_SESSION['cupom_aplicado']['id'])) {

    $consulta = $pdo->prepare("
        SELECT
            id,
            codigo,
            tipo_desconto,
            valor_desconto
        FROM cupons
        WHERE id = :id
        AND ativo = 1
        AND NOW() BETWEEN validade_inicio AND validade_fim
        AND (
            limite_utilizacoes IS NULL
            OR utilizacoes < limite_utilizacoes
        )
        LIMIT 1
    ");

    $consulta->execute([
        ':id' => (int) $_SESSION['cupom_aplicado']['id']
    ]);

    $cupomAtual = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$cupomAtual) {

        unset($_SESSION['cupom_aplicado']);

        $_SESSION['mensagem_carrinho'] = 'O cupom não está mais disponível.';

    } else {

        $subtotalCentavos = (int) round($totalCarrinho * 100);

        if ($cupomAtual['tipo_desconto'] === 'porcentagem') {

            $descontoCentavos = (int) round(
                $subtotalCentavos * ((float) $cupomAtual['valor_desconto'] / 100)
            );

        } else {

            $descontoCentavos = (int) round(
                (float) $cupomAtual['valor_desconto'] * 100
            );

        }

        $descontoCentavos = min(
            max(0, $descontoCentavos),
            $subtotalCentavos
        );

        $valorDesconto = $descontoCentavos / 100;

        $totalComDesconto = (
            $subtotalCentavos - $descontoCentavos
        ) / 100;

    }
}


// 6. Prepara as informações da página

$mensagem = $_SESSION['mensagem_carrinho'] ?? '';

unset($_SESSION['mensagem_carrinho']);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu carrinho | NEXUS TECH</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        /* 7. Configurações gerais */

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(ellipse at 50% 0%, rgba(113, 104, 232, .10), transparent 45%),
                #090d17;
            color: #f1f3ff;
            font-family: Arial, sans-serif;
        }

        a {
            text-decoration: none;
        }


        /* 8. Menu superior */

        .navbar {
            padding: 19px 0;
            background: rgba(12, 17, 30, .94);
            border-bottom: 1px solid rgba(151, 164, 205, .12);
        }

        .marca {
            color: #f4f5ff;
            font-size: 23px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .marca span {
            color: #a7a0ff;
        }

        .voltar {
            color: #a3abc0;
            transition: .2s ease;
        }

        .voltar:hover {
            color: #a7a0ff;
        }


        /* 9. Área principal */

        .conteudo {
            width: 100%;
            max-width: 1180px;
            margin: auto;
            padding: 55px 20px 80px;
        }

        .titulo {
            margin-bottom: 8px;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .subtitulo {
            margin-bottom: 35px;
            color: #8e99b2;
            font-size: 15px;
        }


        /* 10. Cards dos produtos */

        .produto-card,
        .resumo-card,
        .vazio-card {
            background: linear-gradient(145deg, #141b2c, #101625);
            border: 1px solid rgba(151, 164, 205, .13);
            border-radius: 16px;
        }

        .produto-card {
            padding: 21px;
            margin-bottom: 15px;
            transition: .25s ease;
        }

        .produto-card:hover {
            border-color: rgba(167, 160, 255, .35);
            transform: translateY(-2px);
        }

        .imagem-produto {
            display: block;
            width: 100%;
            max-width: 110px;
            height: 105px;
            padding: 9px;
            object-fit: contain;
            background: #0b1120;
            border: 1px solid rgba(151, 164, 205, .10);
            border-radius: 12px;
        }

        .nome-produto {
            color: #f1f3ff;
            font-size: 16px;
            font-weight: 700;
        }

        .texto-secundario {
            color: #8995ae;
            font-size: 13px;
        }

        .preco {
            color: #a7a0ff;
            font-weight: 700;
        }


        /* 11. Controle de quantidade */

        .controle-quantidade {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-top: 8px;
        }

        .controle-quantidade form {
            margin: 0;
        }

        .btn-quantidade {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 33px;
            height: 33px;
            border: 1px solid rgba(167, 160, 255, .3);
            border-radius: 9px;
            background: rgba(139, 124, 255, .10);
            color: #b8b1ff;
            font-size: 19px;
            font-weight: bold;
            cursor: pointer;
            transition: .2s ease;
        }

        .btn-quantidade:hover {
            background: #7168e8;
            color: white;
        }

        .valor-quantidade {
            min-width: 22px;
            color: white;
            font-weight: bold;
            text-align: center;
        }


        /* 12. Botões de ação */

        .btn-remover {
            padding: 9px 13px;
            border: 1px solid rgba(251, 113, 133, .25);
            border-radius: 9px;
            background: rgba(251, 113, 133, .08);
            color: #fb91a5;
            font-size: 13px;
            transition: .2s ease;
        }

        .btn-remover:hover {
            background: rgba(251, 113, 133, .18);
            color: white;
        }

        .btn-loja {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border: 1px solid transparent;
            border-radius: 10px;
            background: linear-gradient(135deg, #8176f5, #6258d9);
            color: white;
            font-weight: 700;
            transition: .2s ease;
        }

        .btn-loja:hover {
            background: linear-gradient(135deg, #948bff, #7168e8);
            color: white;
            transform: translateY(-1px);
        }

        .btn-limpar {
            padding: 9px 13px;
            border: 1px solid rgba(251, 113, 133, .25);
            border-radius: 9px;
            background: transparent;
            color: #fb91a5;
            font-size: 13px;
            transition: .2s ease;
        }

        .btn-limpar:hover {
            background: rgba(251, 113, 133, .10);
        }


        /* 13. Resumo da compra */

        .resumo-card {
            position: sticky;
            top: 25px;
            padding: 25px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, .18);
        }

        .resumo-card h4 {
            color: #f4f5ff;
            font-size: 19px;
        }

        .linha-resumo {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin: 16px 0;
            color: #a3abc0;
            font-size: 14px;
        }

        .linha-resumo.total {
            padding-top: 19px;
            border-top: 1px solid rgba(151, 164, 205, .16);
            color: #f4f5ff;
            font-size: 20px;
            font-weight: 800;
        }


        /* 14. Campo de cupom */

        .campo-cupom {
            min-width: 0;
            background: #0b1120 !important;
            border: 1px solid rgba(151, 164, 205, .2) !important;
            color: white !important;
        }

        .campo-cupom::placeholder {
            color: #69758d;
        }

        .campo-cupom:focus {
            border-color: #8b7cff !important;
            box-shadow: 0 0 0 3px rgba(139, 124, 255, .12) !important;
        }


        /* 15. Carrinho vazio */

        .vazio-card {
            padding: 65px 20px;
            text-align: center;
        }

        .icone-vazio {
            margin-bottom: 20px;
            font-size: 55px;
        }

        .vazio-card h3 {
            color: #f1f3ff;
            font-size: 22px;
            font-weight: 700;
        }

        .vazio-card p {
            margin-bottom: 20px;
        }


        /* 16. Ajustes para telas menores */

        @media (max-width: 991px) {

            .resumo-card {
                position: static;
            }

        }

        @media (max-width: 576px) {

            .conteudo {
                padding: 38px 15px 55px;
            }

            .titulo {
                font-size: 27px;
            }

            .produto-card {
                padding: 14px;
            }

            .imagem-produto {
                max-width: 78px;
                height: 78px;
            }

            .nome-produto {
                font-size: 14px;
            }

            .resumo-card {
                padding: 20px;
            }

            .btn-loja {
                padding: 11px 13px;
            }

        }

    </style>

</head>

<body>

    <!-- 17. Menu superior -->

    <nav class="navbar">

        <div class="container d-flex justify-content-between align-items-center">

            <a href="index.php" class="marca">
                NEXUS<span>TECH</span>
            </a>

            <a href="index.php" class="voltar">
                 Voltar à loja
            </a>

        </div>

    </nav>


    <!-- 18. Conteúdo do carrinho -->

    <main class="conteudo">

        <h1 class="titulo">
            Meu carrinho
        </h1>

        <p class="subtitulo">
            Confira seus produtos antes de continuar.
        </p>


        <!-- Mensagens do carrinho -->

        <?php if ($mensagem): ?>

            <div class="alert alert-info">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>


        <?php if (empty($produtosCarrinho)): ?>


            <!-- Carrinho vazio -->

            <div class="vazio-card">

                <div class="icone-vazio">
                    🛒
                </div>

                <h3>
                    Seu carrinho está vazio
                </h3>

                <p class="texto-secundario mt-2">
                    Explore nossa loja e encontre algo interessante.
                </p>

                <a href="index.php" class="btn-loja mt-3">
                    Explorar produtos
                </a>

            </div>


        <?php else: ?>


            <div class="row g-4">

                <!-- 19. Lista de produtos -->

                <div class="col-lg-8">

                    <?php foreach ($produtosCarrinho as $produto): ?>

                        <div class="produto-card">

                            <div class="row align-items-center g-3">

                                <div class="col-3 col-md-2">

                                    <?php if (!empty($produto['imagem'])): ?>

                                        <img
                                            src="<?= htmlspecialchars($produto['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                            class="imagem-produto"
                                            alt="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                        >

                                    <?php else: ?>

                                        <div class="imagem-produto d-flex align-items-center justify-content-center">
                                            <span>📦</span>
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div class="col-9 col-md-4">

                                    <div class="nome-produto">
                                        <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>

                                    <div class="texto-secundario mt-1">
                                        <?= number_format($produto['preco'], 2, ',', '.') ?> NC por unidade
                                    </div>

                                    <div class="texto-secundario mt-2">
                                        Quantidade
                                    </div>

                                    <div class="controle-quantidade">

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="token_carrinho"
                                                value="<?= htmlspecialchars($_SESSION['token_carrinho'], ENT_QUOTES, 'UTF-8') ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id_produto"
                                                value="<?= (int) $produto['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="diminuir_quantidade"
                                                class="btn-quantidade"
                                                aria-label="Diminuir quantidade"
                                            >
                                                −
                                            </button>

                                        </form>


                                        <span class="valor-quantidade">
                                            <?= (int) $produto['quantidade'] ?>
                                        </span>


                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="token_carrinho"
                                                value="<?= htmlspecialchars($_SESSION['token_carrinho'], ENT_QUOTES, 'UTF-8') ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id_produto"
                                                value="<?= (int) $produto['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="aumentar_quantidade"
                                                class="btn-quantidade"
                                                aria-label="Aumentar quantidade"
                                            >
                                                +
                                            </button>

                                        </form>

                                    </div>

                                    <div class="texto-secundario mt-2">
                                        Estoque: <?= (int) $produto['estoque'] ?>
                                    </div>

                                </div>


                                <div class="col-12 col-md-6 text-md-end">

                                    <div class="preco mb-2">
                                        <?= number_format($produto['subtotal'], 2, ',', '.') ?> NC
                                    </div>

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="token_carrinho"
                                            value="<?= htmlspecialchars($_SESSION['token_carrinho'], ENT_QUOTES, 'UTF-8') ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id_produto"
                                            value="<?= (int) $produto['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="remover_produto"
                                            class="btn-remover"
                                        >
                                            🗑 Remover produto
                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>


                    <!-- 20. Ações da lista -->

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">

                        <a href="index.php" class="voltar">
                             Continuar comprando
                        </a>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="token_carrinho"
                                value="<?= htmlspecialchars($_SESSION['token_carrinho'], ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <button
                                type="submit"
                                name="limpar_carrinho"
                                class="btn-limpar"
                                onclick="return confirm('Deseja remover todos os produtos do carrinho?')"
                            >
                                Esvaziar carrinho
                            </button>

                        </form>

                    </div>

                </div>


                <!-- 21. Resumo do pedido -->

                <div class="col-lg-4">

                    <div class="resumo-card">

                        <h4 class="fw-bold mb-4">
                            Resumo do pedido
                        </h4>

                        <div class="linha-resumo">

                            <span>Produtos</span>

                            <span>
                                <?= count($produtosCarrinho) ?>
                            </span>

                        </div>

                        <div class="linha-resumo">

                            <span>Quantidade de itens</span>

                            <span>
                                <?= array_sum(array_column($produtosCarrinho, 'quantidade')) ?>
                            </span>

                        </div>


                        <!-- Cupom de desconto -->

                        <div class="mt-4 mb-3">

                            <label for="codigo_cupom" class="form-label fw-bold">
                                Cupom de desconto
                            </label>

                            <form method="POST" class="d-flex gap-2">

                                <input
                                    type="hidden"
                                    name="token_carrinho"
                                    value="<?= htmlspecialchars($_SESSION['token_carrinho'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <input
                                    type="text"
                                    name="codigo_cupom"
                                    id="codigo_cupom"
                                    class="form-control campo-cupom"
                                    placeholder="Digite seu cupom"
                                    value="<?= htmlspecialchars($_SESSION['cupom_aplicado']['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="50"
                                    required
                                >

                                <button
                                    type="submit"
                                    name="aplicar_cupom"
                                    class="btn-loja"
                                >
                                    Aplicar
                                </button>

                            </form>

                        </div>


                        <!-- Valores da compra -->

                        <div class="linha-resumo">

                            <span>Subtotal</span>

                            <span>
                                <?= number_format($totalCarrinho, 2, ',', '.') ?> NC
                            </span>

                        </div>


                        <?php if ($valorDesconto > 0): ?>

                            <div class="linha-resumo">

                                <span>Desconto</span>

                                <span style="color: #4ade80;">
                                    - <?= number_format($valorDesconto, 2, ',', '.') ?> NC
                                </span>

                            </div>

                        <?php endif; ?>


                        <div class="linha-resumo total">

                            <span>Total</span>

                            <span class="preco">
                                <?= number_format($totalComDesconto, 2, ',', '.') ?> NC
                            </span>

                        </div>


                        <!-- Finalização da compra -->

                        <form action="finalizar_compra.php" method="POST">

                            <input
                                type="hidden"
                                name="token_finalizar_compra"
                                value="<?= htmlspecialchars($_SESSION['token_finalizar_compra'], ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <button
                                type="submit"
                                class="btn-loja w-100 mt-3"
                                id="botao-finalizar"
                            >
                                Finalizar compra
                            </button>

                        </form>

                        <p class="texto-secundario text-center mt-3 mb-0">
                            Moeda virtual NEXUS Coins
                        </p>

                    </div>

                </div>

            </div>

        <?php endif; ?>

    </main>


    <!-- 22. Scripts -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        const formularioCompra = document.querySelector('form[action="finalizar_compra.php"]');

        if (formularioCompra) {

            formularioCompra.addEventListener('submit', function () {

                const botao = document.getElementById('botao-finalizar');

                botao.disabled = true;
                botao.textContent = 'Processando compra...';

            });

        }

    </script>

</body>

</html>
