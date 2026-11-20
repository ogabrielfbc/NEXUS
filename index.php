
<?php

session_start();

$pdo = require __DIR__ . '/config/database.php';

$clienteLogado = (
    isset($_SESSION['usuario_id']) &&
    isset($_SESSION['usuario_tipo']) &&
    $_SESSION['usuario_tipo'] === 'cliente'
);

$nomeCliente = $clienteLogado
    ? $_SESSION['usuario_nome']
    : '';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NEXUS TECH | Tecnologia e inovação</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- CSS principal -->
    <link rel="stylesheet" href="style.css">

</head>

<body>

    <!-- MENU -->

    <nav class="navbar navbar-expand-lg navbar-dark home-navbar">

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

                <ul class="navbar-nav ms-auto align-items-lg-center">

                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
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
                            Destaques
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="produtos.php">
                            Todos os produtos
                        </a>
                    </li>

                    <?php if ($clienteLogado): ?>

                        <li class="nav-item">
                            <a class="nav-link home-account" href="cliente/minha_conta.php">
                                Olá, <?= htmlspecialchars($nomeCliente) ?>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="cliente/sair.php">
                                Sair
                            </a>
                        </li>

                    <?php else: ?>

                        <li class="nav-item">
                            <a class="nav-link home-account" href="cliente/login.php">
                                Minha conta
                            </a>
                        </li>

                    <?php endif; ?>

                </ul>

            </div>

        </div>

    </nav>


    <!-- BANNER PRINCIPAL -->

    <section class="home-hero">

        <div class="container">

            <div class="row align-items-center">

                <div class="col-lg-7">

                    <div class="home-hero-content">

                        <span class="home-tag">
                            TECNOLOGIA • PERFORMANCE • INOVAÇÃO
                        </span>

                        <h1>
                            Seu próximo setup começa
                            <span>aqui.</span>
                        </h1>

                        <p>
                            Encontre os equipamentos ideais para transformar
                            sua experiência. Explore nossa seleção de produtos
                            e leve seu setup para outro nível.
                        </p>

                        <div class="home-hero-buttons">

                            <a href="produtos.php" class="home-button">
                                Explorar produtos <span>→</span>
                            </a>

                            <a href="#categorias" class="home-secondary-button">
                                Ver categorias
                            </a>

                        </div>

                    </div>

                </div>

                <div class="col-lg-5">

                    <div class="home-hero-visual">

                        <div class="home-visual-glow"></div>

                        <div class="home-visual-painel">

                            <div class="home-visual-orbita"></div>

                            <div class="home-visual-letra">
                                N
                            </div>

                            <div class="home-visual-detalhe">
                                NEXUS TECH
                            </div>

                        </div>

                        <div class="home-etiqueta-flutuante">

                            <span>✦</span>

                            <div>
                                <strong>TECNOLOGIA</strong>
                                <small>Para ir além.</small>
                            </div>

                        </div>

                        <div class="home-etiqueta-superior">
                            PERFORMANCE • INOVAÇÃO
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- CATEGORIAS -->

    <section class="container home-categories" id="categorias">

        <div class="home-section-heading">

            <div>

                <span class="home-eyebrow">
                    EXPLORE
                </span>

                <h2>Encontre sua categoria</h2>

                <p>
                    Descubra os produtos ideais para o seu setup.
                </p>

            </div>

            <a href="produtos.php" class="home-see-all">
                Ver todos <span>→</span>
            </a>

        </div>

        <div class="row g-3 justify-content-center">

            <div class="col-6 col-md-3">
                <a href="produtos.php?categoria=1" class="category-card">
                    <div class="icon">⌨️</div>
                    <h3>Teclados</h3>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="produtos.php?categoria=2" class="category-card">
                    <div class="icon">🖱️</div>
                    <h3>Mouses</h3>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="produtos.php?categoria=3" class="category-card">
                    <div class="icon">🎧</div>
                    <h3>Headsets</h3>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="produtos.php?categoria=4" class="category-card">
                    <div class="icon">🖥️</div>
                    <h3>Monitores</h3>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="produtos.php?categoria=5" class="category-card">
                    <div class="icon">🎮</div>
                    <h3>Consoles</h3>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="produtos.php?categoria=6" class="category-card">
                    <div class="icon">⚙️</div>
                    <h3>Placas de vídeo</h3>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="produtos.php?categoria=7" class="category-card">
                    <div class="icon">💻</div>
                    <h3>Notebooks</h3>
                </a>
            </div>

        </div>

    </section>


    <!-- PRODUTOS EM DESTAQUE -->

    <?php include __DIR__ . '/produto2.php'; ?>


    <!-- RODAPÉ -->

    <footer class="home-footer">

        <div class="container">

            <div class="home-footer-content">

                <div>

                    <a class="navbar-brand" href="index.php">
                        NEXUS<span>TECH</span>
                    </a>

                    <p>
                        Tecnologia, performance e inovação.
                    </p>

                </div>

                <div class="home-footer-links">

                    <a href="index.php">Início</a>
                    <a href="produtos.php">Produtos</a>
                    <a href="#categorias">Categorias</a>

                </div>

            </div>

            <div class="home-footer-bottom">
                © 2026 NEXUS TECH. Todos os direitos reservados.
            </div>

        </div>

    </footer>


    <!-- BOOTSTRAP JS -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
