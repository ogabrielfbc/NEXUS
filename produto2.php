
<?php
if (empty($_SESSION['token_carrinho'])) {
    $_SESSION['token_carrinho'] = bin2hex(random_bytes(32));
}

$sqlDestaques = "SELECT
                    p.id,
                    p.nome,
                    p.descricao,
                    p.preco,
                    p.imagem,
                    c.nome AS categoria_nome
                 FROM produtos p
                 LEFT JOIN categorias c
                    ON p.categoria_id = c.id
                 WHERE p.ativo = 1
                 AND p.destaque = 1
                 ORDER BY p.criado_em DESC
                 LIMIT 8";

$produtosDestaque = $pdo->query($sqlDestaques)->fetchAll();

?>

<section class="container mt-5 pt-5" id="produtos">

    <!-- Cabeçalho -->

    <div class="destaques-topo">

        <div>
            <h2 class="section-title">Produtos em destaque</h2>
            <p>Confira alguns dos produtos selecionados pela NEXUS.</p>
        </div>

        <?php if (!empty($produtosDestaque)): ?>

            <div class="destaques-controles">

                <button
                    type="button"
                    class="seta-destaque"
                    id="destaqueAnterior"
                    aria-label="Produtos anteriores"
                >
                    &#8592;
                </button>

                <button
                    type="button"
                    class="seta-destaque"
                    id="destaqueProximo"
                    aria-label="Próximos produtos"
                >
                    &#8594;
                </button>

            </div>

        <?php endif; ?>

    </div>

    <!-- Produtos -->

    <?php if (!empty($produtosDestaque)): ?>

        <div class="destaques-janela" id="destaquesJanela">

            <div class="destaques-trilha" id="destaquesTrilha">

                <?php
                $listaDestaques = array_merge(
                    $produtosDestaque,
                    $produtosDestaque
                );

                foreach ($listaDestaques as $produto):
                ?>

                    <div class="destaque-item">

                        <div class="product-card">

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

                               <div class="botoes-produto">

    <form action="adicionar_carrinho.php" method="POST">

        <input
            type="hidden"
            name="id"
            value="<?= (int) $produto['id'] ?>"
        >

        <input
            type="hidden"
            name="token_carrinho"
            value="<?= htmlspecialchars($_SESSION['token_carrinho'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <button
            type="submit"
            name="acao"
            value="adicionar"
            class="btn-product"
        >
            Adicionar ao carrinho
        </button>


    </form>

    <a
        href="produto.php?id=<?= (int) $produto['id'] ?>"
        class="btn-product"
    >
        Ver detalhes
    </a>

</div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    <?php else: ?>

        <p class="text-center">
            Nenhum produto em destaque no momento.
        </p>

    <?php endif; ?>

    <!-- Catálogo completo -->

    <div class="text-center mt-5">

        <a href="produtos.php" class="btn-nexus">
            Ver todos os produtos
        </a>

    </div>

</section>

<!-- Carrossel -->

<script>
(function () {
    const trilha = document.getElementById('destaquesTrilha');
    const anterior = document.getElementById('destaqueAnterior');
    const proximo = document.getElementById('destaqueProximo');

    if (!trilha || trilha.children.length < 2) {
        return;
    }

    const quantidade = trilha.children.length / 2;
    const primeiroDuplicado = trilha.children[quantidade];

    let posicao = 0;
    const velocidade = 45;

    let pausado = false;
    let ultimoTempo = 0;

    const limite = primeiroDuplicado.offsetLeft;

    if (limite <= 0) {
        return;
    }

    function atualizarPosicao() {
        while (posicao <= -limite) {
            posicao += limite;
        }

        trilha.style.transform = `translate3d(${posicao}px, 0, 0)`;
    }

    function animar(tempo) {
        if (!ultimoTempo) {
            ultimoTempo = tempo;
        }

        const delta = Math.min((tempo - ultimoTempo) / 1000, 0.05);

        ultimoTempo = tempo;

        if (!pausado) {
            posicao -= velocidade * delta;
            atualizarPosicao();
        }

        requestAnimationFrame(animar);
    }

    trilha.addEventListener('mouseenter', function() {
        pausado = true;
    });

    trilha.addEventListener('mouseleave', function() {
        pausado = false;
    });

    proximo?.addEventListener('click', function() {
        posicao -= 300;
        atualizarPosicao();
    });

    anterior?.addEventListener('click', function() {
        posicao += 300;

        if (posicao > 0) {
            posicao = 0;
        }

        atualizarPosicao();
    });

    requestAnimationFrame(animar);
})();
</script>
