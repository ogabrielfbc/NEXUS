
document.addEventListener("DOMContentLoaded", () => {

    function detectarCorFundo(img, container) {

        if (!img.complete || !img.naturalWidth || !container) {
            return;
        }

        try {

            const canvas = document.createElement("canvas");
            const contexto = canvas.getContext("2d", {
                willReadFrequently: true
            });

            if (!contexto) return;

            const largura = img.naturalWidth;
            const altura = img.naturalHeight;

            const tamanho = Math.max(
                1,
                Math.min(40, Math.floor(Math.min(largura, altura) * 0.08))
            );

            canvas.width = tamanho * 4;
            canvas.height = tamanho;

            const cantos = [
                [0, 0],
                [largura - tamanho, 0],
                [0, altura - tamanho],
                [largura - tamanho, altura - tamanho]
            ];

            cantos.forEach(([x, y], indice) => {

                contexto.drawImage(
                    img,
                    x, y,
                    tamanho, tamanho,
                    indice * tamanho, 0,
                    tamanho, tamanho
                );

            });

            const dados = contexto.getImageData(
                0, 0, canvas.width, canvas.height
            ).data;

            const pixels = [];

            for (let i = 0; i < dados.length; i += 4) {

                if (dados[i + 3] < 200) continue;

                pixels.push([
                    dados[i],
                    dados[i + 1],
                    dados[i + 2]
                ]);

            }

            if (!pixels.length) return;

            let vermelho = 0;
            let verde = 0;
            let azul = 0;

            pixels.forEach(([r, g, b]) => {
                vermelho += r;
                verde += g;
                azul += b;
            });

            const quantidade = pixels.length;

            const cor = `rgb(
                ${Math.round(vermelho / quantidade)},
                ${Math.round(verde / quantidade)},
                ${Math.round(azul / quantidade)}
            )`;

            container.style.backgroundColor = cor;

        } catch (erro) {

            console.error("Erro ao detectar cor da imagem:", erro);

        }

    }


    function configurarImagem(img, container) {

        if (!img || !container) return;

        function atualizar() {
            detectarCorFundo(img, container);
        }

        img.addEventListener("load", atualizar);

        if (img.complete && img.naturalWidth > 0) {
            atualizar();
        }

        img.addEventListener("error", () => {
            console.warn("Imagem não encontrada:", img.src);
        });

    }


    // IMAGENS DA PÁGINA INICIAL
    document.querySelectorAll(".product-image img").forEach(img => {

        configurarImagem(
            img,
            img.closest(".product-image")
        );

    });


    // GALERIA DA PÁGINA DO PRODUTO
    document.querySelectorAll(".product-gallery-item img").forEach(img => {

        configurarImagem(
            img,
            img.closest(".product-gallery-item")
        );

    });


    // MINIATURAS DO MODAL
    document.querySelectorAll(".gallery-modal-thumb img").forEach(img => {

        configurarImagem(
            img,
            img.closest(".gallery-modal-thumb")
        );

    });


    // IMAGEM AMPLIADA DO MODAL
    const imagemAmpliada = document.getElementById("imagemAmpliada");

    if (imagemAmpliada) {

        configurarImagem(
            imagemAmpliada,
            imagemAmpliada.closest(".gallery-modal-main")
        );

    }

});
