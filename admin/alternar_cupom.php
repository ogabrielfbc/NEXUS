
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cupons.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$token = $_POST['token'] ?? '';

if (!$id) {
    $_SESSION['mensagem_cupons'] = 'ID do cupom inválido.';
    header('Location: cupons.php');
    exit;
}

if (
    empty($_SESSION['token_cupons']) ||
    !hash_equals($_SESSION['token_cupons'], $token)
) {
    $_SESSION['mensagem_cupons'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
    header('Location: cupons.php');
    exit;
}

try {

    $sql = "SELECT id, ativo
            FROM cupons
            WHERE id = :id
            LIMIT 1";

    $consulta = $pdo->prepare($sql);
    $consulta->execute([
        ':id' => $id
    ]);

    $cupom = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$cupom) {
        $_SESSION['mensagem_cupons'] = 'Cupom não encontrado.';
        header('Location: cupons.php');
        exit;
    }

    $novoStatus = (int) $cupom['ativo'] === 1 ? 0 : 1;

    $sql = "UPDATE cupons
            SET ativo = :ativo
            WHERE id = :id";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':ativo' => $novoStatus,
        ':id' => $id
    ]);

    $_SESSION['mensagem_cupons'] = $novoStatus === 1
        ? 'Cupom ativado com sucesso!'
        : 'Cupom desativado com sucesso!';

} catch (PDOException $erro) {

    $_SESSION['mensagem_cupons'] = 'Erro ao atualizar o cupom.';

}

header('Location: cupons.php');
exit;
