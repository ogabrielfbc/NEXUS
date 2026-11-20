
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está autenticado como cliente
if (
    !isset($_SESSION['usuario_id']) ||
    !isset($_SESSION['usuario_tipo']) ||
    $_SESSION['usuario_tipo'] !== 'cliente'
) {

    // Impede o armazenamento de páginas protegidas no navegador
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    header('Location: /nexus-tech/cliente/login.php');
    exit;
}
