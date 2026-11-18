
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

// 1. Receber pesquisa
$pesquisa = trim($_GET['pesquisa'] ?? '');

try {

    // 2. Buscar quantidade total de clientes
    $sqlTotal = "SELECT COUNT(*) 
                 FROM usuarios
                 WHERE tipo = 'cliente'";

    $totalClientes = (int) $pdo->query($sqlTotal)->fetchColumn();

    // 3. Buscar quantidade de pedidos dos clientes
    $sqlPedidos = "SELECT COUNT(*)
                   FROM pedidos p
                   INNER JOIN usuarios u
                       ON p.usuario_id = u.id
                   WHERE u.tipo = 'cliente'";

    $totalPedidos = (int) $pdo->query($sqlPedidos)->fetchColumn();

    // 4. Buscar total movimentado em compras não canceladas
    $sqlVendas = "SELECT COALESCE(SUM(p.total), 0)
                  FROM pedidos p
                  INNER JOIN usuarios u
                      ON p.usuario_id = u.id
                  WHERE u.tipo = 'cliente'
                  AND p.status <> 'cancelado'";

    $totalCompras = (float) $pdo->query($sqlVendas)->fetchColumn();

    // 5. Buscar clientes
    $sql = "SELECT
                u.id,
                u.nome,
                u.email,
                u.criado_em,
                COUNT(p.id) AS quantidade_pedidos,
                COALESCE(
                    SUM(
                        CASE
                            WHEN p.status <> 'cancelado'
                            THEN p.total
                            ELSE 0
                        END
                    ), 0
                ) AS total_compras

            FROM usuarios u

            LEFT JOIN pedidos p
                ON p.usuario_id = u.id

            WHERE u.tipo = 'cliente'";

    $parametros = [];

    // 6. Aplicar pesquisa
    if ($pesquisa !== '') {

        $sql .= " AND (
                    u.nome LIKE :pesquisa_nome
                    OR u.email LIKE :pesquisa_email
                  )";

        $parametros[':pesquisa_nome'] = "%$pesquisa%";
        $parametros[':pesquisa_email'] = "%$pesquisa%";
    }

    $sql .= " GROUP BY
                u.id,
                u.nome,
                u.email,
                u.criado_em

              ORDER BY u.criado_em DESC";

    $consulta = $pdo->prepare($sql);
    $consulta->execute($parametros);

    $clientes = $consulta->fetchAll();

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    die('Ocorreu um erro ao carregar os clientes.');
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clientes | NEXUS ADMIN</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #080d18;
            color: #e5edf8;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: 100%;
            max-width: 1450px;
            margin: auto;
            padding: 35px;
        }

        /* Cabeçalho */

        .topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #168bff;
        }

        .logo span {
            color: white;
        }

        .topo-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .topo-links a {
            padding: 10px 15px;
            border-radius: 7px;
            color: #a9b8cc;
            font-size: 13px;
            transition: 0.2s;
        }

        .topo-links a:hover,
        .topo-links a.ativo {
            background: #142b49;
            color: #168bff;
        }

        /* Título */

        .titulo-pagina {
            margin-bottom: 28px;
        }

        .titulo-pagina h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .titulo-pagina p {
            color: #8796aa;
            font-size: 14px;
        }

        /* Indicadores */

        .indicadores {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .card-indicador {
            background: #111a2b;
            border: 1px solid #22344e;
            border-radius: 12px;
            padding: 23px;
        }

        .card-indicador span {
            display: block;
            color: #8998ad;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .card-indicador strong {
            font-size: 27px;
            color: #168bff;
        }

        /* Área de clientes */

        .conteudo {
            background: #111a2b;
            border: 1px solid #22344e;
            border-radius: 12px;
            overflow: hidden;
        }

        .conteudo-topo {
            padding: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            border-bottom: 1px solid #22344e;
        }

        .conteudo-topo h2 {
            font-size: 18px;
        }

        .pesquisa {
            display: flex;
            gap: 8px;
            width: 100%;
            max-width: 400px;
        }

        .pesquisa input {
            flex: 1;
            min-width: 0;
            padding: 11px 13px;
            background: #0b1322;
            border: 1px solid #293b55;
            border-radius: 7px;
            color: white;
            outline: none;
        }

        .pesquisa input:focus {
            border-color: #168bff;
        }

        .pesquisa button {
            padding: 11px 17px;
            background: #087bea;
            border: none;
            border-radius: 7px;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        .pesquisa button:hover {
            background: #0064c7;
        }

        .limpar {
            display: flex;
            align-items: center;
            padding: 0 10px;
            color: #8998ad;
            font-size: 12px;
        }

        /* Tabela */

        .tabela-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            padding: 16px 20px;
            text-align: left;
            color: #8796aa;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #0e1726;
            white-space: nowrap;
        }

        td {
            padding: 17px 20px;
            border-top: 1px solid #22344e;
            font-size: 13px;
            color: #cbd5e1;
        }

        tbody tr:hover {
            background: rgba(22, 139, 255, 0.04);
        }

        .cliente-nome {
            color: white;
            font-weight: bold;
        }

        .cliente-email {
            color: #8998ad;
        }

        .valor {
            color: #42c98b;
            font-weight: bold;
        }

        .quantidade {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #142b49;
            color: #58aaff;
            font-size: 12px;
        }

        .data {
            color: #8998ad;
        }

        /* Estado vazio */

        .vazio {
            padding: 65px 20px;
            text-align: center;
        }

        .vazio-icone {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .vazio h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .vazio p {
            color: #8998ad;
            font-size: 13px;
        }

        .resultado {
            padding: 15px 22px;
            color: #8998ad;
            font-size: 12px;
            border-top: 1px solid #22344e;
        }

        /* Responsividade */

        @media (max-width: 800px) {

            .container {
                padding: 20px 14px;
            }

            .indicadores {
                grid-template-columns: 1fr;
            }

            .conteudo-topo {
                align-items: stretch;
            }

            .pesquisa {
                max-width: 100%;
            }

            .titulo-pagina h1 {
                font-size: 23px;
            }

        }

    </style>

</head>

<body>

    <div class="container">

        <!-- Cabeçalho -->

        <header class="topo">

            <a href="painel.php" class="logo">
                NEXUS<span>ADMIN</span>
            </a>

            <nav class="topo-links">

                <a href="painel.php">
                    Painel
                </a>

                <a href="produtos.php">
                    Produtos
                </a>

                <a href="pedidos.php">
                    Pedidos
                </a>

                <a href="clientes.php" class="ativo">
                    Clientes
                </a>

            </nav>

        </header>

        <!-- Título -->

        <section class="titulo-pagina">

            <h1>Clientes</h1>

            <p>
                Gerencie os clientes cadastrados na NEXUS TECH.
            </p>

        </section>

        <!-- Indicadores -->

        <section class="indicadores">

            <div class="card-indicador">

                <span>Total de clientes</span>

                <strong>
                    <?= number_format($totalClientes, 0, ',', '.') ?>
                </strong>

            </div>

            <div class="card-indicador">

                <span>Pedidos realizados</span>

                <strong>
                    <?= number_format($totalPedidos, 0, ',', '.') ?>
                </strong>

            </div>

            <div class="card-indicador">

                <span>Total em compras não canceladas</span>

                <strong>
                    R$ <?= number_format($totalCompras, 2, ',', '.') ?>
                </strong>

            </div>

        </section>

        <!-- Lista de clientes -->

        <section class="conteudo">

            <div class="conteudo-topo">

                <h2>Clientes cadastrados</h2>

                <form method="GET" class="pesquisa">

                    <input
                        type="text"
                        name="pesquisa"
                        placeholder="Pesquisar nome ou e-mail..."
                        value="<?= htmlspecialchars($pesquisa, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <button type="submit">
                        Buscar
                    </button>

                    <?php if ($pesquisa !== ''): ?>

                        <a href="clientes.php" class="limpar">
                            Limpar
                        </a>

                    <?php endif; ?>

                </form>

            </div>

            <?php if (count($clientes) > 0): ?>

                <div class="tabela-container">

                    <table>

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>E-mail</th>
                                <th>Cadastro</th>
                                <th>Pedidos</th>
                                <th>Total em compras</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($clientes as $cliente): ?>

                                <tr>

                                    <td>
                                        #<?= (int) $cliente['id'] ?>
                                    </td>

                                    <td class="cliente-nome">
                                        <?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td class="cliente-email">
                                        <?= htmlspecialchars($cliente['email'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td class="data">

                                        <?php if (!empty($cliente['criado_em'])): ?>

                                            <?= date('d/m/Y', strtotime($cliente['criado_em'])) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <span class="quantidade">
                                            <?= (int) $cliente['quantidade_pedidos'] ?>
                                        </span>
                                    </td>

                                    <td class="valor">
                                        R$ <?= number_format((float) $cliente['total_compras'], 2, ',', '.') ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <div class="resultado">

                    Exibindo <?= count($clientes) ?>
                    <?= count($clientes) === 1 ? 'cliente' : 'clientes' ?>.

                </div>

            <?php else: ?>

                <div class="vazio">

                    <div class="vazio-icone">
                        👥
                    </div>

                    <?php if ($pesquisa !== ''): ?>

                        <h3>Nenhum cliente encontrado</h3>

                        <p>
                            Não encontramos clientes com esse nome ou e-mail.
                        </p>

                    <?php else: ?>

                        <h3>Nenhum cliente cadastrado</h3>

                        <p>
                            Os clientes aparecerão aqui quando criarem uma conta na NEXUS TECH.
                        </p>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</body>

</html>
