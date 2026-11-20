
<?php

require_once __DIR__ . '/cliente/proteger_cliente.php';

$pdo = require __DIR__ . '/config/database.php';

$usuarioId = (int) $_SESSION['usuario_id'];

// Garante que o carrinho exista.
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Garante que exista um token de segurança.
if (empty($_SESSION['token_finalizar_compra'])) {
    $_SESSION['token_finalizar_compra'] = bin2hex(random_bytes(32));
}

// Só aceita compras enviadas por POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrinho.php');
    exit;
}

// Confere o token de segurança.
$token = $_POST['token_finalizar_compra'] ?? '';

if (
    !is_string($token) ||
    !hash_equals($_SESSION['token_finalizar_compra'], $token)
) {
    $_SESSION['mensagem_carrinho'] = 'Solicitação inválida. Tente novamente.';
    header('Location: carrinho.php');
    exit;
}
// Renova o token para impedir a reutilização do mesmo envio.
$_SESSION['token_finalizar_compra'] = bin2hex(random_bytes(32));
// Confere se existem produtos no carrinho.
if (empty($_SESSION['carrinho'])) {
    $_SESSION['mensagem_carrinho'] = 'Seu carrinho está vazio.';
    header('Location: carrinho.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Converte valores monetários para centavos.
|--------------------------------------------------------------------------
*/

function converterParaCentavos($valor)
{
    $valor = (string) $valor;

    if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $valor, $partes)) {
        throw new RuntimeException('Foi encontrado um preço inválido.');
    }

    $inteiro = (int) $partes[1];

    $centavos = isset($partes[2])
        ? (int) str_pad($partes[2], 2, '0')
        : 0;

    return ($inteiro * 100) + $centavos;
}

/*
|--------------------------------------------------------------------------
| Converte centavos para o formato decimal do banco.
|--------------------------------------------------------------------------
*/

function converterParaDecimal($centavos)
{
    $inteiro = intdiv($centavos, 100);
    $decimal = $centavos % 100;

    return $inteiro . '.' . str_pad(
        (string) $decimal,
        2,
        '0',
        STR_PAD_LEFT
    );
}

