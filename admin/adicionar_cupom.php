
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

$erro = '';

if (empty($_SESSION['token_adicionar_cupom'])) {
    $_SESSION['token_adicionar_cupom'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token'] ?? '';

    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $tipo = $_POST['tipo_desconto'] ?? '';
    $valor = str_replace(',', '.', trim($_POST['valor_desconto'] ?? ''));
    $inicio = $_POST['validade_inicio'] ?? '';
    $fim = $_POST['validade_fim'] ?? '';
    $limite = trim($_POST['limite_utilizacoes'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    try {

        if (!hash_equals($_SESSION['token_adicionar_cupom'], $token)) {
            throw new RuntimeException('Token de segurança inválido.');
        }

        if ($codigo === '' || !preg_match('/^[A-Z0-9_-]{3,50}$/', $codigo)) {
            throw new RuntimeException('O código deve ter entre 3 e 50 caracteres e conter apenas letras, números, hífen ou underline.');
        }

        if (!in_array($tipo, ['porcentagem', 'fixo'], true)) {
            throw new RuntimeException('Selecione um tipo de desconto válido.');
        }

        if (!is_numeric($valor) || (float) $valor <= 0) {
            throw new RuntimeException('Informe um valor de desconto válido.');
        }

        if ($tipo === 'porcentagem' && (float) $valor > 100) {
            throw new RuntimeException('O desconto percentual não pode ultrapassar 100%.');
        }

        if (!is_numeric($valor) || !preg_match('/^\d+(\.\d{1,2})?$/', $valor)) {
            throw new RuntimeException('O desconto deve ter no máximo duas casas decimais.');
        }

        $dataInicio = DateTime::createFromFormat('Y-m-d\TH:i', $inicio);
        $dataFim = DateTime::createFromFormat('Y-m-d\TH:i', $fim);

        if (!$dataInicio || !$dataFim) {
            throw new RuntimeException('Informe datas de validade válidas.');
        }

        if ($dataFim <= $dataInicio) {
            throw new RuntimeException('A data final deve ser posterior à data inicial.');
        }

        if ($limite !== '' && (!ctype_digit($limite) || (int) $limite < 1)) {
            throw new RuntimeException('O limite deve ser um número inteiro maior que zero.');
        }

        $limiteBanco = $limite === '' ? null : (int) $limite;

        $sql = "INSERT INTO cupons (
                    codigo,
                    tipo_desconto,
                    valor_desconto,
                    validade_inicio,
                    validade_fim,
                    limite_utilizacoes,
                    ativo
                ) VALUES (
                    :codigo,
                    :tipo,
                    :valor,
                    :inicio,
                    :fim,
                    :limite,
                    :ativo
                )";

        $consulta = $pdo->prepare($sql);

        $consulta->execute([
            ':codigo' => $codigo,
            ':tipo' => $tipo,
            ':valor' => $valor,
            ':inicio' => $dataInicio->format('Y-m-d H:i:s'),
            ':fim' => $dataFim->format('Y-m-d H:i:s'),
            ':limite' => $limiteBanco,
            ':ativo' => $ativo
        ]);

        unset($_SESSION['token_adicionar_cupom']);

        $_SESSION['mensagem_cupons'] = 'Cupom cadastrado com sucesso!';

        header('Location: cupons.php');
        exit;

    } catch (PDOException $e) {

        if ($e->getCode() === '23000') {
            $erro = 'Esse código de cupom já está cadastrado.';
        } else {
            $erro = 'Não foi possível cadastrar o cupom.';
        }

    } catch (RuntimeException $e) {

        $erro = $e->getMessage();

    }

}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Adicionar cupom | NEXUS</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #080d18;
            color: white;
        }

        .menu-lateral {
            position: fixed;
            width: 250px;
            height: 100vh;
            background: #0d1626;
            border-right: 1px solid #20314a;
            padding: 30px 20px;
        }

        .logo {
            color: #168bff;
            font-size: 30px;
            font-weight: bold;
            letter-spacing: 4px;
            margin-bottom: 45px;
        }

        .menu-lateral a {
            display: block;
            padding: 15px;
            margin-bottom: 8px;
            color: #a8b6c9;
            text-decoration: none;
            border-radius: 8px;
        }

        .menu-lateral a:hover,
        .menu-lateral a.ativo {
            background: #142d4a;
            color: #168bff;
        }

        .conteudo {
            margin-left: 250px;
            padding: 35px;
        }

        .cabecalho {
            margin-bottom: 30px;
        }

        .cabecalho p {
            color: #8998ad;
            margin-top: 8px;
        }

        .formulario {
            max-width: 750px;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 12px;
            padding: 30px;
        }

        .grupo {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            color: #a8b6c9;
            font-size: 14px;
        }

        input,
        select {
            width: 100%;
            padding: 13px;
            background: #080d18;
            border: 1px solid #293b55;
            border-radius: 8px;
            color: white;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #168bff;
        }

        input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            accent-color: #087bea;
        }

        .linha {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .botao {
            display: inline-block;
            padding: 13px 20px;
            border-radius: 8px;
            background: #087bea;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .botao:hover {
            background: #0064c7;
        }

        .botao-voltar {
            background: #243651;
            margin-right: 10px;
        }

        .botao-voltar:hover {
            background: #304765;
        }

        .erro {
            background: #351d28;
            border: 1px solid #703344;
            color: #ff9999;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .ajuda {
            color: #8998ad;
            font-size: 12px;
            margin-top: 7px;
        }

        .ativo-campo {
            display: flex;
            align-items: center;
            color: #a8b6c9;
        }

        @media (max-width: 700px) {

            .menu-lateral {
                position: relative;
                width: 100%;
                height: auto;
            }

            .logo {
                margin-bottom: 20px;
            }

            .conteudo {
                margin-left: 0;
                padding: 20px;
            }

            .formulario {
                padding: 20px;
            }

            .linha {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

    <aside class="menu-lateral">

        <div class="logo">NEXUS</div>

        <a href="painel.php">Visão geral</a>
        <a href="produtos.php">Produtos</a>
        <a href="pedidos.php">Pedidos</a>
        <a href="clientes.php">Clientes</a>
        <a href="cupons.php" class="ativo">Cupons</a>
        <a href="../index.php">Voltar para a loja</a>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <h1>Novo cupom</h1>
            <p>Cadastre uma nova promoção para seus clientes.</p>

        </header>

        <form method="POST" class="formulario">

            <?php if ($erro !== ''): ?>

                <div class="erro">
                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>

            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($_SESSION['token_adicionar_cupom'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <div class="grupo">

                <label for="codigo">Código do cupom</label>

                <input
                    type="text"
                    id="codigo"
                    name="codigo"
                    placeholder="Ex: NEXUS10"
                    maxlength="50"
                    value="<?= htmlspecialchars($_POST['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

                <p class="ajuda">Use letras, números, hífen ou underline.</p>

            </div>

            <div class="linha">

                <div class="grupo">

                    <label for="tipo_desconto">Tipo de desconto</label>

                    <select id="tipo_desconto" name="tipo_desconto" required>

                        <option value="porcentagem">Porcentagem (%)</option>
                        <option value="fixo">Valor fixo (R$)</option>

                    </select>

                </div>

                <div class="grupo">

                    <label for="valor_desconto">Valor do desconto</label>

                    <input
                        type="number"
                        id="valor_desconto"
                        name="valor_desconto"
                        min="0.01"
                        step="0.01"
                        placeholder="Ex: 10"
                        value="<?= htmlspecialchars($_POST['valor_desconto'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

            </div>

            <div class="linha">

                <div class="grupo">

                    <label for="validade_inicio">Início da validade</label>

                    <input
                        type="datetime-local"
                        id="validade_inicio"
                        name="validade_inicio"
                        value="<?= htmlspecialchars($_POST['validade_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

                <div class="grupo">

                    <label for="validade_fim">Fim da validade</label>

                    <input
                        type="datetime-local"
                        id="validade_fim"
                        name="validade_fim"
                        value="<?= htmlspecialchars($_POST['validade_fim'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

            </div>

            <div class="grupo">

                <label for="limite_utilizacoes">Limite de utilizações</label>

                <input
                    type="number"
                    id="limite_utilizacoes"
                    name="limite_utilizacoes"
                    min="1"
                    placeholder="Deixe vazio para ilimitado"
                    value="<?= htmlspecialchars($_POST['limite_utilizacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >

                <p class="ajuda">Define quantas vezes o cupom poderá ser utilizado no total.</p>

            </div>

            <div class="grupo">

                <label class="ativo-campo">

                    <input
                        type="checkbox"
                        name="ativo"
                        value="1"
                        <?= !isset($_POST['ativo']) && $_SERVER['REQUEST_METHOD'] === 'POST' ? '' : 'checked' ?>
                    >

                    Cupom ativo

                </label>

            </div>

            <a href="cupons.php" class="botao botao-voltar">
                Voltar
            </a>

            <button type="submit" class="botao">
                Cadastrar cupom
            </button>

        </form>

    </main>

</body>

</html>
