
<?php

// 1. Conecta ao banco de dados

$pdo = require __DIR__ . '/config/database.php';

// 2. Recebe os filtros enviados pela URL

$categoriaSelecionada = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
$precoMinimo = filter_input(INPUT_GET, 'preco_minimo', FILTER_VALIDATE_FLOAT);
$precoMaximo = filter_input(INPUT_GET, 'preco_maximo', FILTER_VALIDATE_FLOAT);

$ordemSelecionada = $_GET['ordem'] ?? 'recentes';
$busca = $_GET['busca'] ?? '';

if (!is_string($busca)) {
    $busca = '';
}

$busca = trim($busca);

$somenteDisponiveis = isset($_GET['disponivel'])
    && $_GET['disponivel'] === '1';

// 3. Valida os valores dos filtros

if ($categoriaSelecionada === false || $categoriaSelecionada < 1) {
    $categoriaSelecionada = null;
}

if ($precoMinimo === false || $precoMinimo < 0) {
    $precoMinimo = null;
}

if ($precoMaximo === false || $precoMaximo < 0) {
    $precoMaximo = null;
}

if (
    $precoMinimo !== null &&
    $precoMaximo !== null &&
    $precoMinimo > $precoMaximo
) {
    $precoMinimo = null;
    $precoMaximo = null;
}

// 4. Define as opções de ordenação

$ordenacoes = [
    'recentes' => 'p.criado_em DESC',
    'menor_preco' => 'p.preco ASC',
    'maior_preco' => 'p.preco DESC',
    'nome' => 'p.nome ASC'
];

if (!is_string($ordemSelecionada) || !isset($ordenacoes[$ordemSelecionada])) {
    $ordemSelecionada = 'recentes';
}

$ordenacaoSQL = $ordenacoes[$ordemSelecionada];

// 5. Busca as categorias disponíveis

$sqlCategorias = "SELECT
                      id,
                      nome
                  FROM categorias
                  ORDER BY nome ASC";

$consultaCategorias = $pdo->query($sqlCategorias);
$categorias = $consultaCategorias->fetchAll();

// 6. Prepara a consulta dos produtos

$sql = "SELECT
            p.id,
            p.nome,
            p.descricao,
            p.preco,
            p.estoque,
            p.imagem,
            c.nome AS categoria_nome
        FROM produtos p
        LEFT JOIN categorias c
            ON p.categoria_id = c.id
        WHERE p.ativo = 1";

$parametros = [];

// 7. Aplica o filtro por categoria

if ($categoriaSelecionada !== null) {
    $sql .= " AND p.categoria_id = :categoria_id";
    $parametros[':categoria_id'] = $categoriaSelecionada;
}

// 8. Aplica os filtros de preço

if ($precoMinimo !== null) {
    $sql .= " AND p.preco >= :preco_minimo";
    $parametros[':preco_minimo'] = $precoMinimo;
}

if ($precoMaximo !== null) {
    $sql .= " AND p.preco <= :preco_maximo";
    $parametros[':preco_maximo'] = $precoMaximo;
}

// 9. Aplica a busca pelo nome do produto

if ($busca !== '') {
    $sql .= " AND p.nome LIKE :busca";
    $parametros[':busca'] = '%' . $busca . '%';
}

// 10. Filtra somente os produtos disponíveis

if ($somenteDisponiveis) {
    $sql .= " AND p.estoque > 0";
}

// 11. Define a ordenação e busca os produtos

$sql .= " ORDER BY " . $ordenacaoSQL;

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);

