
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

// Token de segurança
if (empty($_SESSION['token_avaliacoes'])) {
    $_SESSION['token_avaliacoes'] = bin2hex(random_bytes(32));
}

// Mensagens
$mensagem = $_SESSION['mensagem_avaliacoes'] ?? '';
unset($_SESSION['mensagem_avaliacoes']);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $acao = $_POST['acao'] ?? '';

    if (
        !$token ||
        !hash_equals($_SESSION['token_avaliacoes'], $token)
    ) {
        $_SESSION['mensagem_avaliacoes'] = 'Token de segurança inválido.';

    } elseif (!$id || !in_array($acao, ['alternar', 'excluir'], true)) {
        $_SESSION['mensagem_avaliacoes'] = 'Ação inválida.';

    } else {

        // Verificar se a avaliação existe
        $sql = "SELECT id, aprovado
                FROM avaliacoes
                WHERE id = :id";

        $consulta = $pdo->prepare($sql);
        $consulta->execute([
            ':id' => $id
        ]);

        $avaliacao = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$avaliacao) {

            $_SESSION['mensagem_avaliacoes'] = 'Avaliação não encontrada.';

        } elseif ($acao === 'alternar') {

            $novoStatus = (int) $avaliacao['aprovado'] === 1 ? 0 : 1;

            $sql = "UPDATE avaliacoes
                    SET aprovado = :aprovado
                    WHERE id = :id";

            $consulta = $pdo->prepare($sql);
            $consulta->execute([
                ':aprovado' => $novoStatus,
                ':id' => $id
            ]);

            $_SESSION['mensagem_avaliacoes'] = $novoStatus === 1
                ? 'Avaliação aprovada com sucesso!'
                : 'Avaliação ocultada com sucesso!';

        } elseif ($acao === 'excluir') {

            $sql = "DELETE FROM avaliacoes
                    WHERE id = :id";

            $consulta = $pdo->prepare($sql);
            $consulta->execute([
                ':id' => $id
            ]);

            $_SESSION['mensagem_avaliacoes'] = 'Avaliação excluída com sucesso!';

        }
    }

    header('Location: avaliacoes.php');
    exit;
}

// Pesquisa
$pesquisa = trim($_GET['pesquisa'] ?? '');

// Estatísticas
$sql = "SELECT
            COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN aprovado = 1 THEN 1 ELSE 0 END), 0) AS aprovadas,
            COALESCE(SUM(CASE WHEN aprovado = 0 THEN 1 ELSE 0 END), 0) AS ocultas,
            COALESCE(AVG(CASE WHEN aprovado = 1 THEN nota END), 0) AS media
        FROM avaliacoes";

$consulta = $pdo->query($sql);
$estatisticas = $consulta->fetch(PDO::FETCH_ASSOC);

// Buscar avaliações
$sql = "SELECT
            a.id,
            a.nota,
            a.comentario,
            a.aprovado,
            a.criado_em,
            p.nome AS nome_produto,
            u.nome AS nome_cliente
        FROM avaliacoes a
        INNER JOIN produtos p
            ON a.produto_id = p.id
        INNER JOIN usuarios u
            ON a.usuario_id = u.id";

if ($pesquisa !== '') {
    $sql .= " WHERE p.nome LIKE :pesquisa_produto
              OR u.nome LIKE :pesquisa_cliente
              OR a.comentario LIKE :pesquisa_comentario";
}

$sql .= " ORDER BY a.criado_em DESC";

$consulta = $pdo->prepare($sql);

if ($pesquisa !== '') {
    $termo = "%$pesquisa%";

    $consulta->execute([
        ':pesquisa_produto' => $termo,
        ':pesquisa_cliente' => $termo,
        ':pesquisa_comentario' => $termo
    ]);
} else {
    $consulta->execute();
}

