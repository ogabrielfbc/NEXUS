
<?php

require_once __DIR__ . '/proteger_cliente.php';

$pdo = require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pedidos.php');
    exit;
}

if (empty($_SESSION['token_avaliacao']) ||
    !hash_equals($_SESSION['token_avaliacao'], $_POST['token'] ?? '')) {
    $_SESSION['mensagem_avaliacao'] = 'Token de segurança inválido.';
    header('Location: pedidos.php');
    exit;
}

$produtoId = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
$pedidoId = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
$nota = filter_input(INPUT_POST, 'nota', FILTER_VALIDATE_INT);
$comentario = trim($_POST['comentario'] ?? '');

$usuarioId = (int) $_SESSION['usuario_id'];

if (!$produtoId || !$pedidoId || !$nota || $nota < 1 || $nota > 5) {
    $_SESSION['mensagem_avaliacao'] = 'Dados da avaliação inválidos.';
    header('Location: pedidos.php');
    exit;
}

if (mb_strlen($comentario) > 2000) {
    $_SESSION['mensagem_avaliacao'] = 'O comentário deve ter no máximo 2.000 caracteres.';
    header('Location: pedidos.php');
    exit;
}

try {

    // Confirmar se o cliente comprou o produto
    $sql = "SELECT ip.produto_id
            FROM itens_pedido ip
            INNER JOIN pedidos p
                ON ip.pedido_id = p.id
            WHERE p.id = :pedido_id
            AND p.usuario_id = :usuario_id
            AND p.status = 'confirmado'
            AND ip.produto_id = :produto_id
            LIMIT 1";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':pedido_id' => $pedidoId,
        ':usuario_id' => $usuarioId,
        ':produto_id' => $produtoId
    ]);

    if (!$consulta->fetch()) {
        throw new RuntimeException('Você precisa ter comprado este produto para avaliá-lo.');
    }

    // Impedir avaliação duplicada
    $sql = "SELECT id
            FROM avaliacoes
            WHERE produto_id = :produto_id
            AND usuario_id = :usuario_id
            LIMIT 1";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':produto_id' => $produtoId,
        ':usuario_id' => $usuarioId
    ]);

    if ($consulta->fetch()) {
        throw new RuntimeException('Você já avaliou este produto.');
    }

    // Salvar avaliação
    $sql = "INSERT INTO avaliacoes (
                produto_id,
                usuario_id,
                pedido_id,
                nota,
                comentario
            ) VALUES (
                :produto_id,
                :usuario_id,
                :pedido_id,
                :nota,
                :comentario
            )";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':produto_id' => $produtoId,
        ':usuario_id' => $usuarioId,
        ':pedido_id' => $pedidoId,
        ':nota' => $nota,
        ':comentario' => $comentario !== '' ? $comentario : null
    ]);

    $_SESSION['mensagem_avaliacao'] = 'Avaliação enviada com sucesso!';

} catch (RuntimeException $erro) {

    $_SESSION['mensagem_avaliacao'] = $erro->getMessage();

} catch (PDOException $erro) {

    $_SESSION['mensagem_avaliacao'] = 'Não foi possível registrar sua avaliação.';

}

header('Location: pedidos.php');
exit;
