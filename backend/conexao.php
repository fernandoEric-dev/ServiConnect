<?php
// backend/conexao.php

// ⚠️ ATENÇÃO: Verifique se estes dados estão corretos no seu XAMPP.
$host = 'sql202.infinityfree.com';
$db   = 'if0_40447154_serviconnect'; 
$user = 'if0_40447154'; 
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
    // Em caso de falha na conexão, ele para
    die("Erro de Conexão com o Banco de Dados: " . $e->getMessage());
}
?>