$avaliacoes = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Avaliações | NEXUS</title>

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
            gap: 20px;
        }

        .cabecalho p {
            color: #8998ad;
            margin-top: 8px;
        }

        .estatisticas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
            font-size: 27px;
        }

        .azul {
            color: #168bff;
        }

        .verde {
            color: #42d392;
        }

        .amarelo {
            color: #ffc857;
        }

        .pesquisa {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .pesquisa input {
            width: 100%;
            max-width: 400px;
            padding: 13px;
            border: 1px solid #293b55;
            border-radius: 8px;
            background: #111a2b;
            color: white;
            outline: none;
        }

        .pesquisa input:focus {
            border-color: #168bff;
        }

        .botao {
            padding: 12px 18px;
            border-radius: 8px;
            background: #087bea;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
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
            min-width: 1000px;
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

        .estrelas {
            color: #ffc857;
            font-size: 17px;
            letter-spacing: 2px;
            white-space: nowrap;
        }

        .comentario {
            color: #a8b6c9;
            max-width: 260px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .status.aprovado {
            color: #42d392;
            background: #123329;
        }

        .status.oculto {
            color: #ffc857;
            background: #3b3020;
        }

        .acoes {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .acoes form {
            margin: 0;
        }

        .botao-acao {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .botao-alternar {
            color: #42d392;
        }

        .botao-alternar:hover {
            color: #25f5a0;
        }

        .botao-excluir {
            color: #ff7777;
        }

        .botao-excluir:hover {
            color: #ff4444;
        }

        .vazio {
            text-align: center;
            padding: 35px;
            color: #8998ad;
        }

        .mensagem {
            padding: 14px 18px;
            margin-bottom: 20px;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 8px;
            color: #42d392;
        }

        .data {
            color: #8998ad;
            white-space: nowrap;
        }

        @media (max-width: 1100px) {
            .estatisticas {
                grid-template-columns: repeat(2, 1fr);
            }
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
            }

            .pesquisa {
                flex-direction: column;
            }

            .estatisticas {
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
        <a href="cupons.php">Cupons</a>
        <a href="avaliacoes.php" class="ativo">Avaliações</a>
        <a href="../index.php">Voltar para a loja</a>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <div>
                <h1>Avaliações dos clientes</h1>
                <p>Gerencie as opiniões e notas dos produtos da NEXUS TECH.</p>
            </div>

        </header>

        <?php if ($mensagem !== ''): ?>

            <div class="mensagem">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <section class="estatisticas">

            <div class="card">
                <p>Total de avaliações</p>
                <h2><?= (int) $estatisticas['total'] ?></h2>
            </div>

            <div class="card">
                <p>Avaliações aprovadas</p>
                <h2 class="verde"><?= (int) $estatisticas['aprovadas'] ?></h2>
            </div>

            <div class="card">
                <p>Avaliações ocultas</p>
                <h2 class="amarelo"><?= (int) $estatisticas['ocultas'] ?></h2>
            </div>

            <div class="card">
                <p>Média das notas aprovadas</p>
                <h2 class="azul">
                    <?= number_format((float) $estatisticas['media'], 1, ',', '.') ?>
                    <span class="estrelas">★</span>
                </h2>
            </div>

        </section>

        <form method="GET" class="pesquisa">

            <input
                type="text"
                name="pesquisa"
                placeholder="Pesquisar produto, cliente ou comentário..."
                value="<?= htmlspecialchars($pesquisa, ENT_QUOTES, 'UTF-8') ?>"
            >

            <button type="submit" class="botao">
                Pesquisar
            </button>

        </form>

        <div class="tabela-container">

            <table>

                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Cliente</th>
                        <th>Nota</th>
                        <th>Comentário</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($avaliacoes) > 0): ?>

                        <?php foreach ($avaliacoes as $avaliacao): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $avaliacao['nome_produto'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $avaliacao['nome_cliente'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <span class="estrelas">
                                        <?= str_repeat('★', (int) $avaliacao['nota']) ?>
                                        <?= str_repeat('☆', 5 - (int) $avaliacao['nota']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="comentario">
                                        <?= !empty($avaliacao['comentario'])
                                            ? htmlspecialchars(
                                                $avaliacao['comentario'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : 'Sem comentário' ?>
                                    </div>
                                </td>

                                <td class="data">
                                    <?= date(
                                        'd/m/Y',
                                        strtotime($avaliacao['criado_em'])
                                    ) ?>
                                </td>

                                <td>

                                    <?php if ((int) $avaliacao['aprovado'] === 1): ?>

                                        <span class="status aprovado">
                                            Aprovada
                                        </span>

                                    <?php else: ?>

                                        <span class="status oculto">
                                            Oculta
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="acoes">

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Deseja alterar a visibilidade desta avaliação?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $avaliacao['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="acao"
                                                value="alternar"
                                            >

                                            <input
                                                type="hidden"
                                                name="token"
                                                value="<?= htmlspecialchars(
                                                    $_SESSION['token_avaliacoes'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="botao-acao botao-alternar"
                                            >
                                                <?= (int) $avaliacao['aprovado'] === 1
                                                    ? 'Ocultar'
                                                    : 'Aprovar' ?>
                                            </button>

                                        </form>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Tem certeza que deseja excluir esta avaliação permanentemente?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $avaliacao['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="acao"
                                                value="excluir"
                                            >

                                            <input
                                                type="hidden"
                                                name="token"
                                                value="<?= htmlspecialchars(
                                                    $_SESSION['token_avaliacoes'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="botao-acao botao-excluir"
                                            >
                                                Excluir
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="vazio">
                                Nenhuma avaliação encontrada.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</body>

</html>
