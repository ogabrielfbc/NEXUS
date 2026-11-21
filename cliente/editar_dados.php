
<?php

// 1. Verifica o acesso e conecta ao banco
require_once __DIR__ . '/proteger_cliente.php';

$pdo = require __DIR__ . '/../config/database.php';

$usuarioId = (int) $_SESSION['usuario_id'];

// 2. Prepara as mensagens da página
$erro = '';
$sucesso = '';

// 3. Cria o token de segurança
if (empty($_SESSION['token_editar_dados'])) {
    $_SESSION['token_editar_dados'] = bin2hex(random_bytes(32));
}

// 4. Busca os dados atuais do cliente
$sql = "SELECT nome, email, senha
        FROM usuarios
        WHERE id = :id
        AND tipo = 'cliente'
        LIMIT 1";

$consulta = $pdo->prepare($sql);

$consulta->execute([
    ':id' => $usuarioId
]);

$cliente = $consulta->fetch();

// 5. Verifica se o cliente existe
if (!$cliente) {
    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}

// 6. Preenche os campos com os dados atuais
$nome = $cliente['nome'];
$email = $cliente['email'];

// 7. Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token'] ?? '';

    // 8. Verifica o token de segurança
    if (
        !is_string($token) ||
        !hash_equals($_SESSION['token_editar_dados'], $token)
    ) {

        $erro = 'Solicitação inválida. Atualize a página.';

    } else {

        // 9. Recebe os novos dados
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        // 10. Valida o nome e o e-mail
        if ($nome === '' || $email === '') {

            $erro = 'Preencha o nome e o e-mail.';

        } elseif (strlen($nome) > 150) {

            $erro = 'O nome deve ter no máximo 150 caracteres.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $erro = 'Informe um e-mail válido.';

        } elseif (strlen($email) > 255) {

            $erro = 'O e-mail é muito longo.';

        } else {

            // 11. Verifica se o e-mail já está sendo utilizado
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

                // 12. Verifica se o cliente deseja alterar a senha
                $alterarSenha = (
                    $senhaAtual !== '' ||
                    $novaSenha !== '' ||
                    $confirmarSenha !== ''
                );

                if ($alterarSenha) {

                    // 13. Valida os campos de senha
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

                // 14. Atualiza os dados do cliente
                if ($erro === '') {

                    if ($alterarSenha) {

                        // Atualiza os dados e a senha
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

                        // Atualiza somente o nome e o e-mail
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

                    // 15. Atualiza o nome na sessão
                    $_SESSION['usuario_nome'] = $nome;

                    // 16. Prepara a mensagem de sucesso
                    $_SESSION['mensagem_edicao'] =
                        'Seus dados foram atualizados com sucesso!';

                    // Renova o token de segurança
                    $_SESSION['token_editar_dados'] =
                        bin2hex(random_bytes(32));

                    // 17. Redireciona para a página
                    header('Location: editar_dados.php');
                    exit;
                }
            }
        }
    }
}

// 18. Recupera a mensagem de sucesso
if (isset($_SESSION['mensagem_edicao'])) {

    $sucesso = $_SESSION['mensagem_edicao'];

    unset($_SESSION['mensagem_edicao']);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <!-- 19. Configurações da página -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar meus dados | NEXUS TECH</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- 20. Estilos da página -->

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
        }

        .voltar:hover {
            color: #38a5ff;
        }

        /* Conteúdo principal */

        .conteudo {
            width: 90%;
            max-width: 650px;
            margin: 45px auto;
        }

        /* Cartão do formulário */

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

        /* Campos do formulário */

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

        /* Área de alteração da senha */

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

        /* Botão de salvar */

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

        /* Mensagens de retorno */

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

        /* Ajustes para telas menores */

        @media (max-width: 600px) {

            .cartao {
                padding: 22px;
            }

        }

    </style>

</head>

<body>

    <!-- 21. Cabeçalho -->

    <header>

        <a href="../index.php" class="logo">
            NEXUS TECH
        </a>

        <a href="minha_conta.php" class="voltar">
            Minha conta
        </a>

    </header>

    <!-- 22. Formulário de edição -->

    <main class="conteudo">

        <div class="cartao">

            <h1>Editar meus dados</h1>

            <p class="descricao">
                Atualize suas informações pessoais da NEXUS TECH.
            </p>

            <!-- 23. Mensagem de erro -->

            <?php if ($erro !== ''): ?>

                <div class="mensagem erro">
                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>

            <!-- 24. Mensagem de sucesso -->

            <?php if ($sucesso !== ''): ?>

                <div class="mensagem sucesso">
                    <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>

            <!-- 25. Formulário principal -->

            <form method="POST">

                <input
                    type="hidden"
                    name="token"
                    value="<?= htmlspecialchars($_SESSION['token_editar_dados'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <!-- Dados pessoais -->

                <label for="nome">
                    Nome completo
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="150"
                    value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="255"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

                <!-- Alteração de senha -->

                <div class="separador">

                    <h3>Alterar senha</h3>

                    <p class="descricao">
                        Se não quiser trocar a senha, deixe os campos vazios.
                    </p>

                    <label for="senha_atual">
                        Senha atual
                    </label>

                    <input
                        type="password"
                        id="senha_atual"
                        name="senha_atual"
                        autocomplete="current-password"
                    >

                    <label for="nova_senha">
                        Nova senha
                    </label>

                    <input
                        type="password"
                        id="nova_senha"
                        name="nova_senha"
                        minlength="8"
                        autocomplete="new-password"
                    >

                    <label for="confirmar_senha">
                        Confirmar nova senha
                    </label>

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
