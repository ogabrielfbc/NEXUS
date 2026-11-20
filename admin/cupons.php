
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

// Token de segurança
if (empty($_SESSION['token_cupons'])) {
    $_SESSION['token_cupons'] = bin2hex(random_bytes(32));
}

// Mensagens
$mensagem = $_SESSION['mensagem_cupons'] ?? '';
unset($_SESSION['mensagem_cupons']);

// Pesquisa
$pesquisa = trim($_GET['pesquisa'] ?? '');

// Estatísticas
$sql = "SELECT
            COUNT(*) AS total,
            SUM(
                CASE
                    WHEN ativo = 1
                    AND NOW() BETWEEN validade_inicio AND validade_fim
                    AND (
                        limite_utilizacoes IS NULL
                        OR utilizacoes < limite_utilizacoes
                    )
                    THEN 1
                    ELSE 0
                END
            ) AS ativos,
            COALESCE(SUM(utilizacoes), 0) AS utilizacoes
        FROM cupons";

$consulta = $pdo->query($sql);
$estatisticas = $consulta->fetch(PDO::FETCH_ASSOC);

// Total de descontos concedidos
$sql = "SELECT COALESCE(SUM(valor_desconto), 0)
        FROM uso_cupons";

$consulta = $pdo->query($sql);
$totalDescontos = $consulta->fetchColumn();

// Buscar cupons
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
            CASE
                WHEN ativo = 0 THEN 'Inativo'
                WHEN NOW() < validade_inicio THEN 'Agendado'
                WHEN NOW() > validade_fim THEN 'Expirado'
                WHEN limite_utilizacoes IS NOT NULL
                    AND utilizacoes >= limite_utilizacoes
                    THEN 'Limite atingido'
                ELSE 'Ativo'
            END AS status
        FROM cupons";

if ($pesquisa !== '') {
    $sql .= " WHERE codigo LIKE :pesquisa";
}

$sql .= " ORDER BY id DESC";

$consulta = $pdo->prepare($sql);

if ($pesquisa !== '') {
    $consulta->execute([
        ':pesquisa' => "%$pesquisa%"
    ]);
} else {
    $consulta->execute();
}

$cupons = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Formatar desconto
function formatarDesconto($tipo, $valor)
{
    if ($tipo === 'porcentagem') {
        return number_format((float) $valor, 2, ',', '.') . '%';
    }

    return number_format((float) $valor, 2, ',', '.') . ' NC';
}

// Formatar data
function formatarData($data)
{
    return date('d/m/Y', strtotime($data));
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cupons | NEXUS</title>

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

        .botao {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 8px;
            background: #087bea;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .botao:hover {
            background: #0064c7;
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

        .tabela-container {
            overflow-x: auto;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
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

        .codigo {
            color: #168bff;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            background: #243651;
        }

        .status.ativo {
            color: #42d392;
            background: #123329;
        }

        .status.inativo,
        .status.expirado {
            color: #ff7777;
            background: #3a2028;
        }

        .status.agendado,
        .status.limite {
            color: #ffc66d;
            background: #3b3020;
        }

        .acoes {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .acoes a {
            color: #168bff;
            text-decoration: none;
        }

        .acoes a:hover {
            text-decoration: underline;
        }

        .acoes form {
            margin: 0;
        }

        .botao-excluir {
            background: none;
            border: none;
            color: #ff7777;
            cursor: pointer;
            font-size: 14px;
        }

        .botao-excluir:hover {
            color: #ff4444;
        }

        .botao-ativar {
            background: none;
            border: none;
            color: #42d392;
            cursor: pointer;
            font-size: 14px;
        }

        .botao-ativar:hover {
            color: #25f5a0;
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
        <a href="pedidos.php" >Pedidos</a>
        <a href="clientes.php">Clientes</a>
        <a href="cupons.php" class="ativo">Cupons</a>
        <a href="avaliacoes.php">Avaliações</a>
        <a href="../index.php">Voltar para a loja</a>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <div>
                <h1>Cupons de desconto</h1>
                <p>Gerencie os descontos e promoções da NEXUS TECH.</p>
            </div>

            <a href="adicionar_cupom.php" class="botao">
                + Criar cupom
            </a>

        </header>

        <?php if ($mensagem !== ''): ?>

            <div class="mensagem">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <section class="estatisticas">

            <div class="card">
                <p>Total de cupons</p>
                <h2><?= (int) $estatisticas['total'] ?></h2>
            </div>

            <div class="card">
                <p>Cupons ativos</p>
                <h2 class="verde"><?= (int) $estatisticas['ativos'] ?></h2>
            </div>

            <div class="card">
                <p>Total de utilizações</p>
                <h2 class="azul"><?= (int) $estatisticas['utilizacoes'] ?></h2>
            </div>

            <div class="card">
                <p>Descontos concedidos</p>
                <h2>
                    <?= number_format((float) $totalDescontos, 2, ',', '.') ?> NC
                </h2>
            </div>

        </section>

        <form method="GET" class="pesquisa">

            <input
                type="text"
                name="pesquisa"
                placeholder="Pesquisar cupom pelo código..."
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
                        <th>Código</th>
                        <th>Desconto</th>
                        <th>Validade</th>
                        <th>Utilizações</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($cupons) > 0): ?>

                        <?php foreach ($cupons as $cupom): ?>

                            <?php

                            $classeStatus = match ($cupom['status']) {
                                'Ativo' => 'ativo',
                                'Inativo', 'Expirado' => 'inativo',
                                'Agendado' => 'agendado',
                                'Limite atingido' => 'limite',
                                default => ''
                            };

                            ?>

                            <tr>

                                <td>
                                    <span class="codigo">
                                        <?= htmlspecialchars($cupom['codigo'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        formatarDesconto(
                                            $cupom['tipo_desconto'],
                                            $cupom['valor_desconto']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= formatarData($cupom['validade_inicio']) ?>
                                    até
                                    <?= formatarData($cupom['validade_fim']) ?>
                                </td>

                                <td>
                                    <?= (int) $cupom['utilizacoes'] ?>
                                    /
                                    <?= $cupom['limite_utilizacoes'] !== null
                                        ? (int) $cupom['limite_utilizacoes']
                                        : 'Ilimitado' ?>
                                </td>

                                <td>
                                    <span class="status <?= $classeStatus ?>">
                                        <?= htmlspecialchars(
                                            $cupom['status'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td>

                                    <div class="acoes">

                                        <a href="editar_cupom.php?id=<?= (int) $cupom['id'] ?>">
                                            Editar
                                        </a>
                                        <a href="historico_cupons.php?id=<?= (int) $cupom['id'] ?>" class="botao-editar">
                                        Histórico
                                        </a>

                                        <form
                                            action="alternar_cupom.php"
                                            method="POST"
                                            onsubmit="return confirm('Deseja alterar o status deste cupom?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $cupom['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="token"
                                                value="<?= htmlspecialchars(
                                                    $_SESSION['token_cupons'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="<?= (int) $cupom['ativo'] === 1
                                                    ? 'botao-excluir'
                                                    : 'botao-ativar' ?>"
                                            >
                                                <?= (int) $cupom['ativo'] === 1
                                                    ? 'Desativar'
                                                    : 'Ativar' ?>
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="vazio">
                                Nenhum cupom encontrado.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</body>

</html>
