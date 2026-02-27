<?php
// backend/controllers/AuthController.php

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

$identificacao = isset($data['identificacao']) ? preg_replace('/\D/', '', $data['identificacao']) : ''; 
$senha = isset($data['senha']) ? $data['senha'] : '';

// 1. VERIFICA PRIMEIRO SE É UM ADMINISTRADOR (NOVA TABELA)
$stmtAdmin = $pdo->prepare("SELECT id, senha, login_cnpj FROM administradores WHERE login_cnpj = ?");
$stmtAdmin->execute([$identificacao]);
$admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    // É um admin! Vamos verificar a senha
    if (password_verify($senha, $admin['senha'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_cnpj'] = $admin['login_cnpj'];
        $_SESSION['user_role'] = 'admin'; 
        
        echo json_encode([
            'success' => true,
            'message' => 'Login de Administrador bem-sucedido!',
            'redirect' => 'admin.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Senha incorreta.']);
    }
    exit; // Para a execução do script aqui para não buscar nos usuários comuns
}

// 2. SE NÃO FOR ADMIN, FAZ A BUSCA NORMAL NA TABELA DE USUÁRIOS
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.senha, u.cpf_cnpj, u.tipo_conta,
        e.tipo_empresa 
    FROM usuarios u
    LEFT JOIN empresas e ON u.id = e.usuario_id
    WHERE u.cpf_cnpj = ?
");
$stmt->execute([$identificacao]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'CNPJ/CPF não encontrado.']);
    exit;
}

if (password_verify($senha, $usuario['senha'])) {
    
    $role = $usuario['tipo_conta']; 

    if ($role === 'empresa' && isset($usuario['tipo_empresa'])) {
        $role = $usuario['tipo_empresa']; 
    }

    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['user_cnpj'] = $usuario['cpf_cnpj'];
    $_SESSION['user_role'] = $role; 

    // REDIRECIONAMENTO SEPARADO
    $redirect_url = '';
    switch ($role) {
        case 'contratante':
            $redirect_url = 'dashboard_contratante.php'; 
            break;
        case 'terceirizada':
            $redirect_url = 'dashboard_terceirizada.php'; 
            break;
        default:
            $redirect_url = 'login.php';
    }

    echo json_encode([
        'success' => true,
        'message' => 'Login bem-sucedido!',
        'redirect' => $redirect_url 
    ]);

} else {
    echo json_encode(['success' => false, 'message' => 'Senha incorreta.']);
}
?>