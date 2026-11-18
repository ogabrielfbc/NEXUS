
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

// 1. Funções auxiliares

function redirecionarEdicao($id)
{
    header("Location: editar.php?id=$id");
    exit;
}

function excluirArquivoSeNaoUtilizado($pdo, $caminho, $pasta)
{
    if (empty($caminho)) {
        return;
    }

    $sqlVerificar = "SELECT id
                     FROM produtos
                     WHERE imagem = :imagem
                     OR modelo_3d = :modelo

                     UNION

                     SELECT produto_id
                     FROM produto_imagens
                     WHERE caminho = :caminho";

    $consultaVerificar = $pdo->prepare($sqlVerificar);

    $consultaVerificar->execute([
        ':imagem' => $caminho,
        ':modelo' => $caminho,
        ':caminho' => $caminho
    ]);

    if ($consultaVerificar->fetch()) {
        return;
    }

    $pastaPermitida = realpath(__DIR__ . '/../uploads/' . $pasta);
    $arquivo = realpath(__DIR__ . '/../' . $caminho);

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


function validarImagem($arquivo)
{
    if (
        !isset($arquivo['tmp_name'], $arquivo['error'], $arquivo['size']) ||
        $arquivo['error'] !== UPLOAD_ERR_OK
    ) {
        return false;
    }

    if ($arquivo['size'] > 5 * 1024 * 1024) {
        return false;
    }

    $informacoes = getimagesize($arquivo['tmp_name']);

    if ($informacoes === false) {
        return false;
    }

    $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($tiposPermitidos[$informacoes['mime']])) {
        return false;
    }

    return $tiposPermitidos[$informacoes['mime']];
}

function validarModelo3D($arquivo)
{
    if (
        !isset($arquivo['tmp_name'], $arquivo['error'], $arquivo['size']) ||
        $arquivo['error'] !== UPLOAD_ERR_OK
    ) {
        return false;
    }

    if ($arquivo['size'] > 30 * 1024 * 1024) {
        return false;
    }

    $extensao = strtolower(
        pathinfo($arquivo['name'], PATHINFO_EXTENSION)
    );

    if ($extensao !== 'glb') {
        return false;
    }

    $arquivoAberto = fopen($arquivo['tmp_name'], 'rb');

    if (!$arquivoAberto) {
        return false;
    }

    $cabecalho = fread($arquivoAberto, 12);

    fclose($arquivoAberto);

    if (strlen($cabecalho) < 12) {
        return false;
    }

    $assinatura = substr($cabecalho, 0, 4);
    $versao = unpack('V', substr($cabecalho, 4, 4))[1];

    if ($assinatura !== 'glTF' || $versao !== 2) {
        return false;
    }

    return true;
}


// 2. Buscar produto

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: produtos.php');
    exit;
}

$sqlProduto = "SELECT
                    id,
                    codigo,
                    categoria_id,
                    nome,
                    descricao,
                    preco,
                    estoque,
                    imagem,
                    modelo_3d,
                    ativo,
                    destaque
                FROM produtos
                WHERE id = :id";

$consultaProduto = $pdo->prepare($sqlProduto);

$consultaProduto->execute([
    ':id' => $id
]);

$produto = $consultaProduto->fetch();

if (!$produto) {
    header('Location: produtos.php');
    exit;
}

$mensagem = '';


