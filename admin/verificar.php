
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/../config/database.php';

// Confere se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Recebe os dados do formulário
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

// Verifica se os campos foram preenchidos
if (empty($email) || empty($senha)) {
    $_SESSION['mensagem_erro'] = 'Preencha todos os campos.';
    header('Location: login.php');
    exit;
}

try {

    // Procura o usuário pelo e-mail
    $sql = "SELECT
                id,
                nome,
                email,
                senha,
                tipo
            FROM usuarios
            WHERE email = :email
            LIMIT 1";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':email' => $email
    ]);

    $usuario = $consulta->fetch();

    // Confere se o usuário existe, se a senha está correta e se é administrador
    if (
        !$usuario ||
        !password_verify($senha, $usuario['senha']) ||
        $usuario['tipo'] !== 'admin'
    ) {
        $_SESSION['mensagem_erro'] = 'E-mail ou senha inválidos.';
        header('Location: login.php');
        exit;
    }

    // Cria uma nova identificação de sessão
    session_regenerate_id(true);

    // Salva os dados do administrador na sessão
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];

    // Entra no painel administrativo
    header('Location: painel.php');
    exit;

} catch (PDOException $erro) {

    $_SESSION['mensagem_erro'] = 'Ocorreu um erro ao tentar entrar.';
    header('Location: login.php');
    exit;

}
