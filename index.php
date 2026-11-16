
<?php

$pdo = require __DIR__ . '/config/database.php';

// ==========================================
// 1. CONFIGURAÇÕES DOS FILTROS
// ==========================================

$categoriaSelecionada = filter_input(
    INPUT_GET,
    'categoria',
    FILTER_VALIDATE_INT
);

$precoMinimo = filter_input(
    INPUT_GET,
    'preco_minimo',
    FILTER_VALIDATE_FLOAT
);

$precoMaximo = filter_input(
    INPUT_GET,
    'preco_maximo',
    FILTER_VALIDATE_FLOAT
);

$ordemSelecionada = $_GET['ordem'] ?? 'recentes';

// Valores inválidos são ignorados.

if ($categoriaSelecionada === false || $categoriaSelecionada < 1) {
    $categoriaSelecionada = null;
}

if ($precoMinimo === false || $precoMinimo < 0) {
    $precoMinimo = null;
}

if ($precoMaximo === false || $precoMaximo < 0) {
    $precoMaximo = null;
}

// ==========================================
// 2. OPÇÕES DE ORDENAÇÃO
// ==========================================

$ordenacoes = [
    'recentes' => 'p.criado_em DESC',
    'menor_preco' => 'p.preco ASC',
    'maior_preco' => 'p.preco DESC',
    'nome' => 'p.nome ASC'
];

if (!isset($ordenacoes[$ordemSelecionada])) {
    $ordemSelecionada = 'recentes';
}

$ordenacaoSQL = $ordenacoes[$ordemSelecionada];

// ==========================================
// 3. BUSCAR CATEGORIAS
// ==========================================

$sqlCategorias = "SELECT
                      id,
                      nome
                  FROM categorias
                  ORDER BY nome ASC";

$consultaCategorias = $pdo->query($sqlCategorias);

$categorias = $consultaCategorias->fetchAll();

// ==========================================
// 4. CONSTRUIR A CONSULTA DOS PRODUTOS
// ==========================================

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

// FILTRO POR CATEGORIA

if ($categoriaSelecionada !== null) {

    $sql .= " AND p.categoria_id = :categoria_id";

    $parametros[':categoria_id'] = $categoriaSelecionada;
}

// FILTRO POR PREÇO MÍNIMO

if ($precoMinimo !== null) {

    $sql .= " AND p.preco >= :preco_minimo";

    $parametros[':preco_minimo'] = $precoMinimo;
}

// FILTRO POR PREÇO MÁXIMO

if ($precoMaximo !== null) {

    $sql .= " AND p.preco <= :preco_maximo";

    $parametros[':preco_maximo'] = $precoMaximo;
}

// ORDENAÇÃO

$sql .= " ORDER BY " . $ordenacaoSQL;

// EXECUTAR CONSULTA

$consulta = $pdo->prepare($sql);

$consulta->execute($parametros);

$produtos = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NEXUS TECH | Tecnologia e inovação</title>

    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- CSS DO PROJETO -->

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <!-- ======================================
         MENU PRINCIPAL
    ======================================= -->

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
                        <a class="nav-link" href="#categorias">
                            Categorias
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#produtos">
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


    <!-- ======================================
         BANNER PRINCIPAL
    ======================================= -->

    <section class="hero">

        <div class="container">

            <div class="row align-items-center">

                <div class="col-lg-7">

                    <h1>
                        Seu próximo setup começa
                        <span>aqui.</span>
                    </h1>

                    <p>
                        Descubra produtos tecnológicos,
                        periféricos e equipamentos para
                        elevar sua experiência.
                    </p>

                    <a href="#categorias" class="btn-nexus">
                        Explorar produtos
                    </a>

                </div>

                <div class="col-lg-5 mt-5 mt-lg-0">

                    <div class="hero-card">

                        <div class="hero-icon">
                            🎧
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- ======================================
         CATEGORIAS
    ======================================= -->

    <section class="container mt-5" id="categorias">

        <h2 class="section-title">
            Explore nossas categorias
        </h2>

        <div class="row g-4">

            <div class="col-6 col-md-3">
                <div class="category-card">
                    <div class="icon">⌨️</div>
                    <h3>Teclados</h3>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="category-card">
                    <div class="icon">🖱️</div>
                    <h3>Mouses</h3>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="category-card">
                    <div class="icon">🎧</div>
                    <h3>Headsets</h3>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="category-card">
                    <div class="icon">🖥️</div>
                    <h3>Monitores</h3>
                </div>
            </div>

        </div>

    </section>


    <!-- ======================================
         CATÁLOGO DE PRODUTOS
    ======================================= -->

    <section class="container mt-5 pt-5" id="produtos">

        <h2 class="section-title">
            Produtos em destaque
        </h2>


        <!-- FILTROS -->
