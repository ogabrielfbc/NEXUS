
<?php

// 1. Prepara o token e as mensagens

if (empty($_SESSION['token_carrinho'])) {
    $_SESSION['token_carrinho'] = bin2hex(random_bytes(32));
}

$mensagemLoja = $_SESSION['mensagem_loja'] ?? '';
$produtoMensagemId = $_SESSION['produto_mensagem_id'] ?? null;

unset($_SESSION['mensagem_loja']);
unset($_SESSION['produto_mensagem_id']);


// 2. Busca os produtos em destaque

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


// 3. Verifica se a mensagem pertence a algum produto em destaque

$produtoEncontrado = false;

if ($produtoMensagemId !== null) {

    foreach ($produtosDestaque as $produto) {

        if ((int) $produto['id'] === (int) $produtoMensagemId) {
            $produtoEncontrado = true;
            break;
        }

    }

}

$mostrarMensagemGeral = (
    !empty($mensagemLoja) &&
    ($produtoMensagemId === null || !$produtoEncontrado)
);

$mensagemExibida = false;

?>

<!-- 4. Estilo da mensagem -->

<style>

    .mensagem-adicionado {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-top: 13px;
        padding: 11px 13px;
        border: 1px solid rgba(74, 222, 128, .25);
        border-radius: 10px;
        background: rgba(34, 197, 94, .09);
        color: #86efac;
        font-size: 13px;
        font-weight: 600;
        animation: aparecerMensagem .3s ease;
        transition: opacity .4s ease, transform .4s ease;
    }

    .mensagem-adicionado .mensagem-icone {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        flex-shrink: 0;
        border-radius: 50%;
        background: rgba(74, 222, 128, .15);
        font-size: 13px;
    }

    .mensagem-adicionado.ocultando {
        opacity: 0;
        transform: translateY(-7px);
    }

    @keyframes aparecerMensagem {

        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }

    }

</style>


<!-- 5. Produtos em destaque -->

<section class="container mt-5 pt-5" id="produtos">

    <!-- Mensagem geral de erro -->

    <?php if ($mostrarMensagemGeral): ?>

        <div class="alert alert-warning mb-4">
            <?= htmlspecialchars($mensagemLoja, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>


    <!-- Cabeçalho -->

    <div class="destaques-topo">

        <div>

            <h2 class="section-title">
                Produtos em destaque
            </h2>

            <p>
                Confira alguns dos produtos selecionados pela NEXUS.
            </p>

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


    <!-- 6. Lista de produtos -->

    <?php if (!empty($produtosDestaque)): ?>

        <div class="destaques-janela" id="destaquesJanela">

            <div class="destaques-trilha" id="destaquesTrilha">

                <?php

                $listaDestaques = array_merge(
                    $produtosDestaque,
                    $produtosDestaque
                );

                foreach ($listaDestaques as $indice => $produto):

                ?>

                    <div class="destaque-item">

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


                                <!-- Botões do produto -->

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


                                <!-- Mensagem de confirmação -->

                                <?php if (
                                    !$mensagemExibida &&
                                    !empty($mensagemLoja) &&
                                    $produtoMensagemId !== null &&
                                    (int) $produtoMensagemId === (int) $produto['id'] &&
                                    $indice < count($produtosDestaque)
                                ): ?>

                                    <div class="mensagem-adicionado" id="mensagemAdicionado">

                                        <span class="mensagem-icone">
                                            ✓
                                        </span>

                                        <span>
                                            <?= htmlspecialchars($mensagemLoja, ENT_QUOTES, 'UTF-8') ?>
                                        </span>

                                    </div>

                                    <?php $mensagemExibida = true; ?>

                                <?php endif; ?>

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


    <!-- 7. Acesso ao catálogo completo -->

    <div class="text-center mt-5">

        <a href="produtos.php" class="btn-nexus">
            Ver todos os produtos
        </a>

    </div>

</section>


<!-- 8. Carrossel -->

<script>

(function () {

    const trilha = document.getElementById('destaquesTrilha');

    const anterior = document.getElementById('destaqueAnterior');

    const proximo = document.getElementById('destaqueProximo');


    if (!trilha || trilha.children.length < 2) {
        return;
    }


    // 8.1. Configura a movimentação

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


    // 8.2. Atualiza a posição dos produtos

    function atualizarPosicao() {

        while (posicao <= -limite) {
            posicao += limite;
        }

        trilha.style.transform = `translate3d(${posicao}px, 0, 0)`;

    }


    // 8.3. Anima o carrossel

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


    // 8.4. Pausa ao passar o mouse

    trilha.addEventListener('mouseenter', function () {
        pausado = true;
    });

    trilha.addEventListener('mouseleave', function () {
        pausado = false;
    });


    // 8.5. Botões de navegação

    proximo?.addEventListener('click', function () {

        posicao -= 300;

        atualizarPosicao();

    });

    anterior?.addEventListener('click', function () {

        posicao += 300;

        if (posicao > 0) {
            posicao = 0;
        }

        atualizarPosicao();

    });


    anterior?.addEventListener('mouseenter', function () {
        pausado = true;
    });

    proximo?.addEventListener('mouseenter', function () {
        pausado = true;
    });


    requestAnimationFrame(animar);

})();


// 9. Faz a mensagem desaparecer automaticamente

const mensagemAdicionado = document.getElementById('mensagemAdicionado');

if (mensagemAdicionado) {

    setTimeout(function () {

        mensagemAdicionado.classList.add('ocultando');

        setTimeout(function () {
            mensagemAdicionado.remove();
        }, 450);

    }, 3500);

}

</script>
