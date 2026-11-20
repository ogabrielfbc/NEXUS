
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/config/database.php';

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

if (empty($_SESSION['token_finalizar_compra'])) {
    $_SESSION['token_finalizar_compra'] = bin2hex(random_bytes(32));
}

if (empty($_SESSION['token_carrinho'])) {
    $_SESSION['token_carrinho'] = bin2hex(random_bytes(32));
}

// Processa as ações do carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token_carrinho'] ?? '';

    if (!hash_equals($_SESSION['token_carrinho'], $token)) {

        $_SESSION['mensagem_carrinho'] = 'Solicitação inválida.';

    } else {

        $idProduto = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);

        // Aumentar quantidade
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

        // Diminuir quantidade
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

        // Remover produto inteiro
        } elseif (isset($_POST['remover_produto'])) {

            if ($idProduto && isset($_SESSION['carrinho'][$idProduto])) {

                unset($_SESSION['carrinho'][$idProduto]);
                $_SESSION['mensagem_carrinho'] = 'Produto removido do carrinho.';

            }

        // Aplicar cupom de desconto
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

        // Limpar carrinho
        } elseif (isset($_POST['limpar_carrinho'])) {

            $_SESSION['carrinho'] = [];
            unset($_SESSION['cupom_aplicado']);

            $_SESSION['mensagem_carrinho'] = 'Carrinho esvaziado.';

        }
    }

    header('Location: carrinho.php');
    exit;
}

// Busca os produtos adicionados ao carrinho
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

// Valores iniciais do desconto
$valorDesconto = 0;
$totalComDesconto = $totalCarrinho;
$cupomAtual = null;

// Verifica o cupom aplicado
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

