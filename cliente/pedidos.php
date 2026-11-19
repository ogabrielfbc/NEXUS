
<?php

require_once __DIR__ . '/proteger_cliente.php';

$pdo = require __DIR__ . '/../config/database.php';

$usuarioId = $_SESSION['usuario_id'];

// Busca os pedidos do cliente.
$sql = "SELECT id, data_pedido, status, total
        FROM pedidos
        WHERE usuario_id = :usuario_id
        ORDER BY data_pedido DESC";

$consulta = $pdo->prepare($sql);

$consulta->execute([
    ':usuario_id' => $usuarioId
]);

$pedidos = $consulta->fetchAll();

// Busca os produtos de cada pedido.
foreach ($pedidos as &$pedido) {

    $sql = "SELECT nome_produto, quantidade, preco_unitario
            FROM itens_pedido
            WHERE pedido_id = :pedido_id";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':pedido_id' => $pedido['id']
    ]);

    $pedido['itens'] = $consulta->fetchAll();
}

unset($pedido);

function formatarCoins($valor)
{
    return number_format((float) $valor, 0, ',', '.');
}

function nomeStatus($status)
{
    $nomes = [
        'pendente' => 'Pendente',
        'confirmado' => 'Confirmado',
        'cancelado' => 'Cancelado'
    ];

    return $nomes[$status] ?? 'Indefinido';
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meus pedidos | NEXUS TECH</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #080f1c;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        header {
            background: #101a2b;
            border-bottom: 1px solid #1d304a;
            padding: 18px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            color: #38a5ff;
            font-size: 23px;
            font-weight: bold;
            letter-spacing: 2px;
            text-decoration: none;
        }

        .voltar {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
        }

        .voltar:hover {
            color: #38a5ff;
        }

        .conteudo {
            width: 90%;
            max-width: 1100px;
            margin: 45px auto;
        }

        .titulo {
            margin-bottom: 30px;
        }

        .titulo h1 {
            font-size: 30px;
            font-weight: bold;
        }

        .titulo p {
            color: #94a3b8;
        }

        .pedido {
            background: #101a2b;
            border: 1px solid #1d304a;
            border-radius: 14px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .pedido-topo {
            padding: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            border-bottom: 1px solid #263b57;
        }

        .pedido-numero {
            font-size: 18px;
            font-weight: bold;
        }

        .pedido-data {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 6px;
        }

        .status {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .pendente {
            background: #493b1d;
            color: #ffd36a;
        }

        .confirmado {
            background: #153e32;
            color: #55e6a5;
        }

        .cancelado {
            background: #492626;
            color: #ff8585;
        }

        .pedido-corpo {
            padding: 22px;
        }

        .produto {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #263b57;
        }

        .produto:last-child {
            border-bottom: none;
        }

        .produto-nome {
            font-weight: bold;
        }

        .produto-detalhes {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 5px;
        }

        .produto-preco {
            color: #38a5ff;
            font-weight: bold;
            white-space: nowrap;
        }

        .pedido-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            margin-top: 15px;
            border-top: 1px solid #263b57;
        }

        .pedido-total strong {
            font-size: 21px;
            color: #38a5ff;
        }

        .vazio {
            background: #101a2b;
            border: 1px solid #1d304a;
            border-radius: 14px;
            text-align: center;
            padding: 55px 20px;
        }

        .vazio h3 {
            font-size: 21px;
            margin-bottom: 10px;
        }

        .vazio p {
            color: #94a3b8;
        }

        .btn-voltar {
            display: inline-block;
            margin-top: 15px;
            padding: 11px 20px;
            background: #1685e5;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }

        .btn-voltar:hover {
            background: #0872ce;
            color: white;
        }

        @media (max-width: 600px) {

            .conteudo {
                margin-top: 30px;
            }

            .pedido-topo,
            .pedido-corpo {
                padding: 17px;
            }

            .pedido-total strong {
                font-size: 17px;
            }

        }

    </style>

</head>

<body>

    <header>

        <a href="../index.php" class="logo">NEXUS TECH</a>

        <a href="minha_conta.php" class="voltar">
            Minha conta
        </a>

    </header>

    <main class="conteudo">

        <div class="titulo">

            <h1>Meus pedidos</h1>

            <p>
                Acompanhe suas compras e consulte os detalhes dos seus pedidos.
            </p>

        </div>

        <?php if (count($pedidos) > 0): ?>

            <?php foreach ($pedidos as $pedido): ?>

                <div class="pedido">

                    <div class="pedido-topo">

                        <div>

                            <div class="pedido-numero">
                                Pedido #<?= (int) $pedido['id'] ?>
                            </div>

                            <div class="pedido-data">
                                <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?>
                            </div>

                        </div>

                        <span class="status <?= htmlspecialchars($pedido['status']) ?>">
                            <?= nomeStatus($pedido['status']) ?>
                        </span>

                    </div>

                    <div class="pedido-corpo">

                        <?php if (count($pedido['itens']) > 0): ?>

                            <?php foreach ($pedido['itens'] as $item): ?>

                                <div class="produto">

                                    <div>

                                        <div class="produto-nome">
                                            <?= htmlspecialchars($item['nome_produto']) ?>
                                        </div>

                                        <div class="produto-detalhes">
                                            Quantidade: <?= (int) $item['quantidade'] ?>
                                        </div>

                                    </div>

                                    <div class="produto-preco">
                                        <?= formatarCoins($item['preco_unitario'] * $item['quantidade']) ?> NC
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <p class="text-secondary mb-0">
                                Nenhum produto registrado neste pedido.
                            </p>

                        <?php endif; ?>

                        <div class="pedido-total">

                            <span>Total do pedido</span>

                            <strong>
                                <?= formatarCoins($pedido['total']) ?> NC
                            </strong>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="vazio">

                <h3>Você ainda não realizou nenhum pedido.</h3>

                <p>
                    Quando fizer uma compra, ela aparecerá aqui.
                </p>

                <a href="../index.php" class="btn-voltar">
                    Explorar produtos
                </a>

            </div>

        <?php endif; ?>

    </main>

</body>

</html>
