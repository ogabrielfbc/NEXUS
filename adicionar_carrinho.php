
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/config/database.php';

// Prepara o carrinho
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Gera o token de segurança
if (empty($_SESSION['token_carrinho'])) {
    $_SESSION['token_carrinho'] = bin2hex(random_bytes(32));
}

// Aceita somente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verifica o token
$token = $_POST['token_carrinho'] ?? '';

if (!hash_equals($_SESSION['token_carrinho'], $token)) {
    $_SESSION['mensagem_loja'] = 'Solicitação inválida. Tente novamente.';
    header('Location: index.php');
    exit;
}

// Recebe o ID do produto
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    $_SESSION['mensagem_loja'] = 'Produto inválido.';
    header('Location: index.php');
    exit;
}

// Busca o produto
$sql = "SELECT id, nome, estoque
        FROM produtos
        WHERE id = :id
        AND ativo = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);

$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto || $produto['estoque'] <= 0) {
    $_SESSION['mensagem_loja'] = 'Produto indisponível no momento.';
    header('Location: index.php');
    exit;
}

// Verifica a quantidade no carrinho
$quantidadeAtual = $_SESSION['carrinho'][$id] ?? 0;

if ($quantidadeAtual >= $produto['estoque']) {
    $_SESSION['mensagem_loja'] = 'Estoque insuficiente.';
    header('Location: index.php');
    exit;
}

// Adiciona uma unidade
$_SESSION['carrinho'][$id] = $quantidadeAtual + 1;
$acao = $_POST['acao'] ?? 'adicionar';

$_SESSION['mensagem_loja'] = 'Produto adicionado ao carrinho!';

if ($acao === 'comprar') {
    header('Location: carrinho.php');
} else {
    header('Location: index.php');
}

exit;
