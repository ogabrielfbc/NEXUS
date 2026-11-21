
<?php

// 1. Verifica o acesso do cliente
require_once __DIR__ . '/proteger_cliente.php';

// 2. Conecta ao banco de dados
$pdo = require __DIR__ . '/../config/database.php';

// 3. Verifica se a requisição foi enviada por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pedidos.php');
    exit;
}

// 4. Valida o token de segurança
$token = $_POST['token'] ?? '';

if (
    empty($_SESSION['token_avaliacao']) ||
    !is_string($token) ||
    !hash_equals($_SESSION['token_avaliacao'], $token)
) {

    $_SESSION['mensagem_avaliacao'] = 'Token de segurança inválido.';

    header('Location: pedidos.php');
    exit;
}

// 5. Recebe os dados da avaliação
$produtoId = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
$pedidoId = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
$nota = filter_input(INPUT_POST, 'nota', FILTER_VALIDATE_INT);

$comentario = trim($_POST['comentario'] ?? '');

$usuarioId = (int) $_SESSION['usuario_id'];

// 6. Valida os dados recebidos
if (
    !$produtoId ||
    !$pedidoId ||
    !$nota ||
    $nota < 1 ||
    $nota > 5
) {

    $_SESSION['mensagem_avaliacao'] = 'Dados da avaliação inválidos.';

    header('Location: pedidos.php');
    exit;
}

// 7. Verifica o tamanho do comentário
if (mb_strlen($comentario) > 2000) {

    $_SESSION['mensagem_avaliacao'] = 'O comentário deve ter no máximo 2.000 caracteres.';

    header('Location: pedidos.php');
    exit;
}

// 8. Processa o registro da avaliação
try {

    // 9. Confirma se o cliente comprou o produto
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
        throw new RuntimeException(
            'Você precisa ter comprado este produto para avaliá-lo.'
        );
    }

    // 10. Verifica se o cliente já avaliou o produto
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
        throw new RuntimeException(
            'Você já avaliou este produto.'
        );
    }

    // 11. Registra a avaliação
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

    // 12. Confirma o sucesso
    $_SESSION['mensagem_avaliacao'] = 'Avaliação enviada com sucesso!';

} catch (RuntimeException $erro) {

    // 13. Trata erros de validação
    $_SESSION['mensagem_avaliacao'] = $erro->getMessage();

} catch (PDOException $erro) {

    // 14. Trata erros do banco de dados
    $_SESSION['mensagem_avaliacao'] = 'Não foi possível registrar sua avaliação.';

}

// 15. Retorna para os pedidos
header('Location: pedidos.php');
exit;
