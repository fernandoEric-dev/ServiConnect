<?php
$is_local = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1');

if ($is_local) {
    $host = 'localhost';
    $db   = 'serviconnect_banco';
    $user = 'root';
    $pass = '';
} else {
    $host = 'sql105.infinityfree.com';
    $db   = 'if0_41205929_serviconnecct_banco '; 
    $user = 'if0_41205929'; 
    $pass = 'legalizadO1'; 
}

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro de Conexão com o Banco de Dados: " . $e->getMessage());
}
?>