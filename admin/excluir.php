
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

// 1. Validar a solicitação
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: produtos.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$token = $_POST['token'] ?? '';

if (
    !$id ||
    $id <= 0 ||
    empty($_SESSION['token_exclusao']) ||
    !hash_equals($_SESSION['token_exclusao'], $token)
) {
    $_SESSION['mensagem_produtos'] = 'Solicitação inválida.';
    header('Location: produtos.php');
    exit;
}

// 2. Buscar os dados do produto
$consulta = $pdo->prepare(
    "SELECT id, imagem, modelo_3d
     FROM produtos
     WHERE id = :id"
);

$consulta->execute([':id' => $id]);
$produto = $consulta->fetch();

if (!$produto) {
    $_SESSION['mensagem_produtos'] = 'Produto não encontrado.';
    header('Location: produtos.php');
    exit;
}

try {

    // 3. Verificar se o produto está em algum pedido
    $consultaPedidos = $pdo->prepare(
        "SELECT COUNT(*)
         FROM itens_pedido
         WHERE produto_id = :produto_id"
    );

    $consultaPedidos->execute([
        ':produto_id' => $id
    ]);

    if ($consultaPedidos->fetchColumn() > 0) {
        $_SESSION['mensagem_produtos'] =
            'Este produto não pode ser excluído porque está associado a pedidos. Você pode desativá-lo na edição.';

        header('Location: produtos.php');
        exit;
    }

    // 4. Buscar as imagens da galeria
    $consultaImagens = $pdo->prepare(
        "SELECT caminho
         FROM produto_imagens
         WHERE produto_id = :produto_id"
    );

    $consultaImagens->execute([
        ':produto_id' => $id
    ]);

    $imagensGaleria = $consultaImagens->fetchAll(PDO::FETCH_COLUMN);

    $arquivos = array_filter([
        $produto['imagem'],
        $produto['modelo_3d'],
        ...$imagensGaleria
    ]);

    // 5. Excluir o produto
    $pdo->beginTransaction();

    $excluirGaleria = $pdo->prepare(
        "DELETE FROM produto_imagens
         WHERE produto_id = :produto_id"
    );

    $excluirGaleria->execute([
        ':produto_id' => $id
    ]);

    $excluirProduto = $pdo->prepare(
        "DELETE FROM produtos
         WHERE id = :id"
    );

    $excluirProduto->execute([
        ':id' => $id
    ]);

    $pdo->commit();

    // 6. Excluir arquivos que não são mais utilizados
    foreach (array_unique($arquivos) as $caminho) {

        $consultaUso = $pdo->prepare(
            "SELECT id
             FROM produtos
             WHERE imagem = :imagem
                OR modelo_3d = :modelo

             UNION

             SELECT produto_id
             FROM produto_imagens
             WHERE caminho = :caminho"
        );

        $consultaUso->execute([
            ':imagem' => $caminho,
            ':modelo' => $caminho,
            ':caminho' => $caminho
        ]);

        if ($consultaUso->fetch()) {
            continue;
        }

        $pasta = null;

        if (strpos($caminho, 'uploads/produtos/') === 0) {
            $pasta = 'produtos';
        } elseif (strpos($caminho, 'uploads/modelos/') === 0) {
            $pasta = 'modelos';
        }

        if ($pasta === null) {
            continue;
        }

        $pastaPermitida = realpath(
            __DIR__ . '/../uploads/' . $pasta
        );

        $arquivo = realpath(
            __DIR__ . '/../' . $caminho
        );

        if (
            $arquivo !== false &&
            $pastaPermitida !== false &&
            strpos(
                $arquivo,
                $pastaPermitida . DIRECTORY_SEPARATOR
            ) === 0 &&
            is_file($arquivo)
        ) {
            unlink($arquivo);
        }
    }

    $_SESSION['mensagem_produtos'] = 'Produto excluído com sucesso.';

} catch (PDOException $erro) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensagem_produtos'] =
        'Não foi possível excluir o produto. Verifique se existem registros relacionados.';

}

header('Location: produtos.php');
exit;
