
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['usuario_id']) ||
    !isset($_SESSION['usuario_tipo']) ||
    $_SESSION['usuario_tipo'] !== 'cliente'
) {
    header('Location: login.php');
    exit;
}
