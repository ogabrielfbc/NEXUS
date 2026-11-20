
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está autenticado como administrador
if (
    !isset($_SESSION['usuario_id']) ||
    !isset($_SESSION['usuario_tipo']) ||
    $_SESSION['usuario_tipo'] !== 'admin'
) {

    // Impede que a página seja armazenada pelo navegador
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    header('Location: login.php');
    exit;
}