// 3. Atualizar informações do produto

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['salvar_produto'])
) {

    $nome = trim($_POST['nome'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    $categoriaId = !empty($_POST['categoria_id'])
        ? filter_var($_POST['categoria_id'], FILTER_VALIDATE_INT)
        : null;

    $preco = filter_var($_POST['preco'] ?? '', FILTER_VALIDATE_FLOAT);
    $estoque = filter_var($_POST['estoque'] ?? '', FILTER_VALIDATE_INT);

    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $destaque = isset($_POST['destaque']) ? 1 : 0;

    if (
        $nome === '' ||
        $codigo === '' ||
        $preco === false ||
        $preco <= 0 ||
        $estoque === false ||
        $estoque < 0 ||
        (!empty($_POST['categoria_id']) && $categoriaId === false)
    ) {

        $mensagem = 'Verifique os dados informados.';

    } else {

        try {

            $sqlAtualizar = "UPDATE produtos
                             SET
                                nome = :nome,
                                codigo = :codigo,
                                categoria_id = :categoria_id,
                                descricao = :descricao,
                                preco = :preco,
                                estoque = :estoque,
                                ativo = :ativo,
                                destaque = :destaque
                             WHERE id = :id";

            $consultaAtualizar = $pdo->prepare($sqlAtualizar);

            $consultaAtualizar->execute([
                ':nome' => $nome,
                ':codigo' => $codigo,
                ':categoria_id' => $categoriaId ?: null,
                ':descricao' => $descricao,
                ':preco' => $preco,
                ':estoque' => $estoque,
                ':ativo' => $ativo,
                ':destaque' => $destaque,
                ':id' => $id
            ]);

            $_SESSION['mensagem_edicao'] = 'Produto atualizado com sucesso!';

            redirecionarEdicao($id);

        } catch (PDOException $erro) {

            if ($erro->getCode() === '23000') {
                $mensagem = 'Este código já está sendo utilizado por outro produto.';
            } else {
                throw $erro;
            }
        }
    }
}


// 4. Definir imagem da galeria como principal

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['definir_imagem_principal'])
) {

    $imagemId = filter_input(
        INPUT_POST,
        'imagem_id',
        FILTER_VALIDATE_INT
    );

    if ($imagemId && $imagemId > 0) {

        $sqlImagem = "SELECT id, caminho
                      FROM produto_imagens
                      WHERE id = :id
                      AND produto_id = :produto_id";

        $consultaImagem = $pdo->prepare($sqlImagem);

        $consultaImagem->execute([
            ':id' => $imagemId,
            ':produto_id' => $id
        ]);

        $imagemSelecionada = $consultaImagem->fetch();

        if ($imagemSelecionada) {

            try {

                $pdo->beginTransaction();

                $imagemAntiga = $produto['imagem'];

                $sqlAtualizar = "UPDATE produtos
                                 SET imagem = :imagem
                                 WHERE id = :id";

                $consultaAtualizar = $pdo->prepare($sqlAtualizar);

                $consultaAtualizar->execute([
                    ':imagem' => $imagemSelecionada['caminho'],
                    ':id' => $id
                ]);

                $sqlExcluir = "DELETE FROM produto_imagens
                               WHERE id = :id
                               AND produto_id = :produto_id";

                $consultaExcluir = $pdo->prepare($sqlExcluir);

                $consultaExcluir->execute([
                    ':id' => $imagemId,
                    ':produto_id' => $id
                ]);

                if (!empty($imagemAntiga)) {

                    $sqlInserir = "INSERT INTO produto_imagens (
                                        produto_id,
                                        caminho
                                    ) VALUES (
                                        :produto_id,
                                        :caminho
                                    )";

                    $consultaInserir = $pdo->prepare($sqlInserir);

                    $consultaInserir->execute([
                        ':produto_id' => $id,
                        ':caminho' => $imagemAntiga
                    ]);
                }

                $pdo->commit();

            } catch (Throwable $erro) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $erro;
            }
        }
    }

    redirecionarEdicao($id);
}


// 5. Excluir imagem principal

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['excluir_imagem_principal'])
) {

    if (!empty($produto['imagem'])) {

        $caminhoImagem = $produto['imagem'];

        $sqlAtualizar = "UPDATE produtos
                         SET imagem = NULL
                         WHERE id = :id";

        $consultaAtualizar = $pdo->prepare($sqlAtualizar);

        $consultaAtualizar->execute([
            ':id' => $id
        ]);

        excluirArquivoSeNaoUtilizado(
            $pdo,
            $caminhoImagem,
            'produtos'
        );
    }

    redirecionarEdicao($id);
}


