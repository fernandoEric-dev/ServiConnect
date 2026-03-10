<?php
// backend/controllers/CadastroEmpresaController.php

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

require_once '../conexao.php';
require_once '../models/UsuarioModel.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$cnpj = isset($data['cnpj']) ? preg_replace('/\D/', '', $data['cnpj']) : '';
$email_acesso = $data['email_acesso'] ?? null;
$senha = $data['senha'] ?? null;
$tipo_empresa = $data['tipo'] ?? null;
$nome_empresa = $data['nome'] ?? 'Empresa em Configuração'; // Nome vindo da API via JS

if (empty($cnpj) || empty($email_acesso) || empty($senha) || empty($tipo_empresa)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados básicos incompletos.']);
    exit;
}

$usuarioModel = new UsuarioModel($pdo);

if ($usuarioModel->existeUsuario($cnpj, $email_acesso)) {
    http_response_code(409); 
    echo json_encode(['success' => false, 'message' => 'CNPJ ou Email já cadastrado.']);
    exit;
}

$hashed_senha = password_hash($senha, PASSWORD_DEFAULT);

$dados_usuario = [
    'cpf_cnpj' => $cnpj,
    'email' => $email_acesso,
    'senha' => $hashed_senha,
    'tipo_conta' => 'empresa'
];

// Preenche os dados da empresa com Nulos, pois serão completados no Perfil depois
$dados_empresa = [
    'nome' => $nome_empresa,
    'tipo_empresa' => $tipo_empresa,
    'telefone' => null,
    'responsavel' => null,
    'descricao' => null,
    'regiao' => null,
    'horario' => null,
    'cep' => null,
    'logradouro' => null,
    'numero' => null,
    'complemento' => null,
    'bairro' => null,
    'cidade' => null,
    'estado' => null,
];

try {
    $pdo->beginTransaction();

    $usuario_id = $usuarioModel->cadastrarUsuario($dados_usuario);

    if ($usuario_id) {
        $dados_empresa['usuario_id'] = $usuario_id; 
        $usuarioModel->cadastrarEmpresa($dados_empresa);
        
        $pdo->commit(); 

        echo json_encode([
            'success' => true, 
            'message' => 'Conta criada com sucesso!'
        ]);
        
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Falha ao cadastrar.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro de PDO no cadastro: " . $e->getMessage()); 
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}
?>