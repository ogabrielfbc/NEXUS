
<?php

$arquivoEnv = __DIR__ . '/../.env';

if (!file_exists($arquivoEnv)) {
    throw new RuntimeException('Arquivo .env não encontrado.');
}

$config = parse_ini_file($arquivoEnv);

if ($config === false) {
    throw new RuntimeException('Erro ao ler o arquivo .env.');
}

$host = $config['DB_HOST'];
$porta = $config['DB_PORT'];
$banco = $config['DB_NAME'];
$usuario = $config['DB_USER'];
$senha = $config['DB_PASSWORD'];


$dsn = "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4";

try {

    $pdo = new PDO(
        $dsn,
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    return $pdo;

} catch (PDOException $erro) {

    throw new RuntimeException(
        'Não foi possível conectar ao banco de dados.',
        0,
        $erro
    );

}