// 6. Excluir imagem adicional

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['excluir_imagem'])
) {

    $imagemId = filter_input(
        INPUT_POST,
        'imagem_id',
        FILTER_VALIDATE_INT
    );

    if ($imagemId && $imagemId > 0) {

        $sqlImagem = "SELECT id, caminho
                      FROM produto_imagens
                      WHERE id = :id
                      AND produto_id = :produto_id";

        $consultaImagem = $pdo->prepare($sqlImagem);

        $consultaImagem->execute([
            ':id' => $imagemId,
            ':produto_id' => $id
        ]);

        $imagem = $consultaImagem->fetch();

        if ($imagem) {

            $sqlExcluir = "DELETE FROM produto_imagens
                           WHERE id = :id
                           AND produto_id = :produto_id";

            $consultaExcluir = $pdo->prepare($sqlExcluir);

            $consultaExcluir->execute([
                ':id' => $imagemId,
                ':produto_id' => $id
            ]);

            excluirArquivoSeNaoUtilizado(
                $pdo,
                $imagem['caminho'],
                'produtos'
            );
        }
    }

    redirecionarEdicao($id);
}


// 7. Adicionar imagens à galeria

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['adicionar_imagens'])
) {

    if (
        !isset($_FILES['imagens']) ||
        !is_array($_FILES['imagens']['name'])
    ) {
        redirecionarEdicao($id);
    }

    $pastaImagens = __DIR__ . '/../uploads/produtos/';

    if (!is_dir($pastaImagens)) {
        mkdir($pastaImagens, 0755, true);
    }

    foreach ($_FILES['imagens']['name'] as $indice => $nomeOriginal) {

        $arquivo = [
            'name' => $_FILES['imagens']['name'][$indice],
            'tmp_name' => $_FILES['imagens']['tmp_name'][$indice],
            'error' => $_FILES['imagens']['error'][$indice],
            'size' => $_FILES['imagens']['size'][$indice]
        ];

        $extensao = validarImagem($arquivo);

        if ($extensao === false) {
            continue;
        }

        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

        $caminhoCompleto = $pastaImagens . $nomeArquivo;

        if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {

            $caminhoBanco = 'uploads/produtos/' . $nomeArquivo;

            $sqlInserir = "INSERT INTO produto_imagens (
                                produto_id,
                                caminho
                            ) VALUES (
                                :produto_id,
                                :caminho
                            )";

            $consultaInserir = $pdo->prepare($sqlInserir);

            $consultaInserir->execute([
                ':produto_id' => $id,
                ':caminho' => $caminhoBanco
            ]);
        }
    }

    redirecionarEdicao($id);
}


// 8. Substituir modelo 3D

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['atualizar_modelo_3d'])
) {

    if (
        isset($_FILES['modelo_3d']) &&
        $_FILES['modelo_3d']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if (!validarModelo3D($_FILES['modelo_3d'])) {
            $mensagem = 'Arquivo inválido. Envie um modelo GLB válido de até 30 MB.';
        } else {

            $pastaModelos = __DIR__ . '/../uploads/modelos/';

            if (!is_dir($pastaModelos)) {
                mkdir($pastaModelos, 0755, true);
            }

            $nomeArquivo = bin2hex(random_bytes(16)) . '.glb';

            $caminhoCompleto = $pastaModelos . $nomeArquivo;

            if (move_uploaded_file(
                $_FILES['modelo_3d']['tmp_name'],
                $caminhoCompleto
            )) {

                $modeloAntigo = $produto['modelo_3d'];

                $caminhoBanco = 'uploads/modelos/' . $nomeArquivo;

                $sqlAtualizar = "UPDATE produtos
                                 SET modelo_3d = :modelo
                                 WHERE id = :id";

                $consultaAtualizar = $pdo->prepare($sqlAtualizar);

                $consultaAtualizar->execute([
                    ':modelo' => $caminhoBanco,
                    ':id' => $id
                ]);

                excluirArquivoSeNaoUtilizado(
                    $pdo,
                    $modeloAntigo,
                    'modelos'
                );

                $_SESSION['mensagem_edicao'] = 'Modelo 3D atualizado com sucesso!';

                redirecionarEdicao($id);

            } else {
                $mensagem = 'Não foi possível salvar o modelo 3D.';
            }
        }

    } else {
        $mensagem = 'Selecione um arquivo GLB antes de continuar.';
    }
}


