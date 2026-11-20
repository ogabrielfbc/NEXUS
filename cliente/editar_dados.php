
<?php

require_once __DIR__ . '/proteger_cliente.php';

$pdo = require __DIR__ . '/../config/database.php';

$usuarioId = $_SESSION['usuario_id'];

$erro = '';
$sucesso = '';

if (empty($_SESSION['token_editar_dados'])) {
    $_SESSION['token_editar_dados'] = bin2hex(random_bytes(32));
}

// Busca os dados do cliente
$sql = "SELECT nome, email, senha
        FROM usuarios
        WHERE id = :id
        AND tipo = 'cliente'
        LIMIT 1";

$consulta = $pdo->prepare($sql);
$consulta->execute([':id' => $usuarioId]);

$cliente = $consulta->fetch();

if (!$cliente) {
    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}

$nome = $cliente['nome'];
$email = $cliente['email'];

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token'] ?? '';

    if (!hash_equals($_SESSION['token_editar_dados'], $token)) {

        $erro = 'Solicitação inválida. Atualize a página.';

    } else {

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        if ($nome === '' || $email === '') {

            $erro = 'Preencha o nome e o e-mail.';

        } elseif (strlen($nome) > 150) {

            $erro = 'O nome deve ter no máximo 150 caracteres.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $erro = 'Informe um e-mail válido.';

        } elseif (strlen($email) > 255) {

            $erro = 'O e-mail é muito longo.';

        } else {

            // Verifica se o e-mail já está sendo utilizado
            $sql = "SELECT id
                    FROM usuarios
                    WHERE email = :email
                    AND id != :id
                    LIMIT 1";

            $consulta = $pdo->prepare($sql);

            $consulta->execute([
                ':email' => $email,
                ':id' => $usuarioId
            ]);

            if ($consulta->fetch()) {

                $erro = 'Esse e-mail já está sendo utilizado.';

            } else {

                $alterarSenha = (
                    $senhaAtual !== '' ||
                    $novaSenha !== '' ||
                    $confirmarSenha !== ''
                );

                if ($alterarSenha) {

                    if (
                        $senhaAtual === '' ||
                        $novaSenha === '' ||
                        $confirmarSenha === ''
                    ) {

                        $erro = 'Preencha todos os campos de senha.';

                    } elseif (!password_verify($senhaAtual, $cliente['senha'])) {

                        $erro = 'A senha atual está incorreta.';

                    } elseif (strlen($novaSenha) < 8) {

                        $erro = 'A nova senha precisa ter pelo menos 8 caracteres.';

                    } elseif ($novaSenha !== $confirmarSenha) {

                        $erro = 'As novas senhas não coincidem.';

                    }
                }

                if ($erro === '') {

                    if ($alterarSenha) {

                        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

                        $sql = "UPDATE usuarios
                                SET nome = :nome,
                                    email = :email,
                                    senha = :senha
                                WHERE id = :id
                                AND tipo = 'cliente'";

                        $consulta = $pdo->prepare($sql);

                        $consulta->execute([
                            ':nome' => $nome,
                            ':email' => $email,
                            ':senha' => $senhaHash,
                            ':id' => $usuarioId
                        ]);

                    } else {

                        $sql = "UPDATE usuarios
                                SET nome = :nome,
                                    email = :email
                                WHERE id = :id
                                AND tipo = 'cliente'";

                        $consulta = $pdo->prepare($sql);

                        $consulta->execute([
                            ':nome' => $nome,
                            ':email' => $email,
                            ':id' => $usuarioId
                        ]);
                    }

                    $_SESSION['usuario_nome'] = $nome;

                    $_SESSION['mensagem_edicao'] =
                        'Seus dados foram atualizados com sucesso!';

                    $_SESSION['token_editar_dados'] =
                        bin2hex(random_bytes(32));

                    header('Location: editar_dados.php');
                    exit;
                }
            }
        }
    }
}

// Recupera mensagem de sucesso
if (isset($_SESSION['mensagem_edicao'])) {
    $sucesso = $_SESSION['mensagem_edicao'];
    unset($_SESSION['mensagem_edicao']);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar meus dados | NEXUS TECH</title>

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
        }

        .voltar:hover {
            color: #38a5ff;
        }

        .conteudo {
            width: 90%;
            max-width: 650px;
            margin: 45px auto;
        }

        .cartao {
            background: #101a2b;
            border: 1px solid #1d304a;
            border-radius: 14px;
            padding: 30px;
        }

        h1 {
            font-size: 27px;
            margin-bottom: 10px;
        }

        .descricao {
            color: #94a3b8;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
        }

        input {
            width: 100%;
            padding: 13px;
            margin-bottom: 20px;
            border: 1px solid #263b57;
            border-radius: 8px;
            background: #080f1c;
            color: white;
            outline: none;
        }

        input:focus {
            border-color: #1685e5;
        }

        .separador {
            border-top: 1px solid #263b57;
            margin: 25px 0;
            padding-top: 25px;
        }

        .separador h3 {
            font-size: 18px;
            color: #38a5ff;
            margin-bottom: 15px;
        }

        .botao {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #1685e5;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .botao:hover {
            background: #0872ce;
        }

        .mensagem {
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .erro {
            background: #421e27;
            color: #ffaaaa;
        }

        .sucesso {
            background: #123b30;
            color: #8af0c2;
        }

        @media (max-width: 600px) {
            .cartao {
                padding: 22px;
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

        <div class="cartao">

            <h1>Editar meus dados</h1>

            <p class="descricao">
                Atualize suas informações pessoais da NEXUS TECH.
            </p>

            <?php if ($erro !== ''): ?>

                <div class="mensagem erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>

                <div class="mensagem sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="token"
                    value="<?= htmlspecialchars($_SESSION['token_editar_dados']) ?>"
                >

                <label for="nome">Nome completo</label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="150"
                    value="<?= htmlspecialchars($nome) ?>"
                    required
                >

                <label for="email">E-mail</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="255"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                >

                <div class="separador">

                    <h3>Alterar senha</h3>

                    <p class="descricao">
                        Se não quiser trocar a senha, deixe os campos vazios.
                    </p>

                    <label for="senha_atual">Senha atual</label>

                    <input
                        type="password"
                        id="senha_atual"
                        name="senha_atual"
                        autocomplete="current-password"
                    >

                    <label for="nova_senha">Nova senha</label>

                    <input
                        type="password"
                        id="nova_senha"
                        name="nova_senha"
                        minlength="8"
                        autocomplete="new-password"
                    >

                    <label for="confirmar_senha">Confirmar nova senha</label>

                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        minlength="8"
                        autocomplete="new-password"
                    >

                </div>

                <button type="submit" class="botao">
                    Salvar alterações
                </button>

            </form>

        </div>

    </main>

</body>
</html>
