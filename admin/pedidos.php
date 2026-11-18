<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

// 1. Criar token de segurança
if (empty($_SESSION['token_pedidos'])) {
    $_SESSION['token_pedidos'] = bin2hex(random_bytes(32));
}

// 2. Atualizar status do pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';

    $statusPermitidos = [
        'pendente',
        'confirmado',
        'cancelado'
    ];

    if (
        empty($_SESSION['token_pedidos']) ||
        !hash_equals($_SESSION['token_pedidos'], $token) ||
        !$id ||
        !in_array($status, $statusPermitidos, true)
    ) {
        $_SESSION['mensagem_pedidos'] = 'Solicitação inválida.';
    } else {

        try {

            $consulta = $pdo->prepare(
                "SELECT status
                 FROM pedidos
                 WHERE id = :id"
            );

            $consulta->execute([':id' => $id]);
            $pedidoAtual = $consulta->fetch();

            if (!$pedidoAtual) {

                $_SESSION['mensagem_pedidos'] = 'Pedido não encontrado.';

            } elseif ($pedidoAtual['status'] !== 'pendente') {

                $_SESSION['mensagem_pedidos'] =
                    'Somente pedidos pendentes podem ter o status alterado.';

            } else {

                $atualizar = $pdo->prepare(
                    "UPDATE pedidos
                     SET status = :status
                     WHERE id = :id
                     AND status = 'pendente'"
                );

                $atualizar->execute([
                    ':status' => $status,
                    ':id' => $id
                ]);

                $_SESSION['mensagem_pedidos'] =
                    'Status do pedido atualizado com sucesso.';
            }

        } catch (PDOException $erro) {

            $_SESSION['mensagem_pedidos'] =
                'Não foi possível atualizar o pedido.';
        }
    }

    header('Location: pedidos.php');
    exit;
}

// 3. Receber filtros
$pesquisa = trim($_GET['pesquisa'] ?? '');
$filtroStatus = $_GET['status'] ?? '';

$statusPermitidos = [
    'pendente',
    'confirmado',
    'cancelado'
];

if (!in_array($filtroStatus, $statusPermitidos, true)) {
    $filtroStatus = '';
}

// 4. Buscar pedidos
$sql = "SELECT
            p.id,
            p.usuario_id,
            p.data_pedido,
            p.forma_pagamento,
            p.status,
            p.total,
            u.nome AS cliente,
            u.email
        FROM pedidos p
        INNER JOIN usuarios u
            ON p.usuario_id = u.id
        WHERE 1 = 1";

$parametros = [];

if ($pesquisa !== '') {

    $sql .= " AND (
                CAST(p.id AS CHAR) LIKE :pesquisa
                OR u.nome LIKE :pesquisa
                OR u.email LIKE :pesquisa
              )";

    $parametros[':pesquisa'] = "%$pesquisa%";
}

if ($filtroStatus !== '') {

    $sql .= " AND p.status = :status";

    $parametros[':status'] = $filtroStatus;
}

$sql .= " ORDER BY p.data_pedido DESC";

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);

$pedidos = $consulta->fetchAll();

// 5. Buscar produtos dos pedidos
$consultaItens = $pdo->prepare(
    "SELECT
        nome_produto,
        quantidade,
        preco_unitario
     FROM itens_pedido
     WHERE pedido_id = :pedido_id
     ORDER BY id ASC"
);

// 6. Mensagem
$mensagem = $_SESSION['mensagem_pedidos'] ?? '';
unset($_SESSION['mensagem_pedidos']);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pedidos | NEXUS</title>

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
            margin-bottom: 30px;
        }

        .cabecalho p {
            color: #8998ad;
            margin-top: 8px;
        }

        .mensagem {
            padding: 15px;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 8px;
            color: #42d392;
            margin-bottom: 20px;
        }

        .filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .filtros input,
        .filtros select {
            padding: 13px;
            border: 1px solid #293b55;
            border-radius: 8px;
            background: #111a2b;
            color: white;
        }

        .filtros input {
            width: 100%;
            max-width: 350px;
        }

        .botao {
            padding: 12px 18px;
            border-radius: 8px;
            background: #087bea;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .botao:hover {
            background: #0064c7;
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
            min-width: 950px;
        }

        th, td {
            padding: 17px;
            text-align: left;
            border-bottom: 1px solid #243651;
        }

        th {
            color: #8998ad;
            font-size: 13px;
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

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: capitalize;
        }

        .pendente {
            background: #49391c;
            color: #ffc45c;
        }

        .confirmado {
            background: #123c32;
            color: #42d392;
        }

        .cancelado {
            background: #481f29;
            color: #ff7777;
        }

        .detalhes {
            background: #0b1322;
            padding: 18px;
        }

        .detalhes h3 {
            margin-bottom: 12px;
            color: #168bff;
        }

        .produto-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #243651;
        }

        .produto-item:last-child {
            border-bottom: none;
        }

        .acoes {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .acoes form {
            display: flex;
            gap: 6px;
        }

        .acoes select {
            padding: 8px;
            border: 1px solid #293b55;
            border-radius: 6px;
            background: #0b1322;
            color: white;
        }

        .botao-atualizar {
            padding: 8px 10px;
            border: none;
            border-radius: 6px;
            background: #087bea;
            color: white;
            cursor: pointer;
        }

        .vazio {
            text-align: center;
            padding: 35px;
            color: #8998ad;
        }

        .link-detalhes {
            color: #168bff;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 14px;
        }

        @media (max-width: 700px) {

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
                gap: 10px;
            }

            .filtros {
                flex-direction: column;
            }

            .filtros input {
                max-width: none;
            }
        }

    </style>