// 9. Excluir modelo 3D

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['excluir_modelo_3d'])
) {

    if (!empty($produto['modelo_3d'])) {

        $caminhoModelo = $produto['modelo_3d'];

        $sqlAtualizar = "UPDATE produtos
                         SET modelo_3d = NULL
                         WHERE id = :id";

        $consultaAtualizar = $pdo->prepare($sqlAtualizar);

        $consultaAtualizar->execute([
            ':id' => $id
        ]);

        excluirArquivoSeNaoUtilizado(
            $pdo,
            $caminhoModelo,
            'modelos'
        );
    }

    redirecionarEdicao($id);
}


// 10. Buscar categorias

$sqlCategorias = "SELECT id, nome
                  FROM categorias
                  ORDER BY nome ASC";

$categorias = $pdo->query($sqlCategorias)->fetchAll();


// 11. Buscar imagens da galeria

$sqlImagens = "SELECT id, caminho
               FROM produto_imagens
               WHERE produto_id = :produto_id
               ORDER BY id DESC";

$consultaImagens = $pdo->prepare($sqlImagens);

$consultaImagens->execute([
    ':produto_id' => $id
]);

$imagens = $consultaImagens->fetchAll();


// 12. Atualizar dados exibidos

$sqlProduto = "SELECT *
               FROM produtos
               WHERE id = :id";

$consultaProduto = $pdo->prepare($sqlProduto);

$consultaProduto->execute([
    ':id' => $id
]);

$produto = $consultaProduto->fetch();

