
<?php

require_once __DIR__ . '/proteger_cliente.php';

$pdo = require __DIR__ . '/../config/database.php';

$sql = "SELECT id, nome, email, criado_em
        FROM usuarios
        WHERE id = :id
        AND tipo = 'cliente'
        LIMIT 1";

$consulta = $pdo->prepare($sql);

$consulta->execute([
    ':id' => $_SESSION['usuario_id']
]);

$cliente = $consulta->fetch();

if (!$cliente) {
    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Minha Conta | NEXUS TECH</title>

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
            gap: 20px;
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

        .boas-vindas {
            margin-bottom: 30px;
        }

        .boas-vindas h1 {
            font-size: 30px;
            font-weight: bold;
        }

        .boas-vindas p {
            color: #94a3b8;
        }

        .cartao {
            background: #101a2b;
            border: 1px solid #1d304a;
            border-radius: 14px;
            padding: 25px;
            height: 100%;
        }

        .cartao h5 {
            color: #38a5ff;
            margin-bottom: 20px;
        }

        .informacao {
            padding: 13px 0;
            border-bottom: 1px solid #263b57;
        }

        .informacao:last-child {
            border-bottom: none;
        }

        .informacao small {
            display: block;
            color: #94a3b8;
            margin-bottom: 5px;
        }

        .informacao strong {
            font-size: 15px;
            overflow-wrap: anywhere;
        }

        .opcao {
            display: block;
            padding: 15px;
            margin-bottom: 12px;
            border: 1px solid #263b57;
            border-radius: 9px;
            color: #dbeafe;
            text-decoration: none;
            transition: 0.2s;
        }

        .opcao:hover {
            border-color: #1685e5;
            background: #14243a;
            color: #38a5ff;
        }

        .opcao span {
            color: #94a3b8;
            display: block;
            font-size: 13px;
            margin-top: 5px;
        }

        .btn-sair {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 25px;
            background: #dc3545;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
        }

        .btn-sair:hover {
            background: #bb2d3b;
            color: #fff;
        }

        @media (max-width: 600px) {
            header {
                padding: 16px 5%;
            }

            .boas-vindas h1 {
                font-size: 24px;
            }

            .conteudo {
                margin-top: 30px;
            }
        }
    </style>
</head>

<body>

    <header>

        <a href="../index.php" class="logo">NEXUS TECH</a>

        <a href="../index.php" class="voltar">
            Voltar para a loja
        </a>

    </header>

    <main class="conteudo">

        <div class="boas-vindas">

            <h1>
                Olá, <?= htmlspecialchars($cliente['nome']) ?>!
            </h1>

            <p>Bem-vindo à sua área pessoal da NEXUS TECH.</p>

        </div>

        <div class="row g-4">

            <div class="col-md-6">

                <div class="cartao">

                    <h5>Meus dados</h5>

                    <div class="informacao">
                        <small>Nome completo</small>
                        <strong><?= htmlspecialchars($cliente['nome']) ?></strong>
                    </div>

                    <div class="informacao">
                        <small>E-mail</small>
                        <strong><?= htmlspecialchars($cliente['email']) ?></strong>
                    </div>

                    <div class="informacao">
                        <small>Cliente desde</small>
                        <strong>
                            <?= date('d/m/Y', strtotime($cliente['criado_em'])) ?>
                        </strong>
                    </div>

                </div>

            </div>

            <div class="col-md-6">

                <div class="cartao">

                    <h5>Minha conta</h5>

                   <a href="carteira.php" class="opcao">
    Minha carteira
    <span>Consulte seu saldo de NEXUS Coins</span>
</a>

<a href="pedidos.php" class="opcao">
    Meus pedidos
    <span>Acompanhe suas compras</span>
</a>

<a href="carteira.php" class="opcao">
    Histórico de Coins
    <span>Consulte suas movimentações</span>
</a>

<a href="editar_dados.php" class="opcao">
    Editar meus dados
    <span>Atualize suas informações pessoais</span>
</a>

                </div>

            </div>

        </div>

        <a href="sair.php" class="btn-sair">
            Sair da conta
        </a>

    </main>

</body>
</html>
