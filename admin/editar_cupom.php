
<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: cupons.php');
    exit;
}

$consulta = $pdo->prepare("SELECT * FROM cupons WHERE id = :id LIMIT 1");
$consulta->execute([':id' => $id]);

$cupom = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$cupom) {
    $_SESSION['mensagem_cupons'] = 'Cupom não encontrado.';
    header('Location: cupons.php');
    exit;
}

if (empty($_SESSION['token_editar_cupom'])) {
    $_SESSION['token_editar_cupom'] = bin2hex(random_bytes(32));
}

$erro = '';

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

        if (!hash_equals($_SESSION['token_editar_cupom'], $token)) {
            throw new RuntimeException('Token de segurança inválido.');
        }

        if ($codigo === '' || !preg_match('/^[A-Z0-9_-]{3,50}$/', $codigo)) {
            throw new RuntimeException('Informe um código válido.');
        }

        if (!in_array($tipo, ['porcentagem', 'fixo'], true)) {
            throw new RuntimeException('Tipo de desconto inválido.');
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $valor) || (float) $valor <= 0) {
            throw new RuntimeException('Informe um valor de desconto válido, com até duas casas decimais.');
        }

        if ($tipo === 'porcentagem' && (float) $valor > 100) {
            throw new RuntimeException('O desconto percentual não pode ultrapassar 100%.');
        }

        $dataInicio = DateTime::createFromFormat('Y-m-d\TH:i', $inicio);
        $dataFim = DateTime::createFromFormat('Y-m-d\TH:i', $fim);

        if (!$dataInicio || !$dataFim) {
            throw new RuntimeException('Informe datas válidas.');
        }

        if ($dataFim <= $dataInicio) {
            throw new RuntimeException('A data final deve ser posterior à inicial.');
        }

        if ($limite !== '' && (!ctype_digit($limite) || (int) $limite < 1)) {
            throw new RuntimeException('O limite deve ser um número inteiro maior que zero.');
        }

        $limiteBanco = $limite === '' ? null : (int) $limite;

        if ($limiteBanco !== null && $limiteBanco < (int) $cupom['utilizacoes']) {
            throw new RuntimeException('O limite não pode ser menor que a quantidade de utilizações já realizadas.');
        }

        $sql = "UPDATE cupons SET
                    codigo = :codigo,
                    tipo_desconto = :tipo,
                    valor_desconto = :valor,
                    validade_inicio = :inicio,
                    validade_fim = :fim,
                    limite_utilizacoes = :limite,
                    ativo = :ativo
                WHERE id = :id";

        $consulta = $pdo->prepare($sql);

        $consulta->execute([
            ':codigo' => $codigo,
            ':tipo' => $tipo,
            ':valor' => $valor,
            ':inicio' => $dataInicio->format('Y-m-d H:i:s'),
            ':fim' => $dataFim->format('Y-m-d H:i:s'),
            ':limite' => $limiteBanco,
            ':ativo' => $ativo,
            ':id' => $id
        ]);

        unset($_SESSION['token_editar_cupom']);

        $_SESSION['mensagem_cupons'] = 'Cupom atualizado com sucesso!';

        header('Location: cupons.php');
        exit;

    } catch (PDOException $e) {

        if ($e->getCode() === '23000') {
            $erro = 'Já existe outro cupom com esse código.';
        } else {
            $erro = 'Não foi possível atualizar o cupom.';
        }

    } catch (RuntimeException $e) {

        $erro = $e->getMessage();

    }

}

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar cupom | NEXUS</title>

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

            <h1>Editar cupom</h1>
            <p>Altere as informações e configurações da promoção.</p>

        </header>

        <form method="POST" class="formulario">

            <?php if ($erro !== ''): ?>

                <div class="erro">
                    <?= escapar($erro) ?>
                </div>

            <?php endif; ?>

            <input
                type="hidden"
                name="token"
                value="<?= escapar($_SESSION['token_editar_cupom']) ?>"
            >

            <div class="grupo">

                <label for="codigo">Código do cupom</label>

                <input
                    type="text"
                    id="codigo"
                    name="codigo"
                    maxlength="50"
                    value="<?= escapar($_POST['codigo'] ?? $cupom['codigo']) ?>"
                    required
                >

                <p class="ajuda">Use letras, números, hífen ou underline.</p>

            </div>

            <div class="linha">

                <div class="grupo">

                    <label for="tipo_desconto">Tipo de desconto</label>

                    <select id="tipo_desconto" name="tipo_desconto" required>

                        <option value="porcentagem"
                            <?= ($_POST['tipo_desconto'] ?? $cupom['tipo_desconto']) === 'porcentagem' ? 'selected' : '' ?>>
                            Porcentagem (%)
                        </option>

                        <option value="fixo"
                            <?= ($_POST['tipo_desconto'] ?? $cupom['tipo_desconto']) === 'fixo' ? 'selected' : '' ?>>
                            Valor fixo (R$)
                        </option>

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
                        value="<?= escapar($_POST['valor_desconto'] ?? $cupom['valor_desconto']) ?>"
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
                        value="<?= escapar($_POST['validade_inicio'] ?? date('Y-m-d\TH:i', strtotime($cupom['validade_inicio']))) ?>"
                        required
                    >

                </div>

                <div class="grupo">

                    <label for="validade_fim">Fim da validade</label>

                    <input
                        type="datetime-local"
                        id="validade_fim"
                        name="validade_fim"
                        value="<?= escapar($_POST['validade_fim'] ?? date('Y-m-d\TH:i', strtotime($cupom['validade_fim']))) ?>"
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
                    value="<?= escapar($_POST['limite_utilizacoes'] ?? $cupom['limite_utilizacoes'] ?? '') ?>"
                >

                <p class="ajuda">
                    Utilizações realizadas: <?= (int) $cupom['utilizacoes'] ?>
                </p>

            </div>

            <div class="grupo">

                <label class="ativo-campo">

                    <input
                        type="checkbox"
                        name="ativo"
                        value="1"
                        <?= (
                            $_SERVER['REQUEST_METHOD'] === 'POST'
                                ? isset($_POST['ativo'])
                                : (int) $cupom['ativo'] === 1
                        ) ? 'checked' : '' ?>
                    >

                    Cupom ativo

                </label>

            </div>

            <a href="cupons.php" class="botao botao-voltar">
                Voltar
            </a>

            <button type="submit" class="botao">
                Salvar alterações
            </button>

        </form>

    </main>

</body>

</html>
