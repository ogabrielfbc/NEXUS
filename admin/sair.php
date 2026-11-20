
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a sessão pertence a um administrador
if (
    !isset($_SESSION['usuario_tipo']) ||
    $_SESSION['usuario_tipo'] !== 'admin'
) {
    header('Location: login.php');
    exit;
}

// Limpa os dados da sessão
$_SESSION = [];

// Remove o cookie da sessão
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

// Destrói a sessão
session_destroy();

// Volta para o login administrativo
header('Location: login.php');
exit;
