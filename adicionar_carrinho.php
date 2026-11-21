
<?php

// 1. Inicia a sessão e conecta ao banco

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/config/database.php';


// 2. Prepara o carrinho

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

if (empty($_SESSION['token_carrinho'])) {
    $_SESSION['token_carrinho'] = bin2hex(random_bytes(32));
}


// 3. Verifica o método da solicitação

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}


// 4. Verifica o token de segurança

$token = $_POST['token_carrinho'] ?? '';

if (!hash_equals($_SESSION['token_carrinho'], $token)) {

    $_SESSION['mensagem_loja'] = 'Solicitação inválida. Tente novamente.';

    unset($_SESSION['produto_mensagem_id']);

    header('Location: index.php#produtos');
    exit;
}


// 5. Recebe e valida o ID do produto

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {

    $_SESSION['mensagem_loja'] = 'Produto inválido.';

    unset($_SESSION['produto_mensagem_id']);

    header('Location: index.php#produtos');
    exit;
}


// 6. Busca o produto no banco

$sql = "SELECT
            id,
            nome,
            estoque
        FROM produtos
        WHERE id = :id
        AND ativo = 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto || $produto['estoque'] <= 0) {

    $_SESSION['mensagem_loja'] = 'Produto indisponível no momento.';

    unset($_SESSION['produto_mensagem_id']);

    header('Location: index.php#produtos');
    exit;
}


// 7. Verifica a quantidade disponível

$quantidadeAtual = $_SESSION['carrinho'][$id] ?? 0;

if ($quantidadeAtual >= $produto['estoque']) {

    $_SESSION['mensagem_loja'] = 'Estoque insuficiente.';

    unset($_SESSION['produto_mensagem_id']);

    header('Location: index.php#produtos');
    exit;
}


// 8. Adiciona o produto ao carrinho

$_SESSION['carrinho'][$id] = $quantidadeAtual + 1;

$acao = $_POST['acao'] ?? 'adicionar';


// 9. Prepara a mensagem de confirmação

$_SESSION['mensagem_loja'] = 'Produto adicionado ao carrinho!';

$_SESSION['produto_mensagem_id'] = (int) $id;


// 10. Redireciona para a página correspondente

if ($acao === 'comprar') {

    header('Location: carrinho.php');

} else {

    header('Location: index.php#produtos');

}

exit;