</head>

<body>

    <aside class="menu-lateral">

        <div class="logo">NEXUS</div>

        <a href="painel.php">Visão geral</a>
        <a href="produtos.php">Produtos</a>
        <a href="pedidos.php" class="ativo">Pedidos</a>
        <a href="clientes.php">Clientes</a>
        <a href="../index.php">Voltar para a loja</a>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <div>
                <h1>Pedidos</h1>
                <p>Gerencie os pedidos realizados na sua loja.</p>
            </div>

        </header>

        <?php if ($mensagem !== ''): ?>

            <div class="mensagem">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <form method="GET" class="filtros">

            <input
                type="text"
                name="pesquisa"
                placeholder="Pesquisar pedido ou cliente..."
                value="<?= htmlspecialchars($pesquisa, ENT_QUOTES, 'UTF-8') ?>"
            >

            <select name="status">

                <option value="">Todos os status</option>

                <option value="pendente" <?= $filtroStatus === 'pendente' ? 'selected' : '' ?>>
                    Pendentes
                </option>

                <option value="confirmado" <?= $filtroStatus === 'confirmado' ? 'selected' : '' ?>>
                    Confirmados
                </option>

                <option value="cancelado" <?= $filtroStatus === 'cancelado' ? 'selected' : '' ?>>
                    Cancelados
                </option>

            </select>

            <button type="submit" class="botao">
                Filtrar
            </button>

        </form>

        <div class="tabela-container">

            <table>

                <thead>

                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Pagamento</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Detalhes</th>
                        <th>Ações</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (count($pedidos) > 0): ?>

                        <?php foreach ($pedidos as $pedido): ?>

                            <?php
                            $consultaItens->execute([
                                ':pedido_id' => $pedido['id']
                            ]);

                            $itens = $consultaItens->fetchAll();
                            ?>

                            <tr>

                                <td>
                                    #<?= (int) $pedido['id'] ?>
                                </td>

                                <td>
                                    <span class="cliente">
                                        <?= htmlspecialchars($pedido['cliente'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>

                                    <span class="email">
                                        <?= htmlspecialchars($pedido['email'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>
                                    <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?>
                                </td>

                                <td>
                                    <?= ucfirst($pedido['forma_pagamento']) ?>
                                </td>

                                <td>
                                    R$ <?= number_format($pedido['total'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <span class="status <?= htmlspecialchars($pedido['status'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= ucfirst($pedido['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="link-detalhes"
                                        onclick="alternarDetalhes(<?= (int) $pedido['id'] ?>)"
                                    >
                                        Ver produtos
                                    </button>
                                </td>

                                <td>

                                    <?php if ($pedido['status'] === 'pendente'): ?>

                                        <div class="acoes">

                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $pedido['id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="token"
                                                    value="<?= htmlspecialchars($_SESSION['token_pedidos'], ENT_QUOTES, 'UTF-8') ?>"
                                                >

                                                <select name="status">

                                                    <option value="confirmado">
                                                        Confirmar
                                                    </option>

                                                    <option value="cancelado">
                                                        Cancelar
                                                    </option>

                                                </select>

                                                <button
                                                    type="submit"
                                                    class="botao-atualizar"
                                                    onclick="return confirm('Deseja realmente atualizar o status deste pedido?')"
                                                >
                                                    Atualizar
                                                </button>

                                            </form>

                                        </div>

                                    <?php else: ?>

                                        <span style="color: #8998ad;">
                                            Finalizado
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                            <tr
                                id="detalhes-<?= (int) $pedido['id'] ?>"
                                style="display: none;"
                            >

                                <td colspan="8">

                                    <div class="detalhes">

                                        <h3>
                                            Produtos do pedido #<?= (int) $pedido['id'] ?>
                                        </h3>

                                        <?php if (count($itens) > 0): ?>

                                            <?php foreach ($itens as $item): ?>

                                                <div class="produto-item">

                                                    <div>

                                                        <strong>
                                                            <?= htmlspecialchars($item['nome_produto'], ENT_QUOTES, 'UTF-8') ?>
                                                        </strong>

                                                        <p style="color: #8998ad; margin-top: 5px;">
                                                            Quantidade: <?= (int) $item['quantidade'] ?>
                                                        </p>

                                                    </div>

                                                    <div>

                                                        <strong>
                                                            R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?>
                                                        </strong>

                                                        <p style="color: #8998ad; margin-top: 5px;">
                                                            R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?> cada
                                                        </p>

                                                    </div>

                                                </div>

                                            <?php endforeach; ?>

                                        <?php else: ?>

                                            <p style="color: #8998ad;">
                                                Nenhum produto encontrado neste pedido.
                                            </p>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="vazio">
                                Nenhum pedido encontrado.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

    <script>

        function alternarDetalhes(id) {

            const linha = document.getElementById('detalhes-' + id);

            if (linha.style.display === 'none') {
                linha.style.display = 'table-row';
            } else {
                linha.style.display = 'none';
            }

        }

    </script>

</body>

</html>