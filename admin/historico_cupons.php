
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

// Identificar o cupom
$cupomId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$cupomId) {
    header('Location: cupons.php');
    exit;
}

// Buscar informações do cupom
$sql = "SELECT *
        FROM cupons
        WHERE id = :id
        LIMIT 1";

$consulta = $pdo->prepare($sql);
$consulta->execute([
    ':id' => $cupomId
]);

$cupom = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$cupom) {
    header('Location: cupons.php');
    exit;
}

// Buscar histórico de utilizações
$sql = "SELECT
            uc.id,
            uc.valor_desconto,
            uc.criado_em,
            u.nome AS nome_cliente,
            u.email,
            p.id AS pedido_id,
            p.total AS total_pedido,
            p.status AS status_pedido
        FROM uso_cupons uc
        INNER JOIN usuarios u
            ON uc.usuario_id = u.id
        INNER JOIN pedidos p
            ON uc.pedido_id = p.id
        WHERE uc.cupom_id = :cupom_id
        ORDER BY uc.criado_em DESC";

$consulta = $pdo->prepare($sql);
$consulta->execute([
    ':cupom_id' => $cupomId
]);

$utilizacoes = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Total de descontos concedidos
$totalDescontos = 0;

foreach ($utilizacoes as $utilizacao) {
    $totalDescontos += (float) $utilizacao['valor_desconto'];
}

function formatarCoinsHistorico($valor)
{
    return number_format((float) $valor, 2, ',', '.');
}

function formatarDataHistorico($data)
{
    return date('d/m/Y H:i', strtotime($data));
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Histórico de Cupons | NEXUS</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #080d18;
            color: white;
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
            gap: 20px;
            margin-bottom: 30px;
        }

        .cabecalho p {
            color: #8998ad;
            margin-top: 8px;
        }

        .botao {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 8px;
            background: #087bea;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .botao:hover {
            background: #0064c7;
        }

        .resumo {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .card {
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 12px;
            padding: 22px;
        }

        .card p {
            color: #8998ad;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .card h2 {
            font-size: 25px;
            color: #168bff;
        }

        .tabela-container {
            overflow-x: auto;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 18px;
            text-align: left;
            border-bottom: 1px solid #243651;
        }

        th {
            color: #8998ad;
            font-size: 12px;
            font-weight: normal;
        }

        td {
            font-size: 14px;
        }

        .cliente {
            font-weight: bold;
        }

        .email {
            display: block;
            color: #8998ad;
            font-size: 12px;
            margin-top: 5px;
        }

        .desconto {
            color: #42d392;
            font-weight: bold;
        }

        .pedido {
            color: #168bff;
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            background: #243651;
        }

        .status.confirmado {
            color: #42d392;
            background: #123329;
        }

        .status.pendente {
            color: #ffc66d;
            background: #3b3020;
        }

        .status.cancelado {
            color: #ff7777;
            background: #3a2028;
        }

        .vazio {
            text-align: center;
            padding: 40px;
            color: #8998ad;
        }

        @media (max-width: 900px) {

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

            .cabecalho {
                align-items: flex-start;
                flex-direction: column;
            }

            .resumo {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

    <aside class="menu-lateral">

        <div class="logo">NEXUS</div>

        <a href="painel.php">Visão geral</a>
        <a href="produtos.php">Produtos</a>
        <a href="pedidos.php">Pedidos</a>
        <a href="clientes.php">Clientes</a>
        <a href="cupons.php" class="ativo">Cupons</a>
        <a href="../index.php">Voltar para a loja</a>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <div>
                <h1>Histórico de utilizações</h1>

                <p>
                    Acompanhe os clientes que utilizaram este cupom.
                </p>
            </div>

            <a href="cupons.php" class="botao">
                Voltar para cupons
            </a>

        </header>

        <section class="resumo">

            <div class="card">
                <p>Código do cupom</p>
                <h2>
                    <?= htmlspecialchars($cupom['codigo'], ENT_QUOTES, 'UTF-8') ?>
                </h2>
            </div>

            <div class="card">
                <p>Total de utilizações</p>
                <h2><?= count($utilizacoes) ?></h2>
            </div>

            <div class="card">
                <p>Total concedido em descontos</p>
                <h2>
                    <?= formatarCoinsHistorico($totalDescontos) ?> NC
                </h2>
            </div>

        </section>

        <div class="tabela-container">

            <table>

                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Pedido</th>
                        <th>Valor do desconto</th>
                        <th>Total do pedido</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($utilizacoes) > 0): ?>

                        <?php foreach ($utilizacoes as $utilizacao): ?>

                            <tr>

                                <td>

                                    <span class="cliente">
                                        <?= htmlspecialchars(
                                            $utilizacao['nome_cliente'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <span class="email">
                                        <?= htmlspecialchars(
                                            $utilizacao['email'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                </td>

                                <td>
                                    <span class="pedido">
                                        #<?= (int) $utilizacao['pedido_id'] ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="desconto">
                                        - <?= formatarCoinsHistorico(
                                            $utilizacao['valor_desconto']
                                        ) ?> NC
                                    </span>
                                </td>

                                <td>
                                    <?= formatarCoinsHistorico(
                                        $utilizacao['total_pedido']
                                    ) ?> NC
                                </td>

                                <td>
                                    <span class="status <?= htmlspecialchars(
                                        $utilizacao['status_pedido'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">
                                        <?= ucfirst(htmlspecialchars(
                                            $utilizacao['status_pedido'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= formatarDataHistorico(
                                        $utilizacao['criado_em']
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="vazio">
                                Este cupom ainda não foi utilizado.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</body>

</html>
