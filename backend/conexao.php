<?php
// Detecta se o site está rodando no seu computador (XAMPP) ou na internet
$is_local = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1');

if ($is_local) {
    // 1. CONFIGURAÇÃO DO XAMPP (Localhost)
    $host = 'localhost';
    $db   = 'serviconnect_banco'; // Nome do seu banco no phpMyAdmin do XAMPP
    $user = 'root';
    $pass = ''; // No XAMPP a senha padrão é vazia
} else {
    // 2. CONFIGURAÇÃO DO INFINITYFREE (Online)
    $host = 'sql105.infinityfree.com';
    $db   = 'if0_41205929_serviconnecct_banco'; // Seu banco real corrigido
    $user = 'if0_41205929'; 
    $pass = 'legalizadO1'; 
}

// Prepara a conexão com o banco escolhido acima
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Tenta conectar
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Se der erro, mostra qual foi
    die("Erro de Conexão com o Banco de Dados: " . $e->getMessage());
}
?>