$mensagem = $_SESSION['mensagem_carrinho'] ?? '';
unset($_SESSION['mensagem_carrinho']);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu carrinho | NEXUS TECH</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            background: #080d18;
            color: #e8eefc;
            font-family: Arial, sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background: #0c1424;
            border-bottom: 1px solid #1b2c49;
            padding: 18px 0;
        }

        .marca {
            color: #35aaff;
            font-size: 23px;
            font-weight: bold;
            letter-spacing: 1px;
            text-decoration: none;
        }

        .voltar {
            color: #b7c5dc;
            text-decoration: none;
            transition: .2s;
        }

        .voltar:hover {
            color: #35aaff;
        }

        .conteudo {
            max-width: 1150px;
            margin: auto;
            padding: 55px 20px;
        }

        .titulo {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .subtitulo {
            color: #8293ad;
            margin-bottom: 35px;
        }

        .produto-card,
        .resumo-card,
        .vazio-card {
            background: #101a2c;
            border: 1px solid #1c2e4c;
            border-radius: 14px;
        }

        .produto-card {
            padding: 20px;
            margin-bottom: 15px;
        }

        .imagem-produto {
            width: 100%;
            max-width: 110px;
            height: 100px;
            object-fit: contain;
            background: #0b1322;
            border-radius: 10px;
            padding: 8px;
        }

        .nome-produto {
            font-size: 17px;
            font-weight: bold;
            color: #f1f5ff;
        }

        .texto-secundario {
            color: #8293ad;
            font-size: 14px;
        }

        .preco {
            color: #39aaff;
            font-weight: bold;
        }

        .resumo-card {
            padding: 25px;
            position: sticky;
            top: 25px;
        }

        .linha-resumo {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            color: #aab9d0;
        }

        .total {
            border-top: 1px solid #263956;
            padding-top: 18px;
            font-size: 21px;
            color: #fff;
            font-weight: bold;
        }

        .controle-quantidade {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-quantidade {
            width: 34px;
            height: 34px;
            border: 1px solid #29496e;
            background: #142640;
            color: #45b2ff;
            border-radius: 8px;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: .2s;
        }

        .btn-quantidade:hover {
            background: #1685d5;
            color: white;
        }

        .valor-quantidade {
            min-width: 20px;
            text-align: center;
            font-weight: bold;
            color: white;
        }

        .btn-remover {
            background: #351a27;
            border: 1px solid #633044;
            color: #ff829b;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
        }

        .btn-remover:hover {
            background: #572235;
            color: #fff;
        }

        .btn-loja {
            background: #1685d5;
            color: white;
            border: none;
            border-radius: 9px;
            padding: 12px 18px;
            text-decoration: none;
            display: inline-block;
            transition: .2s;
        }

        .btn-loja:hover {
            background: #0870bb;
            color: white;
        }

        .btn-limpar {
            background: transparent;
            border: 1px solid #633044;
            color: #ff829b;
            border-radius: 8px;
            padding: 9px 13px;
        }

        .btn-limpar:hover {
            background: #351a27;
        }

        .vazio-card {
            padding: 60px 20px;
            text-align: center;
        }

        .icone-vazio {
            font-size: 55px;
            color: #328fd4;
            margin-bottom: 20px;
        }

        @media (max-width: 576px) {

            .titulo {
                font-size: 26px;
            }

            .imagem-produto {
                max-width: 75px;
                height: 75px;
            }

            .produto-card {
                padding: 14px;
            }

        }

    </style>

</head>
<script>
document.querySelector('form[action="finalizar_compra.php"]')
    .addEventListener('submit', function () {

        const botao = document.getElementById('botao-finalizar');

        botao.disabled = true;
        botao.textContent = 'Processando compra...';

    });
</script>

<body>

    <nav class="navbar">

        <div class="container d-flex justify-content-between align-items-center">

            <a href="index.php" class="marca">
                NEXUS TECH
            </a>

            <a href="index.php" class="voltar">
                ← Voltar à loja
            </a>

        </div>

    </nav>

    <main class="conteudo">

        <h1 class="titulo">Meu carrinho</h1>

        <p class="subtitulo">
            Confira seus produtos antes de continuar.
        </p>

        <?php if ($mensagem): ?>

            <div class="alert alert-info">
                <?= htmlspecialchars($mensagem) ?>
            </div>

        <?php endif; ?>

        <?php if (empty($produtosCarrinho)): ?>

            <div class="vazio-card">

                <div class="icone-vazio">🛒</div>

                <h3>Seu carrinho está vazio</h3>

                <p class="texto-secundario mt-2">
                    Explore nossa loja e encontre algo interessante.
                </p>

                <a href="index.php" class="btn-loja mt-3">
                    Explorar produtos
                </a>

            </div>

        <?php else: ?>

            <div class="row g-4">

                <div class="col-lg-8">

                    <?php foreach ($produtosCarrinho as $produto): ?>

                        <div class="produto-card">

                            <div class="row align-items-center g-3">

                                <div class="col-3 col-md-2">

                                    <?php if (!empty($produto['imagem'])): ?>

                                        <img
                                            src="<?= htmlspecialchars($produto['imagem']) ?>"
                                            class="imagem-produto"
                                            alt="<?= htmlspecialchars($produto['nome']) ?>"
                                        >

                                    <?php else: ?>

                                        <div class="imagem-produto d-flex align-items-center justify-content-center">
                                            <span>📦</span>
                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div class="col-9 col-md-4">

                                    <div class="nome-produto">
                                        <?= htmlspecialchars($produto['nome']) ?>
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
                                                value="<?= htmlspecialchars($_SESSION['token_carrinho']) ?>"
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
                                                value="<?= htmlspecialchars($_SESSION['token_carrinho']) ?>"
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

                                    <div class="texto-secundario mt-1">
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
                                            value="<?= htmlspecialchars($_SESSION['token_carrinho']) ?>"
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

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">

                        <a href="index.php" class="voltar">
                            ← Continuar comprando
                        </a>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="token_carrinho"
                                value="<?= htmlspecialchars($_SESSION['token_carrinho']) ?>"
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

                <div class="col-lg-4">

                    <div class="resumo-card">

                        <h4 class="fw-bold mb-4">
                            Resumo do pedido
                        </h4>

                        <div class="linha-resumo">
                            <span>Produtos</span>
                            <span><?= count($produtosCarrinho) ?></span>
                        </div>

                        <div class="linha-resumo">
                            <span>Quantidade de itens</span>
                            <span>
                                <?= array_sum(array_column($produtosCarrinho, 'quantidade')) ?>
                            </span>
                        </div>

                        <div class="mt-4 mb-3">

                            <label for="codigo_cupom" class="form-label fw-bold">
                                Cupom de desconto
                            </label>

                            <form method="POST" class="d-flex gap-2">

                                <input
                                    type="hidden"
                                    name="token_carrinho"
                                    value="<?= htmlspecialchars($_SESSION['token_carrinho']) ?>"
                                >

                                <input
                                    type="text"
                                    name="codigo_cupom"
                                    id="codigo_cupom"
                                    class="form-control"
                                    placeholder="Digite seu cupom"
                                    value="<?= htmlspecialchars($_SESSION['cupom_aplicado']['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    style="background: #0b1322; border: 1px solid #29496e; color: white;"
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

                        <div class="linha-resumo">
                            <span>Subtotal</span>
                            <span>
                                <?= number_format($totalCarrinho, 2, ',', '.') ?> NC
                            </span>
                        </div>

                        <?php if ($valorDesconto > 0): ?>

                            <div class="linha-resumo">
                                <span>Desconto</span>

                                <span style="color: #39c98a;">
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

</body>

</html>
