
<?php

// 1. Inicia a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Verifica se o usuário é um cliente
if (
    !isset($_SESSION['usuario_tipo']) ||
    $_SESSION['usuario_tipo'] !== 'cliente'
) {
    header('Location: login.php');
    exit;
}

// 3. Limpa os dados da sessão
$_SESSION = [];

// 4. Remove o cookie da sessão
if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros['path'],
        $parametros['domain'],
        $parametros['secure'],
        $parametros['httponly']
    );
}

// 5. Encerra a sessão
session_destroy();

// 6. Redireciona para o login
header('Location: login.php');
exit;
