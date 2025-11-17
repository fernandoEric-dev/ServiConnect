<?php
// backend/teste_conexao.php

// ATENÇÃO: O caminho deve estar correto, baseado na localização deste arquivo.
require_once 'conexao.php'; 

echo "<h1>Teste de Conexão PDO</h1>";

try {
    // 1. Tentar buscar o nome do banco de dados (Teste a Conexão Básica)
    $stmt = $pdo->query('SELECT DATABASE()');
    $db_name_result = $stmt->fetchColumn();

    // 2. Tentar buscar o ID máximo na tabela de usuários (Teste o Schema/Tabela)
    $stmt_user = $pdo->query('SELECT MAX(id) FROM usuarios');
    $max_id = $stmt_user->fetchColumn();

    // 3. Tentar buscar o ID máximo na tabela de empresas (Teste o Schema/Tabela)
    $stmt_emp = $pdo->query('SELECT MAX(id) FROM empresas');
    $max_emp_id = $stmt_emp->fetchColumn();

    echo "<p style='color: green; font-weight: bold;'>✅ SUCESSO! A conexão com o MySQL foi estabelecida.</p>";
    echo "<p>Banco de Dados Conectado: <strong>" . htmlspecialchars($db_name_result) . "</strong></p>";
    echo "<p>Último ID na tabela USUARIOS: <strong>" . (empty($max_id) ? '0 (Tabela Vazia)' : htmlspecialchars($max_id)) . "</strong></p>";
    echo "<p>Último ID na tabela EMPRESAS: <strong>" . (empty($max_emp_id) ? '0 (Tabela Vazia)' : htmlspecialchars($max_emp_id)) . "</strong></p>";
    echo "<p>Tudo pronto para o desenvolvimento do Back-end! 👍</p>";

} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ ERRO NA CONEXÃO OU CONSULTA!</p>";
    echo "<p>Verifique o arquivo 'conexao.php' e o Painel do XAMPP (MySQL). Detalhes do erro:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>