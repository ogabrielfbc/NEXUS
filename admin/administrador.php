
<?php

$pdo = require __DIR__ . '/../config/database.php';

$mensagem = '';
$cadastroPermitido = true;

// Verifica se já existe um administrador
$consulta = $pdo->query("
    SELECT COUNT(*)
    FROM usuarios
    WHERE tipo = 'admin'
");

$quantidadeAdministradores = $consulta->fetchColumn();

if ($quantidadeAdministradores > 0) {
    $cadastroPermitido = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cadastroPermitido) {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $chave = $_POST['chave'] ?? '';

    $arquivoEnv = __DIR__ . '/../.env';
    $config = parse_ini_file($arquivoEnv);

    $chaveCorreta = $config['ADMIN_CHAVE'] ?? '';

    if ($chave === '' || $chave !== $chaveCorreta) {

        $mensagem = 'Chave de segurança inválida.';

    } elseif ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = 'Preencha os dados corretamente.';

    } elseif (strlen($senha) < 8) {

        $mensagem = 'A senha precisa ter pelo menos 8 caracteres.';

    } else {

        $consulta = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ");

        $consulta->execute([
            ':email' => $email
        ]);

        if ($consulta->fetch()) {

            $mensagem = 'Esse e-mail já está cadastrado.';

        } else {

            $senhaProtegida = password_hash($senha, PASSWORD_DEFAULT);

            $consulta = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, tipo)
                VALUES (:nome, :email, :senha, 'admin')
            ");

            $consulta->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senhaProtegida
            ]);

            $cadastroPermitido = false;
            $mensagem = 'Administrador criado com sucesso! Agora você pode acessar o painel.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Criar administrador | NEXUS</title>

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

        .caixa {
            width: 100%;
            max-width: 430px;
            padding: 35px;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 14px;
        }

        h1 {
            color: #168bff;
            margin-bottom: 10px;
        }

        p {
            color: #8998ad;
            margin-bottom: 25px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 13px;
            margin-bottom: 18px;
            border: 1px solid #293b55;
            border-radius: 7px;
            background: #0b1322;
            color: white;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 7px;
            background: #087bea;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .mensagem {
            padding: 12px;
            margin-bottom: 20px;
            background: #172a40;
            border-radius: 7px;
            color: #8fc7ff;
            font-size: 14px;
        }

        a {
            display: block;
            margin-top: 20px;
            color: #8998ad;
            text-align: center;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="caixa">

        <h1>NEXUS</h1>
        <p>Cadastro do primeiro administrador</p>

        <?php if ($mensagem !== ''): ?>
            <div class="mensagem">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($cadastroPermitido): ?>

            <form method="POST">

                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required>

                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>

                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" minlength="8" required>

                <label for="chave">Chave de segurança</label>
                <input type="password" id="chave" name="chave" required>

                <button type="submit">Criar administrador</button>

            </form>

        <?php else: ?>

            <p>O cadastro inicial está bloqueado. Já existe um administrador ou o cadastro foi concluído.</p>

        <?php endif; ?>

        <a href="login.php">Ir para o login</a>

    </div>

</body>
</html>
