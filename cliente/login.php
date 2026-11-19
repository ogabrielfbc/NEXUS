
<?php

session_start();

$pdo = require __DIR__ . '/../config/database.php';

$mensagem = '';
$email = '';

if (
    isset($_SESSION['usuario_id']) &&
    isset($_SESSION['usuario_tipo']) &&
    $_SESSION['usuario_tipo'] === 'cliente'
) {
    header('Location: minha_conta.php');
    exit;
}

if (empty($_SESSION['token_login_cliente'])) {
    $_SESSION['token_login_cliente'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $token = $_POST['token'] ?? '';

    if (!hash_equals($_SESSION['token_login_cliente'], $token)) {

        $mensagem = 'Solicitação inválida. Atualize a página e tente novamente.';

    } elseif (empty($email) || empty($senha)) {

        $mensagem = 'Preencha todos os campos.';

    } else {

        try {

            $sql = "SELECT id, nome, email, senha, tipo
                    FROM usuarios
                    WHERE email = :email
                    LIMIT 1";

            $consulta = $pdo->prepare($sql);

            $consulta->execute([
                ':email' => $email
            ]);

            $usuario = $consulta->fetch();

            if (
                !$usuario ||
                !password_verify($senha, $usuario['senha']) ||
                $usuario['tipo'] !== 'cliente'
            ) {

                $mensagem = 'E-mail ou senha inválidos.';

            } else {

                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_tipo'] = $usuario['tipo'];

                unset($_SESSION['token_login_cliente']);

                header('Location: minha_conta.php');
                exit;
            }

        } catch (PDOException $erro) {

            $mensagem = 'Ocorreu um erro ao tentar entrar. Tente novamente.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Entrar | NEXUS TECH</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            background: #080f1c;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        .container-login {
            width: 100%;
            max-width: 430px;
            padding: 38px;
            background: #101a2b;
            border: 1px solid #1d304a;
            border-radius: 16px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.35);
        }

        .logo {
            text-align: center;
            font-size: 27px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #38a5ff;
            margin-bottom: 8px;
        }

        .subtitulo {
            text-align: center;
            color: #94a3b8;
            margin-bottom: 30px;
        }

        .form-label {
            color: #dbeafe;
            font-size: 14px;
        }

        .form-control {
            background: #0b1423;
            border: 1px solid #263b57;
            color: #fff;
            padding: 12px;
        }

        .form-control:focus {
            background: #0b1423;
            color: #fff;
            border-color: #2496f3;
            box-shadow: 0 0 0 3px rgba(36, 150, 243, 0.15);
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            border: none;
            border-radius: 8px;
            background: #1685e5;
            color: white;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-login:hover {
            background: #0872ce;
        }

        .mensagem {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid #dc3545;
            color: #ff8c96;
        }

        .rodape {
            text-align: center;
            margin-top: 25px;
            color: #94a3b8;
            font-size: 14px;
        }

        .rodape a {
            color: #38a5ff;
            text-decoration: none;
        }

        .rodape a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container-login {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

    <div class="container-login">

        <div class="logo">NEXUS TECH</div>

        <p class="subtitulo">Entre na sua conta para continuar.</p>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($_SESSION['token_login_cliente']) ?>"
            >

            <div class="mb-3">
                <label class="form-label">E-mail</label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="seuemail@exemplo.com"
                    maxlength="150"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Senha</label>

                <input
                    type="password"
                    name="senha"
                    class="form-control"
                    placeholder="Digite sua senha"
                    required
                >
            </div>

            <button type="submit" class="btn-login">
                Entrar na minha conta
            </button>

        </form>

        <div class="rodape">

            Ainda não possui uma conta?
            <a href="cadastro.php">Criar conta</a>

            <br><br>

            <a href="../index.php">Voltar para a loja</a>

        </div>

    </div>

</body>
</html>
