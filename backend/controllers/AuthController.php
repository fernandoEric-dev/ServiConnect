<?php
require_once __DIR__ . '/../conexao.php';
header('Content-Type: application/json; charset=utf-8');
require_post();
$data = json_input();
require_csrf($data);
$identificacao = normalize_document(field_text($data, 'identificacao', 30, true));
$senha = $data['senha'] ?? '';
if (!is_string($senha) || strlen($senha) > 1024 || !preg_match('/^(?:[0-9]{11}|[A-Z0-9]{12}[0-9]{2})$/D', $identificacao)) abort_request(422, 'Dados de acesso inválidos.');
$key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ':' . $identificacao);
$attempt = $pdo->prepare('SELECT tentativas FROM login_tentativas WHERE chave = ? AND janela_inicio > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
$attempt->execute([$key]);
if ((int)$attempt->fetchColumn() >= 10) { header('Retry-After: 900'); abort_request(429, 'Muitas tentativas. Tente novamente em 15 minutos.'); }
$stmt = $pdo->prepare('SELECT id, senha, login_cnpj FROM administradores WHERE login_cnpj = ?');
$stmt->execute([$identificacao]);
$user = $stmt->fetch();
$role = $user ? 'admin' : '';
if (!$user) {
    $stmt = $pdo->prepare('SELECT u.id,u.senha,u.status,u.tipo_pessoa,e.tipo_empresa FROM usuarios u LEFT JOIN empresas e ON e.usuario_id=u.id WHERE u.cpf_cnpj=?');
    $stmt->execute([$identificacao]);
    $user = $stmt->fetch();
    $role = account_role($user['tipo_empresa'] ?? '');
}
if (!$user || !password_verify($senha, $user['senha']) || ($role !== 'admin' && ($user['status'] ?? '') !== 'ativo')
    || !in_array($role, ['admin','contratante','prestador'], true)) {
    $pdo->prepare('INSERT INTO login_tentativas (chave,tentativas,janela_inicio) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE tentativas=IF(janela_inicio < DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,tentativas+1), janela_inicio=IF(janela_inicio < DATE_SUB(NOW(),INTERVAL 15 MINUTE),NOW(),janela_inicio)')->execute([$key]);
    abort_request(401, 'Documento ou senha inválidos, ou conta indisponível.');
}
$pdo->prepare('DELETE FROM login_tentativas WHERE chave=?')->execute([$key]);
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_cnpj'] = $identificacao;
$_SESSION['user_document'] = $identificacao;
$_SESSION['user_role'] = $role;
$_SESSION['account_type'] = $user['tipo_empresa'] ?? 'admin';
$_SESSION['person_type'] = $user['tipo_pessoa'] ?? 'juridica';
csrf_token();
$routes = ['admin'=>'admin.php','contratante'=>'dashboard_contratante.php','prestador'=>'dashboard_tercerizada.php'];
$requestedProvider = isset($data['prestador']) && is_scalar($data['prestador'])
    ? (filter_var($data['prestador'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0) : 0;
$destination = $requestedProvider ? 'perfil_empresa.php?id=' . $requestedProvider : $routes[$role];
echo json_encode(['success'=>true,'message'=>'Acesso autorizado.','redirect'=>$destination]);
