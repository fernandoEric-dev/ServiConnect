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
    echo json_encode(['success' => false, 'message' => 'CNPJ não encontrado.']);
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
        case 'admin':
            $redirect_url = 'admin.php';
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