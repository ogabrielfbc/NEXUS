
<?php

// 1. Verifica o acesso e conecta ao banco
require_once __DIR__ . '/proteger_cliente.php';

$pdo = require __DIR__ . '/../config/database.php';

$usuarioId = (int) $_SESSION['usuario_id'];

// 2. Busca a carteira do cliente
$sql = "SELECT id, saldo
        FROM carteira_coins
        WHERE usuario_id = :usuario_id
        LIMIT 1";

$consulta = $pdo->prepare($sql);

$consulta->execute([
    ':usuario_id' => $usuarioId
]);

$carteira = $consulta->fetch();

// 3. Verifica se a carteira existe
if (!$carteira) {
    exit('Carteira não encontrada.');
}

$carteiraId = $carteira['id'];

// 4. Busca o total de Coins recebidos e gastos
$sql = "SELECT
            COALESCE(SUM(CASE WHEN tipo = 'credito' THEN quantidade ELSE 0 END), 0) AS total_recebido,
            COALESCE(SUM(CASE WHEN tipo = 'debito' THEN quantidade ELSE 0 END), 0) AS total_gasto
        FROM movimentacoes_coins
        WHERE carteira_id = :carteira_id";

$consulta = $pdo->prepare($sql);

$consulta->execute([
    ':carteira_id' => $carteiraId
]);

$resumo = $consulta->fetch();

// 5. Busca o histórico de movimentações
$sql = "SELECT tipo, quantidade, saldo_anterior, saldo_novo, descricao, criado_em
        FROM movimentacoes_coins
        WHERE carteira_id = :carteira_id
        ORDER BY criado_em DESC, id DESC";

$consulta = $pdo->prepare($sql);

$consulta->execute([
    ':carteira_id' => $carteiraId
]);

$movimentacoes = $consulta->fetchAll(PDO::FETCH_ASSOC);

