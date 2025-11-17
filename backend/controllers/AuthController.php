<?php
// backend/controllers/AuthController.php

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
require_once '../conexao.php';
require_once '../models/UsuarioModel.php'; 

// Habilitar erros de sessão no TOPO, se necessário:
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$identificacao = isset($data['identificacao']) ? preg_replace('/\D/', '', $data['identificacao']) : ''; 
$senha = isset($data['senha']) ? $data['senha'] : '';

// 1. BUSCA O USUÁRIO E O TIPO ESPECÍFICO DE EMPRESA (JOIN)
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.senha, u.cpf_cnpj, u.tipo_conta,
        e.tipo_empresa 
    FROM usuarios u
    -- Junta a tabela 'empresas' para pegar a função específica
    LEFT JOIN empresas e ON u.id = e.usuario_id
    WHERE u.cpf_cnpj = ?
");
$stmt->execute([$identificacao]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'CNPJ não encontrado.']);
    exit;
}

// 2. VERIFICAÇÃO DE SENHA COM HASH
if (password_verify($senha, $usuario['senha'])) {
    
    // Define a role: prioriza o tipo_empresa (contratante/terceirizada), 
    // mas usa tipo_conta ('admin') se for o caso.
    $role = $usuario['tipo_conta']; // Valor inicial: 'empresa' ou 'admin'

    if ($role === 'empresa' && isset($usuario['tipo_empresa'])) {
        // Se for uma 'empresa' genérica, usa o valor específico da tabela 'empresas'
        $role = $usuario['tipo_empresa']; // Agora será 'contratante' ou 'terceirizada'
    }

    // 3. Sucesso! Registra a sessão
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['user_cnpj'] = $usuario['cpf_cnpj'];
    $_SESSION['user_role'] = $role; // A sessão agora tem o valor correto ('contratante' ou 'terceirizada')

    // 4. Determinar URL de Redirecionamento
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