<!-- BOTÃO PARA ABRIR OS FILTROS -->

<div class="mb-4">

    <button
        type="button"
        class="btn-filtro"
        onclick="abrirFiltros()"
    >
        <i class="fas fa-sliders-h"></i>
        Filtrar
    </button>

</div>


<!-- MODAL DE FILTROS -->

<div class="modal-filtro" id="modalFiltro">

    <div class="conteudo-filtro">

        <!-- CABEÇALHO -->

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


        <!-- FORMULÁRIO -->

        <form method="GET" action="index.php#produtos">

            <div class="opcoes-filtro">

                <!-- CATEGORIA -->

                <div class="campo-filtro">

                    <label for="categoria">Categoria</label>

                    <select name="categoria" id="categoria">

                        <option value="">Todas as categorias</option>

                        <?php foreach ($categorias as $categoria): ?>

                            <option
                                value="<?= (int) $categoria['id'] ?>"
                                <?= $categoriaSelecionada == $categoria['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- PREÇO MÍNIMO -->

                <div class="campo-filtro">

                    <label for="preco_minimo">Preço mínimo</label>

                    <input
                        type="number"
                        name="preco_minimo"
                        id="preco_minimo"
                        placeholder="R$ 0,00"
                        min="0"
                        step="0.01"
                        value="<?= $precoMinimo !== null ? htmlspecialchars((string) $precoMinimo) : '' ?>"
                    >

                </div>


                <!-- PREÇO MÁXIMO -->

                <div class="campo-filtro">

                    <label for="preco_maximo">Preço máximo</label>

                    <input
                        type="number"
                        name="preco_maximo"
                        id="preco_maximo"
                        placeholder="R$ 0,00"
                        min="0"
                        step="0.01"
                        value="<?= $precoMaximo !== null ? htmlspecialchars((string) $precoMaximo) : '' ?>"
                    >

                </div>


                <!-- ORDENAÇÃO -->

                <div class="campo-filtro">

                    <label for="ordem">Ordenar por</label>

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


            <!-- BOTÕES -->

            <div class="acoes-filtro">

                <a href="index.php#produtos" class="btn-limpar-filtro">
                    Limpar filtros
                </a>

                <button type="submit" class="btn-aplicar-filtro">
                    Aplicar filtros
                </button>

            </div>

        </form>

    </div>

</div>
        <!-- RESULTADOS -->

        <div class="row g-4">

            <?php if (!empty($produtos)): ?>

                <?php foreach ($produtos as $produto): ?>

                    <div class="col-12 col-sm-6 col-lg-3">

                        <div class="product-card">

                            <!-- IMAGEM -->

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


                            <!-- INFORMAÇÕES -->

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


    <!-- ======================================
         RODAPÉ
    ======================================= -->

    <footer>

        <p>
            © 2026 NEXUS TECH.
            Todos os direitos reservados.
        </p>

        <p>
            Tecnologia, performance e inovação.
        </p>

    </footer>


    <!-- BOOTSTRAP JS -->
    <script>

// ABRIR MODAL

function abrirFiltros() {

    const modal = document.getElementById('modalFiltro');

    modal.classList.add('ativo');

}


// FECHAR MODAL

function fecharFiltros() {

    const modal = document.getElementById('modalFiltro');

    modal.classList.remove('ativo');

}


// FECHAR AO CLICAR FORA DA JANELA

document.getElementById('modalFiltro').addEventListener('click', function(event) {

    if (event.target === this) {

        fecharFiltros();

    }

});

</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
