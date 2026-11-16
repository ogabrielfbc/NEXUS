
<?php

$pdo = require __DIR__ . '/config/database.php';

/* BUSCAR PRODUTOS CADASTRADOS */
$sql = "SELECT
            id,
            nome,
            descricao,
            preco,
            estoque,
            imagem
        FROM produtos
        ORDER BY id DESC";

$consulta = $pdo->query($sql);

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

    <!-- MENU -->
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
                        <a class="nav-link" href="index.php">Início</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#categorias">Categorias</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#produtos">Produtos</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Minha conta</a>
                    </li>

                </ul>

            </div>

        </div>

    </nav>


    <!-- BANNER PRINCIPAL -->
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


    <!-- CATEGORIAS -->
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


    <!-- PRODUTOS -->
    <section class="container mt-5 pt-5" id="produtos">

        <h2 class="section-title">
            Produtos em destaque
        </h2>

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
                        Nenhum produto cadastrado no momento.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- RODAPÉ -->
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DETECÇÃO AUTOMÁTICA -->
<!--<script src="/nexus-tech/assets/js/detectar-cor.js" defer></script>-->

</body>
</html>
