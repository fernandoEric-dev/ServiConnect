<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$runtime = json_decode(file_get_contents($argv[1] ?? ''), true, 512, JSON_THROW_ON_ERROR);
$port = (int)$runtime['port'];
$pdo = new PDO("mysql:host=127.0.0.1;port=$port;charset=utf8mb4", 'root', $runtime['root_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS serviconnect_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$pdo->exec('USE serviconnect_dev');
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if ($tables && !in_array('schema_migrations', $tables, true)) {
    throw new RuntimeException('Banco existente sem controle de migrações. Instalação interrompida para preservar os dados.');
}
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (versao VARCHAR(100) PRIMARY KEY, aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
foreach (glob(dirname(__DIR__) . '/database/migrations/*.sql') as $file) {
    $version = basename($file);
    $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE versao = ?');
    $check->execute([$version]);
    if ($check->fetchColumn()) continue;
    $pdo->exec(file_get_contents($file));
    $pdo->prepare('INSERT INTO schema_migrations (versao) VALUES (?)')->execute([$version]);
    echo "Migração aplicada: $version\n";
}
$password = $pdo->quote($runtime['app_password']);
$pdo->exec("CREATE USER IF NOT EXISTS 'serviconnect_dev'@'127.0.0.1' IDENTIFIED BY $password");
$pdo->exec("ALTER USER 'serviconnect_dev'@'127.0.0.1' IDENTIFIED BY $password");
$pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON serviconnect_dev.* TO 'serviconnect_dev'@'127.0.0.1'");
$config = ['environment' => 'development', 'app_url' => 'http://127.0.0.1:8080', 'db_host' => '127.0.0.1',
    'db_port' => $port, 'db_name' => 'serviconnect_dev', 'db_user' => 'serviconnect_dev', 'db_password' => $runtime['app_password']];
$configPath = dirname(__DIR__) . '/backend/config.local.php';
if (is_file($configPath)) {
    $previous = require $configPath;
    if (($previous['environment'] ?? '') !== 'development' || ($previous['db_name'] ?? '') !== 'serviconnect_dev') {
        throw new RuntimeException('config.local.php existente não é deste ambiente de desenvolvimento.');
    }
}
file_put_contents($configPath, "<?php\nreturn " . var_export($config, true) . ";\n");
require __DIR__ . '/seed-dev.php';
echo "Banco serviconnect_dev pronto, com dados fictícios.\n";
