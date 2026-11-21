
<?php

// 1. Inicia a sessão e conecta ao banco
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/../config/database.php';

// 2. Inicializa as mensagens e os campos
$mensagem = '';
$tipoMensagem = '';

$nome = '';
$email = '';

// 3. Cria o token de segurança
if (empty($_SESSION['token_cadastro'])) {
    $_SESSION['token_cadastro'] = bin2hex(random_bytes(32));
}

// 4. Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $token = $_POST['token'] ?? '';

    // 5. Verifica o token de segurança
    if (
        !is_string($token) ||
        !hash_equals($_SESSION['token_cadastro'], $token)
    ) {

        $mensagem = 'Solicitação inválida. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';

    // 6. Verifica os campos obrigatórios
    } elseif (
        empty($nome) ||
        empty($email) ||
        empty($senha) ||
        empty($confirmarSenha)
    ) {

        $mensagem = 'Preencha todos os campos.';
        $tipoMensagem = 'erro';

    // 7. Valida o nome
    } elseif (strlen($nome) > 120) {

        $mensagem = 'O nome deve ter no máximo 120 caracteres.';
        $tipoMensagem = 'erro';

    // 8. Valida o e-mail
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {

        $mensagem = 'Digite um endereço de e-mail válido.';
        $tipoMensagem = 'erro';

    // 9. Valida a senha
    } elseif (strlen($senha) < 8) {

        $mensagem = 'A senha deve ter pelo menos 8 caracteres.';
        $tipoMensagem = 'erro';

    // 10. Confirma se as senhas são iguais
    } elseif ($senha !== $confirmarSenha) {

        $mensagem = 'As senhas não coincidem.';
        $tipoMensagem = 'erro';

    } else {

        try {

            // 11. Verifica se o e-mail já está cadastrado
            $sql = "SELECT id
                    FROM usuarios
                    WHERE email = :email
                    LIMIT 1";

            $consulta = $pdo->prepare($sql);

            $consulta->execute([
                ':email' => $email
            ]);

            $usuarioExistente = $consulta->fetch();

            if ($usuarioExistente) {

                $mensagem = 'Este e-mail já está cadastrado.';
                $tipoMensagem = 'erro';

            } else {

                // 12. Inicia a transação
                $pdo->beginTransaction();

                // 13. Cria o usuário
                $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

                $sql = "INSERT INTO usuarios
                        (nome, email, senha, tipo)
                        VALUES
                        (:nome, :email, :senha, 'cliente')";

                $consulta = $pdo->prepare($sql);

                $consulta->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha' => $senhaCriptografada
                ]);

                $usuarioId = $pdo->lastInsertId();

                // 14. Cria a carteira com 10.000 Coins
                $saldoInicial = 10000;

                $sql = "INSERT INTO carteira_coins
                        (usuario_id, saldo)
                        VALUES
                        (:usuario_id, :saldo)";

                $consulta = $pdo->prepare($sql);

                $consulta->execute([
                    ':usuario_id' => $usuarioId,
                    ':saldo' => $saldoInicial
                ]);

                $carteiraId = $pdo->lastInsertId();

                // 15. Registra o bônus de boas-vindas
                $sql = "INSERT INTO movimentacoes_coins
                        (
                            carteira_id,
                            tipo,
                            quantidade,
                            saldo_anterior,
                            saldo_novo,
                            descricao
                        )
                        VALUES
                        (
                            :carteira_id,
                            'credito',
                            :quantidade,
                            0,
                            :saldo_novo,
                            'Bônus de boas-vindas'
                        )";

                $consulta = $pdo->prepare($sql);

                $consulta->execute([
                    ':carteira_id' => $carteiraId,
                    ':quantidade' => $saldoInicial,
                    ':saldo_novo' => $saldoInicial
                ]);

                // 16. Confirma todas as operações
                $pdo->commit();

                // 17. Exibe a confirmação do cadastro
                $mensagem = 'Cadastro realizado com sucesso! Você recebeu 10.000 NEXUS Coins.';
                $tipoMensagem = 'sucesso';

                $nome = '';
                $email = '';

                // Gera um novo token
                $_SESSION['token_cadastro'] = bin2hex(random_bytes(32));
            }

        } catch (PDOException $erro) {

            // 18. Desfaz as operações caso ocorra algum erro
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mensagem = 'Não foi possível realizar o cadastro. Tente novamente.';
            $tipoMensagem = 'erro';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <!-- 19. Configurações da página -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Criar conta | NEXUS TECH</title>

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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            background: #080f1c;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        /* Cartão de cadastro */

        .container-cadastro {
            width: 100%;
            max-width: 460px;
            padding: 38px;
            background: #101a2b;
            border: 1px solid #1d304a;
            border-radius: 16px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.35);
        }

        /* Logo e subtítulo */

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
            margin-bottom: 20px;
        }

        /* Bônus de boas-vindas */

        .bonus {
            text-align: center;
            padding: 13px;
            margin-bottom: 25px;
            border: 1px solid rgba(56, 165, 255, 0.3);
            border-radius: 9px;
            background: rgba(56, 165, 255, 0.08);
            color: #38a5ff;
        }

        .bonus strong {
            display: block;
            font-size: 21px;
            margin-bottom: 3px;
        }

        .bonus span {
            font-size: 12px;
            color: #94a3b8;
        }

        /* Campos do formulário */

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

        /* Botão de cadastro */

        .btn-cadastro {
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

        .btn-cadastro:hover {
            background: #0872ce;
        }

        /* Mensagens */

        .mensagem {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .erro {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid #dc3545;
            color: #ff8c96;
        }

        .sucesso {
            background: rgba(25, 135, 84, 0.15);
            border: 1px solid #198754;
            color: #75dba8;
        }

        /* Rodapé */

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

        /* Ajustes para telas menores */

        @media (max-width: 480px) {

            .container-cadastro {
                padding: 25px 20px;
            }

        }

    </style>

</head>

<body>

    <!-- 21. Formulário de cadastro -->

    <div class="container-cadastro">

        <div class="logo">
            NEXUS TECH
        </div>

        <p class="subtitulo">
            Crie sua conta e faça parte da NEXUS.
        </p>

        <!-- 22. Bônus de boas-vindas -->

        <div class="bonus">

            <strong>🪙 +10.000 NEXUS Coins</strong>

            <span>
                Receba seu bônus de boas-vindas ao criar uma conta.
            </span>

        </div>

        <!-- 23. Mensagem de retorno -->

        <?php if (!empty($mensagem)): ?>

            <div class="mensagem <?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <!-- 24. Campos do formulário -->

        <form method="POST" action="cadastro.php">

            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($_SESSION['token_cadastro'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <div class="mb-3">

                <label class="form-label">
                    Nome completo
                </label>

                <input
                    type="text"
                    name="nome"
                    class="form-control"
                    placeholder="Digite seu nome"
                    maxlength="120"
                    value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    E-mail
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="seuemail@exemplo.com"
                    maxlength="150"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Senha
                </label>

                <input
                    type="password"
                    name="senha"
                    class="form-control"
                    placeholder="Mínimo de 8 caracteres"
                    minlength="8"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Confirmar senha
                </label>

                <input
                    type="password"
                    name="confirmar_senha"
                    class="form-control"
                    placeholder="Digite a senha novamente"
                    minlength="8"
                    required
                >

            </div>

            <!-- 25. Botão de envio -->

            <button type="submit" class="btn-cadastro">
                Criar minha conta
            </button>

        </form>

        <!-- 26. Links de navegação -->

        <div class="rodape">

            Já possui uma conta?
            <a href="login.php">Entrar</a>

            <br><br>

            <a href="../index.php">
                Voltar para a loja
            </a>

        </div>

    </div>

</body>

</html>
