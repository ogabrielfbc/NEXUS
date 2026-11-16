<?php

try {

    $pdo = require __DIR__ . '/config/database.php';

    $sql = "SELECT id, nome, preco, estoque
            FROM produtos
            WHERE ativo = 1";

    $consulta = $pdo->query($sql);

    $produtos = $consulta->fetchAll();

    echo "<h1>NEXUS TECH</h1>";
    echo "<h2>Produtos encontrados: " . count($produtos) . "</h2>";

    foreach ($produtos as $produto) {

        echo "<hr>";

        echo "<h3>" .
            htmlspecialchars($produto['nome']) .
            "</h3>";

        echo "<p>Preço: R$ " .
            number_format($produto['preco'], 2, ',', '.') .
            "</p>";

        echo "<p>Estoque: " .
            $produto['estoque'] .
            "</p>";
    }

} catch (Throwable $erro) {

    echo "Erro: " . htmlspecialchars($erro->getMessage());

}

?>
!html_entity_decode