// 6. Formata os valores em NEXUS Coins
function formatarCoins($valor)
{
    return number_format((float) $valor, 2, ',', '.');
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <!-- 7. Configurações da página -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Minha Carteira | NEXUS TECH</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- 8. Estilos da página -->

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

        /* Cabeçalho */

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

        /* Conteúdo principal */

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

        /* Cartão do saldo */

        .saldo-card {
            position: relative;
            overflow: hidden;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid #1765a0;
            background: linear-gradient(135deg, #102e4c, #101a2b 75%);
            margin-bottom: 25px;
        }

        .saldo-card::after {
            content: '🪙';
            position: absolute;
            right: 35px;
            top: 20px;
            font-size: 80px;
            opacity: 0.15;
        }

        .saldo-label {
            color: #94cfff;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .saldo-valor {
            font-size: 42px;
            font-weight: bold;
            color: #fff;
        }

        .saldo-valor span {
            color: #38a5ff;
            font-size: 20px;
        }

        .saldo-descricao {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 10px;
        }

        /* Cartões de resumo */

        .resumo-card {
            height: 100%;
            padding: 22px;
            border-radius: 12px;
            background: #101a2b;
            border: 1px solid #1d304a;
        }

        .resumo-card small {
            display: block;
            color: #94a3b8;
            margin-bottom: 12px;
        }

        .resumo-card strong {
            font-size: 25px;
        }

        .recebido {
            color: #38d996;
        }

        .gasto {
            color: #ff7777;
        }

        /* Histórico de movimentações */

        .historico {
            margin-top: 30px;
            padding: 25px;
            background: #101a2b;
            border: 1px solid #1d304a;
            border-radius: 14px;
        }

        .historico h4 {
            margin-bottom: 25px;
        }

        .movimentacao {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 17px 0;
            border-bottom: 1px solid #263b57;
        }

        .movimentacao:last-child {
            border-bottom: none;
        }

        .mov-info {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .mov-icone {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #172b43;
            font-size: 19px;
        }

        .mov-descricao {
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .mov-data {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 5px;
        }

        .mov-valor {
            font-weight: bold;
            white-space: nowrap;
        }

        .mov-valor.credito {
            color: #38d996;
        }

        .mov-valor.debito {
            color: #ff7777;
        }

        /* Histórico vazio */

        .vazio {
            text-align: center;
            color: #94a3b8;
            padding: 35px 10px;
        }

        /* Botão de retorno */

        .btn-voltar {
            display: inline-block;
            margin-top: 25px;
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

        /* Ajustes para telas menores */

        @media (max-width: 600px) {

            .conteudo {
                margin-top: 30px;
            }

            .saldo-valor {
                font-size: 32px;
            }

            .saldo-card {
                padding: 23px;
            }

            .saldo-card::after {
                right: 15px;
                font-size: 55px;
            }

            .historico {
                padding: 18px;
            }

            .movimentacao {
                align-items: flex-start;
            }

        }

    </style>

</head>

<body>

    <!-- 9. Cabeçalho -->

    <header>

        <a href="../index.php" class="logo">
            NEXUS TECH
        </a>

        <a href="minha_conta.php" class="voltar">
            Minha conta
        </a>

    </header>

    <!-- 10. Conteúdo da carteira -->

    <main class="conteudo">

        <div class="titulo">

            <h1>Minha carteira</h1>

            <p>
                Gerencie seus NEXUS Coins e acompanhe suas movimentações.
            </p>

        </div>

        <!-- 11. Saldo principal -->

        <div class="saldo-card">

            <div class="saldo-label">
                SALDO DISPONÍVEL
            </div>

            <div class="saldo-valor">

                <?= formatarCoins($carteira['saldo']) ?>

                <span>NC</span>

            </div>

            <div class="saldo-descricao">
                NEXUS Coins disponíveis para suas compras.
            </div>

        </div>

        <!-- 12. Resumo financeiro -->

        <div class="row g-3">

            <div class="col-md-6">

                <div class="resumo-card">

                    <small>Total recebido</small>

                    <strong class="recebido">
                        + <?= formatarCoins($resumo['total_recebido']) ?> NC
                    </strong>

                </div>

            </div>

            <div class="col-md-6">

                <div class="resumo-card">

                    <small>Total gasto</small>

                    <strong class="gasto">
                        - <?= formatarCoins($resumo['total_gasto']) ?> NC
                    </strong>

                </div>

            </div>

        </div>

        <!-- 13. Histórico de movimentações -->

        <div class="historico">

            <h4>Histórico de movimentações</h4>

            <?php if (count($movimentacoes) > 0): ?>

                <!-- 14. Lista as movimentações -->

                <?php foreach ($movimentacoes as $movimentacao): ?>

                    <div class="movimentacao">

                        <div class="mov-info">

                            <div class="mov-icone">
                                <?= $movimentacao['tipo'] === 'credito' ? '↙' : '↗' ?>
                            </div>

                            <div>

                                <div class="mov-descricao">
                                    <?= htmlspecialchars($movimentacao['descricao'], ENT_QUOTES, 'UTF-8') ?>
                                </div>

                                <span class="mov-data">
                                    <?= date('d/m/Y H:i', strtotime($movimentacao['criado_em'])) ?>
                                </span>

                            </div>

                        </div>

                        <div class="mov-valor <?= htmlspecialchars($movimentacao['tipo'], ENT_QUOTES, 'UTF-8') ?>">

                            <?= $movimentacao['tipo'] === 'credito' ? '+' : '-' ?>

                            <?= formatarCoins($movimentacao['quantidade']) ?> NC

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <!-- 15. Mensagem quando não existem movimentações -->

                <div class="vazio">
                    Nenhuma movimentação encontrada.
                </div>

            <?php endif; ?>

        </div>

        <!-- 16. Botão para voltar à conta -->

        <a href="minha_conta.php" class="btn-voltar">
            Voltar para minha conta
        </a>

    </main>

</body>

</html>