$produtos = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NEXUS TECH | Todos os produtos</title>

    <!-- 12. Carrega o Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- 13. Carrega o CSS principal -->

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <!-- 14. Menu de navegação -->

    <nav class="navbar navbar-expand-lg navbar-dark">

        <div class="container">

            <a class="navbar-brand" href="index.php">
                NEXUS<span>TECH</span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menu"
                aria-controls="menu"
                aria-expanded="false"
                aria-label="Abrir menu"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu">

                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            Início
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="produtos.php">
                            Produtos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            Minha conta
                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </nav>

    <!-- 15. Catálogo de produtos -->

    <section class="container mt-5 pt-4" id="produtos">

        <h2 class="section-title">
            Todos os produtos
        </h2>

        <p>
            Explore nosso catálogo e encontre os equipamentos ideais para seu setup.
        </p>

        <!-- 16. Botão para abrir os filtros -->

        <div class="mb-4">

            <button
                type="button"
                class="btn-filtro"
                onclick="abrirFiltros()"
            >
                ☰ Filtrar produtos
            </button>

        </div>

        <!-- 17. Modal de filtros -->

        <div class="modal-filtro" id="modalFiltro">

            <div class="conteudo-filtro">

                <div class="topo-filtro">

                    <h2>Filtrar produtos</h2>

                    <button
                        type="button"
                        class="fechar-filtro"
                        onclick="fecharFiltros()"
                    >
                        &times;
                    </button>

                </div>

                <form method="GET" action="produtos.php#produtos">

                    <div class="opcoes-filtro">

                        <!-- Busca -->

                        <div class="campo-filtro">

                            <label for="busca">
                                Buscar produto
                            </label>

                            <input
                                type="text"
                                name="busca"
                                id="busca"
                                placeholder="Ex.: Teclado, mouse..."
                                value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>"
                            >

                        </div>

                        <!-- Categoria -->

                        <div class="campo-filtro">

                            <label for="categoria">
                                Categoria
                            </label>

                            <select name="categoria" id="categoria">

                                <option value="">
                                    Todas as categorias
                                </option>

                                <?php foreach ($categorias as $categoria): ?>

                                    <option
                                        value="<?= (int) $categoria['id'] ?>"
                                        <?= $categoriaSelecionada === (int) $categoria['id'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <!-- Preço mínimo -->

                        <div class="campo-filtro">

                            <label for="preco_minimo">
                                Preço mínimo
                            </label>

                            <input
                                type="number"
                                name="preco_minimo"
                                id="preco_minimo"
                                placeholder="R$ 0,00"
                                min="0"
                                step="0.01"
                                value="<?= $precoMinimo !== null ? htmlspecialchars((string) $precoMinimo, ENT_QUOTES, 'UTF-8') : '' ?>"
                            >

                        </div>

                        <!-- Preço máximo -->

                        <div class="campo-filtro">

                            <label for="preco_maximo">
                                Preço máximo
                            </label>

                            <input
                                type="number"
                                name="preco_maximo"
                                id="preco_maximo"
                                placeholder="R$ 0,00"
                                min="0"
                                step="0.01"
                                value="<?= $precoMaximo !== null ? htmlspecialchars((string) $precoMaximo, ENT_QUOTES, 'UTF-8') : '' ?>"
                            >

                        </div>

                        <!-- Ordenação -->

                        <div class="campo-filtro">

                            <label for="ordem">
                                Ordenar por
                            </label>

                            <select name="ordem" id="ordem">

                                <option value="recentes" <?= $ordemSelecionada === 'recentes' ? 'selected' : '' ?>>
                                    Mais recentes
                                </option>

                                <option value="menor_preco" <?= $ordemSelecionada === 'menor_preco' ? 'selected' : '' ?>>
                                    Menor preço
                                </option>

                                <option value="maior_preco" <?= $ordemSelecionada === 'maior_preco' ? 'selected' : '' ?>>
                                    Maior preço
                                </option>

                                <option value="nome" <?= $ordemSelecionada === 'nome' ? 'selected' : '' ?>>
                                    Nome do produto
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- Disponibilidade -->

                    <div class="campo-disponibilidade">

                        <label>

                            <input
                                type="checkbox"
                                name="disponivel"
                                value="1"
                                <?= $somenteDisponiveis ? 'checked' : '' ?>
                            >

                            Somente produtos em estoque

                        </label>

                    </div>

                    <!-- Ações dos filtros -->

                    <div class="acoes-filtro">

                        <a
                            href="produtos.php#produtos"
                            class="btn-limpar-filtro"
                        >
                            Limpar filtros
                        </a>

                        <button
                            type="submit"
                            class="btn-aplicar-filtro"
                        >
                            Aplicar filtros
                        </button>

                    </div>

                </form>

            </div>

        </div>

        <!-- 18. Exibe os resultados -->

        <div class="row g-4">

            <?php if (!empty($produtos)): ?>

                <?php foreach ($produtos as $produto): ?>

                    <div class="col-12 col-sm-6 col-lg-3">

                        <div class="product-card">

                            <!-- Imagem do produto -->

                            <div class="product-image">

                                <?php if (!empty($produto['imagem'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($produto['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <span>🖥️</span>

                                <?php endif; ?>

                            </div>

                            <!-- Informações do produto -->

                            <div class="product-info">

                                <h3>
                                    <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>
                                </h3>

                                <?php if (!empty($produto['categoria_nome'])): ?>

                                    <small>
                                        <?= htmlspecialchars($produto['categoria_nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </small>

                                <?php endif; ?>

                                <p>
                                    <?= htmlspecialchars($produto['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </p>

                                <h4>
                                    R$ <?= number_format((float) $produto['preco'], 2, ',', '.') ?>
                                </h4>

                                <small>
                                    Estoque: <?= (int) $produto['estoque'] ?>
                                </small>

                                <a
                                    href="produto.php?id=<?= (int) $produto['id'] ?>"
                                    class="btn-product"
                                >
                                    Ver detalhes
                                </a>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12">

                    <p class="text-center">
                        Nenhum produto encontrado com os filtros selecionados.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>

    <!-- 19. Rodapé -->

    <footer>

        <p>
            © 2026 NEXUS TECH.
            Todos os direitos reservados.
        </p>

        <p>
            Tecnologia, performance e inovação.
        </p>

    </footer>

    <!-- 20. JavaScript dos filtros -->

    <script>

        function abrirFiltros() {
            document.getElementById('modalFiltro').classList.add('ativo');
        }

        function fecharFiltros() {
            document.getElementById('modalFiltro').classList.remove('ativo');
        }

        const modalFiltro = document.getElementById('modalFiltro');

        modalFiltro.addEventListener('click', function(event) {

            if (event.target === modalFiltro) {
                fecharFiltros();
            }

        });

    </script>

    <!-- 21. JavaScript do Bootstrap -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
