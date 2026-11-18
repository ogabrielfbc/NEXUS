
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

$mensagemErro = '';

function salvarArquivoProduto($arquivo, $tipo)
{
    if (
        !isset($arquivo['error']) ||
        $arquivo['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erro ao enviar o arquivo.');
    }

    if (!is_uploaded_file($arquivo['tmp_name'])) {
        throw new RuntimeException('Arquivo enviado inválido.');
    }

    $tamanho = filesize($arquivo['tmp_name']);

    if ($tipo === 'imagem') {

        if ($tamanho > 5 * 1024 * 1024) {
            throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
        }

        $informacoes = getimagesize($arquivo['tmp_name']);

        if ($informacoes === false) {
            throw new RuntimeException('O arquivo não é uma imagem válida.');
        }

        $formatos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $mime = $informacoes['mime'];

        if (!isset($formatos[$mime])) {
            throw new RuntimeException('Formato de imagem não permitido.');
        }

        $extensao = $formatos[$mime];
        $pasta = 'produtos';

    } elseif ($tipo === 'modelo') {

        if ($tamanho > 30 * 1024 * 1024) {
            throw new RuntimeException('O modelo 3D deve ter no máximo 30 MB.');
        }

        $extensaoOriginal = strtolower(
            pathinfo($arquivo['name'], PATHINFO_EXTENSION)
        );

        $arquivoModelo = fopen($arquivo['tmp_name'], 'rb');
        $cabecalho = fread($arquivoModelo, 12);
        fclose($arquivoModelo);

        $versao = unpack('Vversao', substr($cabecalho, 4, 4));

        if (
            $extensaoOriginal !== 'glb' ||
            substr($cabecalho, 0, 4) !== 'glTF' ||
            !$versao ||
            $versao['versao'] !== 2
        ) {
            throw new RuntimeException('Selecione um modelo GLB válido.');
        }

        $extensao = 'glb';
        $pasta = 'modelos';

    } else {
        throw new RuntimeException('Tipo de arquivo inválido.');
    }

    $diretorio = __DIR__ . '/../uploads/' . $pasta;

    if (!is_dir($diretorio)) {
        if (!mkdir($diretorio, 0755, true)) {
            throw new RuntimeException('Não foi possível criar a pasta.');
        }
    }

    $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

    $destino = $diretorio . '/' . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Não foi possível salvar o arquivo.');
    }

    return 'uploads/' . $pasta . '/' . $nomeArquivo;
}




if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');

    $categoriaId = !empty($_POST['categoria_id'])
        ? (int) $_POST['categoria_id']
        : null;

    $descricao = trim($_POST['descricao'] ?? '');
    $preco = filter_var($_POST['preco'] ?? '', FILTER_VALIDATE_FLOAT);
    $estoque = filter_var($_POST['estoque'] ?? '', FILTER_VALIDATE_INT);

    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $arquivosSalvos = [];

    if (
        $nome === '' ||
        $codigo === '' ||
        $preco === false ||
        $preco <= 0 ||
        $estoque === false ||
        $estoque < 0
    ) {

        $mensagemErro = 'Preencha os campos corretamente.';

    } else {

        try {

            // Salvar imagem principal
            $imagem = salvarArquivoProduto(
                $_FILES['imagem'] ?? [],
                'imagem'
            );

            if ($imagem !== null) {
                $arquivosSalvos[] = $imagem;
            }

            // Salvar modelo 3D
            $modelo3d = salvarArquivoProduto(
                $_FILES['modelo_3d'] ?? [],
                'modelo'
            );

            if ($modelo3d !== null) {
                $arquivosSalvos[] = $modelo3d;
            }

            $sql = "INSERT INTO produtos (
                        codigo,
                        categoria_id,
                        nome,
                        descricao,
                        preco,
                        estoque,
                        imagem,
                        modelo_3d,
                        destaque,
                        ativo
                    ) VALUES (
                        :codigo,
                        :categoria_id,
                        :nome,
                        :descricao,
                        :preco,
                        :estoque,
                        :imagem,
                        :modelo_3d,
                        :destaque,
                        :ativo
                    )";

            $consulta = $pdo->prepare($sql);

            $consulta->execute([
                ':codigo' => $codigo,
                ':categoria_id' => $categoriaId,
                ':nome' => $nome,
                ':descricao' => $descricao,
                ':preco' => $preco,
                ':estoque' => $estoque,
                ':imagem' => $imagem,
                ':modelo_3d' => $modelo3d,
                ':destaque' => $destaque,
                ':ativo' => $ativo
            ]);

            header('Location: produtos.php?sucesso=1');
            exit;

        } catch (Throwable $erro) {

            // Remover arquivos enviados se o cadastro falhar
            foreach ($arquivosSalvos as $arquivoSalvo) {

                $caminho = __DIR__ . '/../' . $arquivoSalvo;

                if (is_file($caminho)) {
                    unlink($caminho);
                }
            }

            if ($erro instanceof PDOException && $erro->getCode() === '23000') {

                $mensagemErro = 'Esse código de produto já está cadastrado.';

            } elseif ($erro instanceof RuntimeException) {

                $mensagemErro = $erro->getMessage();

            } else {

                $mensagemErro = 'Ocorreu um erro ao cadastrar o produto.';
            }
        }
    }
}

