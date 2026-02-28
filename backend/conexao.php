<?php
// DADOS EXCLUSIVOS DO INFINITYFREE
$host = 'sql105.infinityfree.com';
$db   = 'if0_41205929_serviconnecct_banco'; // Espaço final removido!
$user = 'if0_41205929'; 
$pass = 'legalizadO1'; 

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