if (isset($_SESSION['mensagem_edicao'])) {
    $mensagem = $_SESSION['mensagem_edicao'];
    unset($_SESSION['mensagem_edicao']);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar produto | NEXUS</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #080d18;
            color: #e8edf7;
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
        }

        .pagina {
            max-width: 1250px;
            margin: auto;
            padding: 40px 30px;
        }

        .cabecalho {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            gap: 20px;
        }

        .cabecalho h1 {
            font-size: 28px;
            margin-bottom: 9px;
        }

        .cabecalho p {
            color: #8995aa;
            font-size: 14px;
        }

        .botao-voltar {
            color: #b8c5da;
            text-decoration: none;
            border: 1px solid #26344d;
            padding: 12px 18px;
            border-radius: 9px;
        }

        .formulario {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 22px;
            align-items: start;
        }

        .coluna {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .cartao {
            background: #101827;
            border: 1px solid #202e44;
            border-radius: 13px;
            padding: 25px;
        }

        .cartao h2 {
            font-size: 17px;
            margin-bottom: 22px;
        }

        .grupo-campo {
            margin-bottom: 20px;
        }

        .grupo-campo:last-child {
            margin-bottom: 0;
        }

        .linha-campos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        label {
            display: block;
            color: #cbd5e5;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        input,
        select,
        textarea {
            width: 100%;
            background: #0a1220;
            border: 1px solid #293850;
            border-radius: 8px;
            padding: 13px 14px;
            color: #edf3ff;
            outline: none;
            font-size: 14px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #1687ff;
        }

        textarea {
            resize: vertical;
            min-height: 125px;
        }

        .ajuda {
            display: block;
            color: #8995aa;
            font-size: 12px;
            margin-top: 9px;
        }

        .opcao {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #0a1220;
            border: 1px solid #293850;
            padding: 15px;
            border-radius: 9px;
            margin-bottom: 12px;
        }

        .opcao input {
            width: 17px;
            height: 17px;
            accent-color: #1687ff;
        }

        .opcao label {
            margin: 0;
        }

        .opcao span {
            display: block;
            color: #75839a;
            font-size: 11px;
            margin-top: 4px;
        }

        .botoes {
            display: flex;
            gap: 12px;
            margin-top: 22px;
        }

        .botao {
            display: inline-block;
            border: none;
            border-radius: 9px;
            padding: 14px 20px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .botao-principal {
            flex: 1;
            color: white;
            background: #0879ed;
        }

        .botao-cancelar {
            color: #aebbd0;
            background: #192438;
        }

        .botao-gerenciar {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 12px 16px;
            margin-bottom: 20px;
            border: 1px solid #1687ff;
            border-radius: 8px;
            background: #102744;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }

        .botao-gerenciar:hover {
            background: #164477;
        }

        .modal-filtro {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0, 0, 0, .65);
            backdrop-filter: blur(5px);
        }

        .modal-filtro.ativo {
            display: flex;
        }

        .conteudo-filtro {
            width: 100%;
            max-width: 750px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 28px;
            border-radius: 20px;
            background: linear-gradient(120deg, #080d18, #10213c);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
        }

        .topo-filtro {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .topo-filtro h2 {
            margin: 0;
            color: #fff;
            font-size: 22px;
        }

        .fechar-filtro {
            border: none;
            background: transparent;
            color: #aaa;
            font-size: 30px;
            cursor: pointer;
        }

        .opcoes-filtro {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .opcoes-filtro h3 {
            color: #dce8fa;
            font-size: 15px;
        }

        .grade-imagens {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 18px;
        }

        .item-imagem {
            background: #17243a;
            border: 1px solid #263752;
            border-radius: 12px;
            padding: 10px;
            overflow: hidden;
        }

        .item-imagem img {
            width: 100%;
            height: 130px;
            object-fit: contain;
            background: #0b1220;
            border-radius: 8px;
        }

        .item-imagem span {
            display: block;
            color: #c4d3e8;
            font-size: 12px;
            margin-top: 10px;
        }

        .botao-excluir-imagem {
            width: 100%;
            padding: 9px;
            margin-top: 10px;
            border: none;
            border-radius: 8px;
            background: #dc3545;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .botao-excluir-imagem:hover {
            background: #b02a37;
        }

        .botao-principal-imagem {
            width: 100%;
            padding: 9px;
            margin-top: 8px;
            border: 1px solid #2675e8;
            border-radius: 8px;
            background: transparent;
            color: #70aaff;
            font-weight: 600;
            cursor: pointer;
        }

        .botao-principal-imagem:hover {
            background: #2675e8;
            color: white;
        }

        .botao-adicionar {
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            background: #0879ed;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .botao-adicionar:hover {
            background: #0563c5;
        }

        .mensagem {
            padding: 14px 18px;
            margin-bottom: 22px;
            border: 1px solid #2675e8;
            border-radius: 9px;
            background: #102744;
            color: #b8d7ff;
            font-size: 14px;
        }

        .modelo-cadastrado {
            color: #4ade80;
            font-size: 13px;
            margin-bottom: 12px;
        }

        @media (max-width: 850px) {
            .formulario {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 550px) {
            .pagina {
                padding: 25px 15px;
            }

            .cabecalho {
                align-items: flex-start;
                flex-direction: column;
            }

            .linha-campos {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .botoes {
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

    <main class="pagina">

        <header class="cabecalho">

            <div>
                <h1>Editar produto</h1>
                <p>Atualize as informações do produto.</p>
            </div>

            <a href="produtos.php" class="botao-voltar">
                ← Voltar para produtos
            </a>

        </header>


        <?php if (!empty($mensagem)): ?>

            <div class="mensagem">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>


        <!-- 13. Formulário principal -->

        <form id="formProduto" action="" method="POST"></form>

        <div class="formulario">

                <div class="coluna">

                    <section class="cartao">

                        <h2>Informações do produto</h2>

                        <div class="grupo-campo">

                            <label for="nome">Nome do produto</label>

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                form="formProduto"
                                maxlength="150"
                                value="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>

                        <div class="linha-campos">

                            <div class="grupo-campo">

                                <label for="codigo">Código</label>

                                <input
                                    type="text"
                                    id="codigo"
                                    name="codigo"
                                    form="formProduto"
                                    maxlength="40"
                                    value="<?= htmlspecialchars($produto['codigo'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                            </div>

                            <div class="grupo-campo">

                                <label for="categoria_id">Categoria</label>

                                <select name="categoria_id" id="categoria_id" form="formProduto">

                                    <option value="">Sem categoria</option>

                                    <?php foreach ($categorias as $categoria): ?>

                                        <option
                                            value="<?= $categoria['id'] ?>"
                                            <?= $produto['categoria_id'] == $categoria['id'] ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>

                        <div class="grupo-campo">

                            <label for="descricao">Descrição</label>

                            <textarea
                                id="descricao"
                                name="descricao"
                                form="formProduto"
                            ><?= htmlspecialchars($produto['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

                        </div>

                    </section>


                    <section class="cartao">

                        <h2>Preço e estoque</h2>

                        <div class="linha-campos">

                            <div class="grupo-campo">

                                <label for="preco">Preço (R$)</label>

                                <input
                                    type="number"
                                    id="preco"
                                    name="preco"
                                    form="formProduto"
                                    step="0.01"
                                    min="0.01"
                                    value="<?= htmlspecialchars((string) $produto['preco'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                            </div>

                            <div class="grupo-campo">

                                <label for="estoque">Estoque</label>

                                <input
                                    type="number"
                                    id="estoque"
                                    name="estoque"
                                    form="formProduto"
                                    min="0"
                                    value="<?= (int) $produto['estoque'] ?>"
                                    required
                                >

                            </div>

                        </div>

                    </section>


                    <section class="cartao">

                        <h2>Imagens do produto</h2>

                        <button
                            type="button"
                            class="botao-gerenciar"
                            onclick="abrirGerenciadorImagens()"
                        >
                            ▦ Gerenciar imagens
                        </button>

                        <p class="ajuda">
                            Adicione imagens, escolha a imagem principal ou exclua imagens.
                        </p>

                    </section>

                </div>


                <aside class="coluna">

                    <section class="cartao">

                        <h2>Visibilidade</h2>

                        <div class="opcao">

                            <input
                                type="checkbox"
                                id="ativo"
                                name="ativo"
                                form="formProduto"
                                value="1"
                                <?= $produto['ativo'] == 1 ? 'checked' : '' ?>
                            >

                            <label for="ativo">
                                Produto ativo
                                <span>Disponível na loja.</span>
                            </label>

                        </div>

                        <div class="opcao">

                            <input
                                type="checkbox"
                                id="destaque"
                                name="destaque"
                                form="formProduto"
                                value="1"
                                <?= $produto['destaque'] == 1 ? 'checked' : '' ?>
                            >

                            <label for="destaque">
                                Produto em destaque
                                <span>Exibir na área de destaques.</span>
                            </label>

                        </div>

                    </section>


                    <!-- 14. Gerenciador do modelo 3D -->

                    <section class="cartao">

                        <h2>Modelo 3D</h2>

                        <?php if (!empty($produto['modelo_3d'])): ?>

                            <p class="modelo-cadastrado">
                                ✓ Modelo 3D cadastrado
                            </p>

                            <p class="ajuda">
                                <?= htmlspecialchars(basename($produto['modelo_3d']), ENT_QUOTES, 'UTF-8') ?>
                            </p>

                            <form
                                method="POST"
                                onsubmit="return confirm('Deseja excluir o modelo 3D?');"
                            >

                                <button
                                    type="submit"
                                    name="excluir_modelo_3d"
                                    value="1"
                                    class="botao-excluir-imagem"
                                >
                                    Excluir modelo 3D
                                </button>

                            </form>

                        <?php else: ?>

                            <p class="ajuda">
                                Nenhum modelo 3D cadastrado.
                            </p>

                        <?php endif; ?>


                        <form
                            method="POST"
                            enctype="multipart/form-data"
                            style="margin-top: 18px;"
                        >

                            <div class="grupo-campo">

                                <label for="modelo_3d">
                                    <?= !empty($produto['modelo_3d']) ? 'Substituir modelo' : 'Enviar modelo' ?>
                                </label>

                                <input
                                    type="file"
                                    id="modelo_3d"
                                    name="modelo_3d"
                                    accept=".glb,model/gltf-binary"
                                    required
                                >

                                <span class="ajuda">
                                    Formato GLB. Tamanho máximo: 30 MB.
                                </span>

                            </div>

                            <button
                                type="submit"
                                name="atualizar_modelo_3d"
                                value="1"
                                class="botao-adicionar"
                            >
                                Salvar modelo 3D
                            </button>

                        </form>

                    </section>


                    <section class="cartao">

                        <h2>Salvar alterações</h2>

                        <p class="ajuda">
                            Confira os dados antes de atualizar.
                        </p>

                        <div class="botoes">

                            <a href="produtos.php" class="botao botao-cancelar">
                                Cancelar
                            </a>

                            <button
                                type="submit"
                                name="salvar_produto"
                                value="1"
                                form="formProduto"
                                class="botao botao-principal"
                            >
                                Salvar alterações
                            </button>

                        </div>

                    </section>

                </aside>

            </div>


        <!-- 15. Gerenciador de imagens -->

        <div class="modal-filtro" id="modalImagens">

            <div class="conteudo-filtro">

                <div class="topo-filtro">

                    <h2>Gerenciar imagens</h2>

                    <button
                        type="button"
                        class="fechar-filtro"
                        onclick="fecharGerenciadorImagens()"
                    >
                        &times;
                    </button>

                </div>


                <div class="opcoes-filtro">

                    <h3>Imagem principal</h3>

                    <?php if (!empty($produto['imagem'])): ?>

                        <div class="item-imagem">

                            <img
                                src="../<?= htmlspecialchars($produto['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="Imagem principal"
                            >

                            <span>Imagem principal</span>

                            <form
                                method="POST"
                                onsubmit="return confirm('Deseja excluir a imagem principal?');"
                            >

                                <button
                                    type="submit"
                                    name="excluir_imagem_principal"
                                    value="1"
                                    class="botao-excluir-imagem"
                                >
                                    Excluir imagem principal
                                </button>

                            </form>

                        </div>

                    <?php else: ?>

                        <p>Nenhuma imagem principal cadastrada.</p>

                    <?php endif; ?>


                    <h3>Imagens adicionais</h3>


                    <!-- 16. Adicionar imagens -->

                    <form method="POST" enctype="multipart/form-data">

                        <input
                            type="file"
                            name="imagens[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            required
                        >

                        <button
                            type="submit"
                            name="adicionar_imagens"
                            value="1"
                            class="botao-adicionar"
                        >
                            Adicionar imagens
                        </button>

                    </form>


                    <!-- 17. Exibir galeria -->

                    <div class="grade-imagens">

                        <?php foreach ($imagens as $imagem): ?>

                            <div class="item-imagem">

                                <img
                                    src="../<?= htmlspecialchars($imagem['caminho'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Imagem adicional"
                                >

                                <span>Imagem adicional</span>


                                <form
                                    method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja excluir esta imagem?');"
                                >

                                    <input
                                        type="hidden"
                                        name="imagem_id"
                                        value="<?= (int) $imagem['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="excluir_imagem"
                                        value="1"
                                        class="botao-excluir-imagem"
                                    >
                                        Excluir
                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    onsubmit="return confirm('Deseja definir esta imagem como principal?');"
                                >

                                    <input
                                        type="hidden"
                                        name="imagem_id"
                                        value="<?= (int) $imagem['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="definir_imagem_principal"
                                        value="1"
                                        class="botao-principal-imagem"
                                    >
                                        Definir como principal
                                    </button>

                                </form>

                            </div>

                        <?php endforeach; ?>


                        <?php if (empty($imagens)): ?>

                            <p>Nenhuma imagem adicional cadastrada.</p>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </main>


    <!-- 18. JavaScript do modal -->

    <script>

        function abrirGerenciadorImagens() {
            document.getElementById('modalImagens').classList.add('ativo');
        }

        function fecharGerenciadorImagens() {
            document.getElementById('modalImagens').classList.remove('ativo');
        }

        const modalImagens = document.getElementById('modalImagens');

        modalImagens.addEventListener('click', function(event) {

            if (event.target === modalImagens) {
                fecharGerenciadorImagens();
            }

        });

    </script>

</body>

</html>
