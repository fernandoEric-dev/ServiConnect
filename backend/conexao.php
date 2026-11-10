<?php
require_once __DIR__ . '/security.php';
$config = app_config();
$is_local = $config['environment'] === 'development';
try {
    if ($config['db_host'] === '' || $config['db_name'] === '' || $config['db_user'] === '') {
        throw new RuntimeException('Configure o banco em variáveis de ambiente ou config.local.php.');
    }
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_port'], $config['db_name']),
        $config['db_user'], $config['db_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
    $pdo->exec("SET time_zone = '+00:00'");
} catch (Throwable $e) {
    error_log('Falha de configuração/conexão do banco: ' . $e->getMessage());
    abort_request(503, 'Serviço temporariamente indisponível. Tente novamente mais tarde.');
}
