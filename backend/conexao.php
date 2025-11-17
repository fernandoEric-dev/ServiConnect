<?php

// --- 1. CONFIGURAÇÕES DO BANCO DE DADOS ---

$host = 'localhost';        // Geralmente 'localhost' em ambientes de desenvolvimento
$db_name = 'serviconnect_db'; // Nome do seu banco de dados
$username = 'root';         // Seu usuário do BD (Ex: root no XAMPP/MAMP)
$password = '';             // Sua senha do BD (Ex: vazia no XAMPP/MAMP)
$charset = 'utf8mb4';       // Codificação recomendada para suportar emojis e caracteres especiais

// --- 2. CONFIGURAÇÃO DSN (Data Source Name) ---

// Define a string de conexão para MySQL usando o driver 'mysql'
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

// --- 3. OPÇÕES DO PDO ---

// Define opções para forçar o tratamento de erros e o modo de busca padrão (fetch mode)
$options = [
    // Lança exceções para erros, permitindo tratamento adequado (muito importante!)
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    // Garante que os resultados sejam retornados como arrays associativos (chave => valor)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
    // Desliga a emulação de prepared statements (melhorando a segurança)
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- 4. TENTATIVA DE CONEXÃO ---

try {
     // Cria uma nova instância do PDO (a conexão)
     $pdo = new PDO($dsn, $username, $password, $options);
     
     // Opcional: Apenas para verificar se a conexão foi bem-sucedida
     // echo "Conexão bem-sucedida!";

} catch (\PDOException $e) {
     // Se houver um erro, exibe uma mensagem amigável e registra o erro
     // O código 'die' interrompe a execução do script
     die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// A variável $pdo agora contém o objeto de conexão que será usado em outros arquivos PHP.

// Não é necessário fechar a tag ?> no final de arquivos PHP puros.