try {

    // Organiza os IDs para bloquear os produtos em ordem.
    $carrinho = $_SESSION['carrinho'];

    $idsProdutos = array_keys($carrinho);

    sort($idsProdutos, SORT_NUMERIC);

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | 1. Bloqueia e consulta a carteira do cliente.
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT id, saldo
            FROM carteira_coins
            WHERE usuario_id = :usuario_id
            LIMIT 1
            FOR UPDATE";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':usuario_id' => $usuarioId
    ]);

    $carteira = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$carteira) {
        throw new RuntimeException('Sua carteira não foi encontrada.');
    }

    $carteiraId = (int) $carteira['id'];

    $saldoAnterior = converterParaCentavos($carteira['saldo']);

    /*
    |--------------------------------------------------------------------------
    | 2. Consulta produtos e verifica o estoque.
    |--------------------------------------------------------------------------
    */

    $produtosCompra = [];
    $totalCentavos = 0;

    foreach ($idsProdutos as $idProduto) {

        $idProduto = filter_var(
            $idProduto,
            FILTER_VALIDATE_INT
        );

        $quantidade = filter_var(
            $carrinho[$idProduto] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            !$idProduto ||
            !$quantidade ||
            $quantidade < 1
        ) {
            throw new RuntimeException('Existe uma quantidade inválida no carrinho.');
        }

        $sql = "SELECT id, nome, preco, estoque
                FROM produtos
                WHERE id = :id
                AND ativo = 1
                LIMIT 1
                FOR UPDATE";

        $consulta = $pdo->prepare($sql);

        $consulta->execute([
            ':id' => $idProduto
        ]);

        $produto = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            throw new RuntimeException(
                'Um dos produtos não está mais disponível.'
            );
        }

        if ($quantidade > (int) $produto['estoque']) {
            throw new RuntimeException(
                'Estoque insuficiente para o produto: ' . $produto['nome']
            );
        }

        $precoCentavos = converterParaCentavos($produto['preco']);

        $subtotalCentavos = $precoCentavos * $quantidade;

        $totalCentavos += $subtotalCentavos;

        $produtosCompra[] = [
            'id' => (int) $produto['id'],
            'nome' => $produto['nome'],
            'preco' => $precoCentavos,
            'quantidade' => $quantidade
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Valida e calcula o desconto do cupom.
    |--------------------------------------------------------------------------
    */

    $cupomId = null;
    $descontoCentavos = 0;

    if (!empty($_SESSION['cupom_aplicado']['id'])) {

        $cupomId = (int) $_SESSION['cupom_aplicado']['id'];

        // Bloqueia o cupom durante a compra.
        $sql = "SELECT
                    id,
                    codigo,
                    tipo_desconto,
                    valor_desconto,
                    limite_utilizacoes,
                    utilizacoes,
                    ativo
                FROM cupons
                WHERE id = :id
                AND ativo = 1
                AND NOW() BETWEEN validade_inicio AND validade_fim
                LIMIT 1
                FOR UPDATE";

        $consulta = $pdo->prepare($sql);

        $consulta->execute([
            ':id' => $cupomId
        ]);

        $cupom = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$cupom) {
            throw new RuntimeException(
                'O cupom não existe mais, está desativado ou expirou.'
            );
        }

        if (
            $cupom['limite_utilizacoes'] !== null &&
            (int) $cupom['utilizacoes'] >= (int) $cupom['limite_utilizacoes']
        ) {
            throw new RuntimeException(
                'Este cupom atingiu o limite de utilizações.'
            );
        }

        // Calcula o desconto em centavos.
        if ($cupom['tipo_desconto'] === 'porcentagem') {

            $descontoCentavos = (int) round(
                $totalCentavos * ((float) $cupom['valor_desconto'] / 100)
            );

        } else {

            $descontoCentavos = converterParaCentavos(
                $cupom['valor_desconto']
            );
        }

        // Impede descontos negativos ou maiores que a compra.
        $descontoCentavos = min(
            max(0, $descontoCentavos),
            $totalCentavos
        );

        // Aplica o desconto ao total.
        $totalCentavos -= $descontoCentavos;
    }


    /*
    |--------------------------------------------------------------------------
    | 3. Confere o saldo disponível.
    |--------------------------------------------------------------------------
    */

    if ($totalCentavos <= 0) {
        throw new RuntimeException('O valor da compra é inválido.');
    }

    if ($saldoAnterior < $totalCentavos) {
        throw new RuntimeException(
            'Saldo insuficiente! Adicione NEXUS Coins à sua carteira.'
        );
    }

    $saldoNovo = $saldoAnterior - $totalCentavos;

    /*
    |--------------------------------------------------------------------------
    | 4. Registra o pedido.
    |--------------------------------------------------------------------------
    */

    $sql = "INSERT INTO pedidos (
                usuario_id,
                forma_pagamento,
                status,
                total
            ) VALUES (
                :usuario_id,
                :forma_pagamento,
                :status,
                :total
            )";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':usuario_id' => $usuarioId,
        ':forma_pagamento' => 'coins',
        ':status' => 'confirmado',
        ':total' => converterParaDecimal($totalCentavos)
    ]);

    $pedidoId = (int) $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Registra a utilização do cupom.
    |--------------------------------------------------------------------------
    */

    if ($cupomId !== null) {

        // Incrementa o contador de utilizações.
        $sql = "UPDATE cupons
                SET utilizacoes = utilizacoes + 1
                WHERE id = :id
                AND (
                    limite_utilizacoes IS NULL
                    OR utilizacoes < limite_utilizacoes
                )";

        $consulta = $pdo->prepare($sql);

        $consulta->execute([
            ':id' => $cupomId
        ]);

        if ($consulta->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível utilizar este cupom.'
            );
        }

        // Guarda o desconto utilizado no pedido.
        $sql = "INSERT INTO uso_cupons (
                    cupom_id,
                    usuario_id,
                    pedido_id,
                    valor_desconto
                ) VALUES (
                    :cupom_id,
                    :usuario_id,
                    :pedido_id,
                    :valor_desconto
                )";

        $consulta = $pdo->prepare($sql);

        $consulta->execute([
            ':cupom_id' => $cupomId,
            ':usuario_id' => $usuarioId,
            ':pedido_id' => $pedidoId,
            ':valor_desconto' => converterParaDecimal($descontoCentavos)
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | 5. Registra os produtos e atualiza o estoque.
    |--------------------------------------------------------------------------
    */

    foreach ($produtosCompra as $produto) {

        // Registra o item comprado.
        $sql = "INSERT INTO itens_pedido (
                    pedido_id,
                    produto_id,
                    nome_produto,
                    quantidade,
                    preco_unitario
                ) VALUES (
                    :pedido_id,
                    :produto_id,
                    :nome_produto,
                    :quantidade,
                    :preco_unitario
                )";

        $consulta = $pdo->prepare($sql);

        $consulta->execute([
            ':pedido_id' => $pedidoId,
            ':produto_id' => $produto['id'],
            ':nome_produto' => $produto['nome'],
            ':quantidade' => $produto['quantidade'],
            ':preco_unitario' => converterParaDecimal($produto['preco'])
        ]);

        // Retira a quantidade comprada do estoque.
        $sql = "UPDATE produtos
                SET estoque = estoque - :quantidade
                WHERE id = :id
                AND ativo = 1
                AND estoque >= :estoque_minimo";

        $consulta = $pdo->prepare($sql);

        $consulta->execute([
            ':quantidade' => $produto['quantidade'],
            ':id' => $produto['id'],
            ':estoque_minimo' => $produto['quantidade']
        ]);

        if ($consulta->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o estoque de um produto.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Atualiza o saldo da carteira.
    |--------------------------------------------------------------------------
    */

    $sql = "UPDATE carteira_coins
            SET saldo = :saldo_novo
            WHERE id = :carteira_id";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':saldo_novo' => converterParaDecimal($saldoNovo),
        ':carteira_id' => $carteiraId
    ]);

    /*
    |--------------------------------------------------------------------------
    | 7. Registra a movimentação financeira.
    |--------------------------------------------------------------------------
    */

    $sql = "INSERT INTO movimentacoes_coins (
                carteira_id,
                tipo,
                quantidade,
                saldo_anterior,
                saldo_novo,
                descricao
            ) VALUES (
                :carteira_id,
                :tipo,
                :quantidade,
                :saldo_anterior,
                :saldo_novo,
                :descricao
            )";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':carteira_id' => $carteiraId,
        ':tipo' => 'debito',
        ':quantidade' => converterParaDecimal($totalCentavos),
        ':saldo_anterior' => converterParaDecimal($saldoAnterior),
        ':saldo_novo' => converterParaDecimal($saldoNovo),
        ':descricao' => 'Compra realizada - Pedido #' . $pedidoId
    ]);

    /*
    |--------------------------------------------------------------------------
    | 8. Confirma todas as operações.
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    // Só limpa o carrinho depois que a compra foi confirmada.
    $_SESSION['carrinho'] = [];
    unset($_SESSION['cupom_aplicado']);


    $_SESSION['mensagem_carrinho'] =
        'Compra realizada com sucesso! Pedido #' . $pedidoId .
        ' - Total: ' . number_format(
            $totalCentavos / 100,
            2,
            ',',
            '.'
        ) . ' NC.';

} catch (RuntimeException $erro) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensagem_carrinho'] = $erro->getMessage();

} catch (Throwable $erro) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensagem_carrinho'] =
        'Não foi possível finalizar sua compra. Tente novamente.';
}

header('Location: carrinho.php');
exit;