$sqlCategorias = "SELECT
                    id,
                    nome
                  FROM categorias
                  ORDER BY nome ASC";

$categorias = $pdo->query($sqlCategorias)->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Adicionar produto | NEXUS</title>

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

        .titulo-pagina h1 {
            font-size: 28px;
            margin-bottom: 9px;
        }

        .titulo-pagina p {
            color: #8995aa;
            font-size: 14px;
        }

        .botao-voltar {
            color: #b8c5da;
            text-decoration: none;
            border: 1px solid #26344d;
            padding: 12px 18px;
            border-radius: 9px;
            transition: 0.2s;
            font-size: 14px;
        }

        .botao-voltar:hover {
            border-color: #1687ff;
            color: #1687ff;
        }

        .mensagem-erro {
            background: rgba(255, 65, 85, 0.10);
            border: 1px solid rgba(255, 65, 85, 0.35);
            color: #ff7888;
            padding: 15px;
            border-radius: 9px;
            margin-bottom: 22px;
        }

        .formulario {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 22px;
            align-items: start;
        }

        .coluna-principal,
        .coluna-lateral {
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

        .cabecalho-cartao {
            margin-bottom: 24px;
        }

        .cabecalho-cartao h2 {
            font-size: 17px;
            margin-bottom: 7px;
        }

        .cabecalho-cartao p {
            color: #8390a5;
            font-size: 13px;
            line-height: 1.5;
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
            transition: 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #1687ff;
            box-shadow: 0 0 0 3px rgba(22, 135, 255, 0.10);
        }

        input::placeholder,
        textarea::placeholder {
            color: #59677d;
        }

        textarea {
            resize: vertical;
            min-height: 125px;
            line-height: 1.6;
        }

        select {
            cursor: pointer;
        }

        .ajuda {
            display: block;
            color: #75839a;
            font-size: 12px;
            margin-top: 8px;
        }

        .area-arquivo {
            background: #0a1220;
            border: 1px dashed #344761;
            border-radius: 10px;
            padding: 23px 15px;
            text-align: center;
            transition: 0.2s;
        }

        .area-arquivo:hover {
            border-color: #1687ff;
            background: #0c192b;
        }

        .icone-arquivo {
            font-size: 28px;
            margin-bottom: 12px;
        }

        .area-arquivo strong {
            display: block;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .area-arquivo p {
            color: #75839a;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .area-arquivo input {
            background: transparent;
            border: none;
            padding: 0;
            font-size: 12px;
            max-width: 100%;
        }

        .previa-imagem {
            display: none;
            width: 100%;
            max-height: 190px;
            object-fit: contain;
            margin-top: 15px;
            border-radius: 8px;
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
            cursor: pointer;
        }

        .opcao label {
            margin: 0;
            cursor: pointer;
            line-height: 1.4;
        }

        .opcao label span {
            display: block;
            color: #75839a;
            font-size: 11px;
            font-weight: normal;
            margin-top: 4px;
        }

        .botoes {
            display: flex;
            gap: 12px;
            margin-top: 22px;
        }

        .botao {
            border: none;
            border-radius: 9px;
            padding: 14px 20px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: 0.2s;
        }

        .botao-principal {
            flex: 1;
            color: white;
            background: #0879ed;
        }

        .botao-principal:hover {
            background: #168cff;
            transform: translateY(-1px);
        }

        .botao-cancelar {
            color: #aebbd0;
            background: #192438;
        }

        .botao-cancelar:hover {
            background: #25344d;
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

            .cartao {
                padding: 19px;
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

            <div class="titulo-pagina">

                <h1>Adicionar produto</h1>

                <p>Cadastre um novo produto no catálogo NEXUS.</p>

            </div>

            <a href="produtos.php" class="botao-voltar">
                ← Voltar para produtos
            </a>

        </header>

        <?php if ($mensagemErro !== ''): ?>

            <div class="mensagem-erro">
                <?= htmlspecialchars($mensagemErro) ?>
            </div>

        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">

            <div class="formulario">

                <div class="coluna-principal">

                    <section class="cartao">

                        <div class="cabecalho-cartao">

                            <h2>Informações do produto</h2>

                            <p>Adicione os dados principais do produto.</p>

                        </div>

                        <div class="grupo-campo">

                            <label for="nome">Nome do produto</label>

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                placeholder="Ex: Teclado Mecânico RGB"
                                maxlength="150"
                                required
                            >

                        </div>

                        <div class="linha-campos">

                            <div class="grupo-campo">

                                <label for="codigo">Código do produto</label>

                                <input
                                    type="text"
                                    id="codigo"
                                    name="codigo"
                                    placeholder="Ex: NEX-TEC-001"
                                    maxlength="40"
                                    required
                                >

                            </div>

                            <div class="grupo-campo">

                                <label for="categoria_id">Categoria</label>

                                <select name="categoria_id" id="categoria_id">

                                    <option value="">Selecione uma categoria</option>

                                    <?php foreach ($categorias as $categoria): ?>

                                        <option value="<?= $categoria['id'] ?>">
                                            <?= htmlspecialchars($categoria['nome']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>

                        <div class="grupo-campo">

                            <label for="descricao">Descrição do produto</label>

                            <textarea
                                id="descricao"
                                name="descricao"
                                placeholder="Descreva as características e informações do produto..."
                            ></textarea>

                        </div>

                    </section>

                    <section class="cartao">

                        <div class="cabecalho-cartao">

                            <h2>Preço e estoque</h2>

                            <p>Defina o valor de venda e a quantidade disponível.</p>

                        </div>

                        <div class="linha-campos">

                            <div class="grupo-campo">

                                <label for="preco">Preço (R$)</label>

                                <input
                                    type="number"
                                    id="preco"
                                    name="preco"
                                    placeholder="0,00"
                                    step="0.01"
                                    min="0.01"
                                    required
                                >

                            </div>

                            <div class="grupo-campo">

                                <label for="estoque">Quantidade em estoque</label>

                                <input
                                    type="number"
                                    id="estoque"
                                    name="estoque"
                                    placeholder="Ex: 20"
                                    min="0"
                                    required
                                >

                            </div>

                        </div>

                    </section>

                    <section class="cartao">

                        <div class="cabecalho-cartao">

                            <h2>Arquivos do produto</h2>

                            <p>Adicione os arquivos visuais que serão exibidos na loja.</p>

                        </div>

                        <div class="grupo-campo">

                            <label>Imagem principal</label>

                            <div class="area-arquivo">

                                <div class="icone-arquivo">🖼️</div>

                                <strong>Imagem do produto</strong>

                                <p>Formatos JPG, PNG ou WEBP</p>

                                <input
                                    type="file"
                                    id="imagem"
                                    name="imagem"
                                    accept="image/jpeg,image/png,image/webp"
                                >

                                <img
                                    src=""
                                    id="previaImagem"
                                    class="previa-imagem"
                                    alt="Pré-visualização da imagem"
                                >

                            </div>

                        </div>

                        <div class="grupo-campo">

                            <label>Modelo 3D</label>

                            <div class="area-arquivo">

                                <div class="icone-arquivo">🧊</div>

                                <strong>Modelo tridimensional</strong>

                                <p>Selecione um arquivo no formato GLB.</p>

                                <input
                                    type="file"
                                    name="modelo_3d"
                                    accept=".glb"
                                >

                            </div>

                        </div>

                    </section>

                </div>

                <aside class="coluna-lateral">

                    <section class="cartao">

                        <div class="cabecalho-cartao">

                            <h2>Visibilidade</h2>

                            <p>Controle como o produto aparece na loja.</p>

                        </div>

                        <div class="opcao">

                            <input
                                type="checkbox"
                                id="ativo"
                                name="ativo"
                                value="1"
                                checked
                            >

                            <label for="ativo">
                                Produto ativo
                                <span>Disponível para aparecer no catálogo.</span>
                            </label>

                        </div>

                        <div class="opcao">

                            <input
                                type="checkbox"
                                id="destaque"
                                name="destaque"
                                value="1"
                            >

                            <label for="destaque">
                                Produto em destaque
                                <span>Permite exibir o produto na área de destaques.</span>
                            </label>

                        </div>

                    </section>

                    <section class="cartao">

                        <div class="cabecalho-cartao">

                            <h2>Finalizar cadastro</h2>

                            <p>Confira as informações antes de salvar.</p>

                        </div>

                        <div class="botoes">

                            <a href="produtos.php" class="botao botao-cancelar">
                                Cancelar
                            </a>

                            <button type="submit" class="botao botao-principal">
                                Cadastrar produto
                            </button>

                        </div>

                    </section>

                </aside>

            </div>

        </form>

    </main>

    <script>

        const campoImagem = document.getElementById('imagem');
        const previaImagem = document.getElementById('previaImagem');

        campoImagem.addEventListener('change', function () {

            const arquivo = this.files[0];

            if (!arquivo) {
                previaImagem.style.display = 'none';
                previaImagem.src = '';
                return;
            }

            const formatosPermitidos = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (!formatosPermitidos.includes(arquivo.type)) {
                alert('Selecione uma imagem JPG, PNG ou WEBP.');
                this.value = '';
                previaImagem.style.display = 'none';
                previaImagem.src = '';
                return;
            }

            const leitor = new FileReader();

            leitor.onload = function (evento) {
                previaImagem.src = evento.target.result;
                previaImagem.style.display = 'block';
            };

            leitor.readAsDataURL(arquivo);

        });

    </script>

</body>

</html>