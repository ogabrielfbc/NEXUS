
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

// Conta os produtos cadastrados
$totalProdutos = $pdo->query("
    SELECT COUNT(*)
    FROM produtos
")->fetchColumn();

// Conta os produtos ativos
$produtosAtivos = $pdo->query("
    SELECT COUNT(*)
    FROM produtos
    WHERE ativo = 1
")->fetchColumn();

// Conta os pedidos
$totalPedidos = $pdo->query("
    SELECT COUNT(*)
    FROM pedidos
")->fetchColumn();

// Conta os clientes
$totalClientes = $pdo->query("
    SELECT COUNT(*)
    FROM usuarios
    WHERE tipo = 'cliente'
")->fetchColumn();

// Conta os cupons cadastrados
$totalCupons = $pdo->query("
    SELECT COUNT(*)
    FROM cupons
")->fetchColumn();

// Conta as avaliações recebidas
$totalAvaliacoes = $pdo->query("
    SELECT COUNT(*)
    FROM avaliacoes
")->fetchColumn();

// Conta os pedidos pendentes
$pedidosPendentes = $pdo->query("
    SELECT COUNT(*)
    FROM pedidos
    WHERE status = 'pendente'
")->fetchColumn();

$nomeAdministrador = $_SESSION['usuario_nome'] ?? 'Administrador';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Painel Administrativo | NEXUS</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #080d18;
            color: #fff;
        }

        .menu-lateral {
            position: fixed;
            width: 250px;
            height: 100vh;
            background: #0d1626;
            border-right: 1px solid #20314a;
            padding: 30px 20px;
        }

        .logo {
            color: #168bff;
            font-size: 30px;
            font-weight: bold;
            letter-spacing: 4px;
            margin-bottom: 45px;
        }

        .menu-lateral a {
            display: block;
            padding: 15px;
            margin-bottom: 8px;
            color: #a8b6c9;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
        }

        .menu-lateral a:hover,
        .menu-lateral a.ativo {
            background: #142d4a;
            color: #168bff;
        }

        .conteudo {
            margin-left: 250px;
            padding: 35px;
        }

        .cabecalho {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            gap: 20px;
        }

        .cabecalho h1 {
            font-size: 27px;
        }

        .cabecalho p {
            color: #8998ad;
            margin-top: 8px;
        }

        .sair {
            color: #ff7777;
            text-decoration: none;
        }

        .cartoes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .cartao {
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 12px;
            padding: 25px;
            transition: 0.3s;
        }

        .cartao:hover {
            border-color: #168bff;
            transform: translateY(-3px);
        }

        .cartao h3 {
            color: #8998ad;
            font-size: 14px;
            font-weight: normal;
            margin-bottom: 18px;
        }

        .cartao strong {
            font-size: 32px;
            color: #168bff;
        }

        .boas-vindas {
            margin-top: 35px;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 12px;
            padding: 30px;
        }

        .boas-vindas h2 {
            margin-bottom: 12px;
        }

        .boas-vindas p {
            color: #8998ad;
            line-height: 1.6;
        }

        @media (max-width: 1000px) {

            .cartoes {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 650px) {

            .menu-lateral {
                position: relative;
                width: 100%;
                height: auto;
            }

            .logo {
                margin-bottom: 20px;
            }

            .conteudo {
                margin-left: 0;
                padding: 20px;
            }

            .cartoes {
                grid-template-columns: 1fr;
            }

            .cabecalho {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

    <aside class="menu-lateral">

        <div class="logo">NEXUS</div>

        <a href="painel.php" class="ativo">Visão geral</a>
        <a href="produtos.php">Produtos</a>
        <a href="pedidos.php">Pedidos</a>
        <a href="clientes.php">Clientes</a>
        <a href="cupons.php">Cupons</a>
        <a href="avaliacoes.php">Avaliações</a>
        <a href="../index.php">Voltar para a loja</a>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <div>

                <h1>Painel administrativo</h1>

                <p>
                    Bem-vindo, <?= htmlspecialchars($nomeAdministrador) ?>.
                </p>

            </div>

            <a href="sair.php" class="sair">Sair da conta</a>

        </header>

        <section class="cartoes">

            <div class="cartao">
                <h3>Total de produtos</h3>
                <strong><?= $totalProdutos ?></strong>
            </div>

            <div class="cartao">
                <h3>Produtos ativos</h3>
                <strong><?= $produtosAtivos ?></strong>
            </div>

            <div class="cartao">
                <h3>Total de pedidos</h3>
                <strong><?= $totalPedidos ?></strong>
            </div>

            <div class="cartao">
                <h3>Pedidos pendentes</h3>
                <strong><?= $pedidosPendentes ?></strong>
            </div>

            <div class="cartao">
                <h3>Clientes cadastrados</h3>
                <strong><?= $totalClientes ?></strong>
            </div>

            <div class="cartao">
                <h3>Cupons cadastrados</h3>
                <strong><?= $totalCupons ?></strong>
            </div>

            <div class="cartao">
                <h3>Avaliações recebidas</h3>
                <strong><?= $totalAvaliacoes ?></strong>
            </div>

        </section>

        <section class="boas-vindas">

            <h2>Central de gerenciamento</h2>

            <p>
                Gerencie os produtos da loja, acompanhe os pedidos,
                consulte os clientes cadastrados, controle os cupons
                de desconto e administre as avaliações dos produtos.
                Todas as informações principais estão reunidas neste painel.
            </p>

        </section>

    </main>

</body>
</html>
