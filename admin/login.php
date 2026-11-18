
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    isset($_SESSION['usuario_id']) &&
    isset($_SESSION['usuario_tipo']) &&
    $_SESSION['usuario_tipo'] === 'admin'
) {
    header('Location: painel.php');
    exit;
}

$mensagem = $_SESSION['mensagem_erro'] ?? '';
unset($_SESSION['mensagem_erro']);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login Administrativo | NEXUS</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #080d18;
            color: white;
            padding: 20px;
        }

        .caixa-login {
            width: 100%;
            max-width: 420px;
            padding: 40px;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 16px;
            box-shadow: 0 0 35px rgba(0, 140, 255, 0.10);
        }

        .logo {
            text-align: center;
            font-size: 34px;
            font-weight: bold;
            letter-spacing: 5px;
            color: #168bff;
            margin-bottom: 10px;
        }

        .subtitulo {
            text-align: center;
            color: #8998ad;
            margin-bottom: 35px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 14px;
            margin-bottom: 22px;
            border: 1px solid #293b55;
            border-radius: 8px;
            background: #0b1322;
            color: white;
            outline: none;
        }

        input:focus {
            border-color: #168bff;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #087bea;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #0064c7;
        }

        .mensagem-erro {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: rgba(255, 60, 60, 0.10);
            border: 1px solid rgba(255, 60, 60, 0.30);
            color: #ff7777;
            font-size: 13px;
        }

        .voltar {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #8998ad;
            text-decoration: none;
            font-size: 13px;
        }

        .voltar:hover {
            color: #168bff;
        }
    </style>
</head>

<body>

    <div class="caixa-login">

        <div class="logo">NEXUS</div>

        <p class="subtitulo">Painel de Administração</p>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem-erro">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="verificar.php" method="POST">

            <label for="email">E-mail administrativo</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Digite seu e-mail"
                required
            >

            <label for="senha">Senha</label>

            <input
                type="password"
                id="senha"
                name="senha"
                placeholder="Digite sua senha"
                required
            >

            <button type="submit">
                Entrar no painel
            </button>

        </form>

        <a href="../index.php" class="voltar">
            Voltar para a loja
        </a>

    </div>

</body>
</html>
