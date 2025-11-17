<?php
// backend/conexao.php

// 1. CONFIGURAÇÕES DO BANCO DE DADOS (AGORA COM O NOME CORRIGIDO)
$host = 'localhost';        
$db_name = 'serviconnect.dbt'; // Nome do banco de dados corrigido!
$username = 'root';         
$password = '';             
$charset = 'utf8mb4';       

// 2. CONFIGURAÇÃO DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

// 3. OPÇÕES DO PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4. TENTATIVA DE CONEXÃO
try {
     // Objeto de conexão PDO
     $pdo = new PDO($dsn, $username, $password, $options);
     
} catch (\PDOException $e) {
     // Se falhar, exibe o erro e para o script
     die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// O objeto de conexão ($pdo) agora está pronto para ser usado.