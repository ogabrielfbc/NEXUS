<?php

require_once __DIR__ . '/proteger.php';

$pdo = require __DIR__ . '/../config/database.php';

// 1. Criar token de segurança
if (empty($_SESSION['token_exclusao'])) {
    $_SESSION['token_exclusao'] = bin2hex(random_bytes(32));
}

// 2. Receber mensagens
$mensagem = $_SESSION['mensagem_produtos'] ?? '';
unset($_SESSION['mensagem_produtos']);

// 3. Receber a pesquisa
$pesquisa = trim($_GET['pesquisa'] ?? '');

// 4. Buscar os produtos
$sql = "SELECT
            p.id,
            p.codigo,
            p.nome,
            p.preco,
            p.estoque,
            p.imagem,
            p.ativo,
            c.nome AS categoria
        FROM produtos p
        LEFT JOIN categorias c
            ON p.categoria_id = c.id";

if ($pesquisa !== '') {
    $sql .= " WHERE p.nome LIKE :pesquisa
              OR p.codigo LIKE :pesquisa";
}

$sql .= " ORDER BY p.id DESC";

$consulta = $pdo->prepare($sql);

if ($pesquisa !== '') {
    $consulta->execute([
        ':pesquisa' => "%$pesquisa%"
    ]);
} else {
    $consulta->execute();
}

$produtos = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Produtos | NEXUS</title>

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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
        }

        .cabecalho p {
            color: #8998ad;
            margin-top: 8px;
        }

        .botao {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 8px;
            background: #087bea;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .botao:hover {
            background: #0064c7;
        }

        .pesquisa {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .pesquisa input {
            width: 100%;
            max-width: 400px;
            padding: 13px;
            border: 1px solid #293b55;
            border-radius: 8px;
            background: #111a2b;
            color: white;
        }

        .tabela-container {
            overflow-x: auto;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            padding: 17px;
            text-align: left;
            border-bottom: 1px solid #243651;
        }

        th {
            color: #8998ad;
            font-size: 13px;
            font-weight: normal;
        }

        td {
            font-size: 14px;
        }

        .imagem-produto {
            width: 55px;
            height: 55px;
            object-fit: contain;
            background: #0b1322;
            border-radius: 8px;
            padding: 5px;
        }

        .ativo {
            color: #42d392;
        }

        .inativo {
            color: #ff7777;
        }

        .vazio {
            text-align: center;
            padding: 35px;
            color: #8998ad;
        }

        .mensagem {
            padding: 14px 18px;
            margin-bottom: 20px;
            background: #111a2b;
            border: 1px solid #243651;
            border-radius: 8px;
            color: #42d392;
        }

        .mensagem.erro {
            color: #ff7777;
        }

        .acoes {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .acoes a {
            color: #168bff;
            text-decoration: none;
        }

        .acoes a:hover {
            text-decoration: underline;
        }

        .botao-excluir {
            background: none;
            border: none;
            color: #ff7777;
            cursor: pointer;
            font-size: 14px;
        }

        .botao-excluir:hover {
            color: #ff4444;
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

            .cabecalho {
                align-items: flex-start;
                flex-direction: column;
            }

            .pesquisa {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <aside class="menu-lateral">

        <div class="logo">NEXUS</div>

        <a href="painel.php">Visão geral</a>
        <a href="produtos.php" class="ativo">Produtos</a>
        <a href="pedidos.php">Pedidos</a>
        <a href="clientes.php">Clientes</a>
        <a href="cupons.php">Cupons</a>
        <a href="avaliacoes.php">Avaliações</a>
        <a href="../index.php">Voltar para a loja</a>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <div>
                <h1>Produtos</h1>
                <p>Gerencie os produtos da sua loja.</p>
            </div>

            <a href="adicionar.php" class="botao">
                + Adicionar produto
            </a>

        </header>

        <?php if ($mensagem !== ''): ?>

            <div class="mensagem <?= strpos($mensagem, 'não') !== false || strpos($mensagem, 'inválida') !== false ? 'erro' : '' ?>">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <form method="GET" class="pesquisa">

            <input
                type="text"
                name="pesquisa"
                placeholder="Pesquisar por nome ou código..."
                value="<?= htmlspecialchars($pesquisa, ENT_QUOTES, 'UTF-8') ?>"
            >

            <button type="submit" class="botao">
                Pesquisar
            </button>

        </form>

        <div class="tabela-container">

            <table>

                <thead>
                    <tr>
                        <th>Imagem</th>
                        <th>Produto</th>
                        <th>Código</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Estoque</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($produtos) > 0): ?>

                        <?php foreach ($produtos as $produto): ?>

                            <tr>

                                <td>
                                    <?php if (!empty($produto['imagem'])): ?>
                                        <img
                                            src="../<?= htmlspecialchars($produto['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                            class="imagem-produto"
                                            alt="Imagem do produto"
                                        >
                                    <?php else: ?>
                                        Sem imagem
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($produto['codigo'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($produto['categoria'] ?? 'Sem categoria', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <?= (int) $produto['estoque'] ?>
                                </td>

                                <td>
                                    <?php if ($produto['ativo'] == 1): ?>
                                        <span class="ativo">Ativo</span>
                                    <?php else: ?>
                                        <span class="inativo">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="acoes">

                                        <a href="editar.php?id=<?= (int) $produto['id'] ?>">
                                            Editar
                                        </a>

                                        <form
                                            action="excluir.php"
                                            method="POST"
                                            onsubmit="return confirm('Tem certeza que deseja excluir este produto? Essa ação não poderá ser desfeita.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $produto['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="token"
                                                value="<?= htmlspecialchars($_SESSION['token_exclusao'], ENT_QUOTES, 'UTF-8') ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="botao-excluir"
                                            >
                                                Excluir
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="vazio">
                                Nenhum produto encontrado.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</body>
</html>