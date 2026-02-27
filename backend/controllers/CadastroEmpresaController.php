<?php
// backend/controllers/CadastroEmpresaController.php

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

require_once '../conexao.php';
// Certifique-se de que o caminho para o Model está correto
require_once '../models/UsuarioModel.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// 1. Recebe e decodifica os dados JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// 2. Validação básica (CNPJ e Senha limpos)
$cnpj = isset($data['cnpj']) ? preg_replace('/\D/', '', $data['cnpj']) : '';
$email_acesso = $data['email_acesso'] ?? null;
$senha = $data['senha'] ?? null;
$tipo_empresa = $data['tipo'] ?? null;

if (empty($cnpj) || empty($email_acesso) || empty($senha) || empty($tipo_empresa)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios (CNPJ, Email, Senha, Tipo) não preenchidos.']);
    exit;
}

// 3. Inicializa o Model
$usuarioModel = new UsuarioModel($pdo);

// ... (código anterior igual) ...

// 4. Verifica se o CNPJ ou Email já existe (Prevenção de Duplicidade)
if ($usuarioModel->existeUsuario($cnpj, $email_acesso)) {
    http_response_code(409); // Conflict
    echo json_encode(['success' => false, 'message' => 'CNPJ ou Email já cadastrado.']);
    exit;
}

// 4.1 VERIFICAÇÃO LGPD E BLOQUEIO: Checa se o usuário está arquivado/banido
$stmtArquivado = $pdo->prepare("SELECT id FROM usuarios_arquivados WHERE cpf_cnpj = ?");
$stmtArquivado->execute([$cnpj]); // Usamos a variável $cnpj que já foi limpa lá em cima

if ($stmtArquivado->fetch()) {
    http_response_code(403); // Proibido
    echo json_encode(['success' => false, 'message' => 'Este documento está restrito ou arquivado em nosso sistema. Entre em contato com o suporte.']);
    exit;
}

// 5. Hash da Senha e Preparação dos Dados
$hashed_senha = password_hash($senha, PASSWORD_DEFAULT);

// ... (resto do código igual) ...

$dados_usuario = [
    'cpf_cnpj' => $cnpj,
    'email' => $email_acesso,
    'senha' => $hashed_senha,
    'tipo_conta' => 'empresa' // Define o tipo de conta como genérico 'empresa'
];

$dados_empresa = [
    'nome' => $data['nome'] ?? null,
    'tipo_empresa' => $tipo_empresa, // 'contratante' ou 'terceirizada'
    'telefone' => $data['telefoneEmpresa'] ?? null,
    'responsavel' => $data['responsavel'] ?? null,
    'descricao' => $data['descricao'] ?? null,
    'regiao' => $data['regiao'] ?? null,
    'horario' => $data['horario'] ?? null,
    'cep' => preg_replace('/\D/', '', $data['cepEmpresa'] ?? ''),
    'logradouro' => $data['logradouroEmpresa'] ?? null,
    'numero' => $data['numeroEmpresa'] ?? null,
    'complemento' => $data['complementoEmpresa'] ?? null,
    'bairro' => $data['bairroEmpresa'] ?? null,
    'cidade' => $data['cidadeEmpresa'] ?? null,
    'estado' => $data['estadoEmpresa'] ?? null,
];


// 6. Executa a Transação de Cadastro
try {
    // Inicia a transação
    $pdo->beginTransaction();

    // Insere o usuário e pega o ID recém-criado
    $usuario_id = $usuarioModel->cadastrarUsuario($dados_usuario);

    if ($usuario_id) {
        // Usa o ID do usuário para o registro na tabela 'empresas'
        $dados_empresa['usuario_id'] = $usuario_id; 
        
        // Cadastra os detalhes da empresa
        $usuarioModel->cadastrarEmpresa($dados_empresa);
        
        // Confirma todas as operações
        $pdo->commit(); 

        echo json_encode([
            'success' => true, 
            'message' => 'Cadastro de Empresa realizado com sucesso!'
        ]);
        
    } else {
        // Falha na inserção do usuário
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Falha ao cadastrar usuário na tabela principal.']);
    }

} catch (PDOException $e) {
    // Captura qualquer erro de banco de dados e desfaz
    $pdo->rollBack();
    error_log("Erro de PDO no cadastro: " . $e->getMessage()); // Para debug no log do XAMPP
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao registrar a empresa.']);
}
?>