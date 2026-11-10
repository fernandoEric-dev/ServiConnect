<?php
// Entrada do aplicativo: destinos internos fixos e sessão normal do site.
require_once __DIR__ . '/backend/conexao.php';
header('Cache-Control: no-store');
$targets = ['orcamentos'=>'orcamentos.php', 'perfil'=>'dashboard.php#perfil', 'conta'=>'dashboard.php'];
$destination = isset($_GET['destino']) && is_string($_GET['destino']) ? $_GET['destino'] : 'conta';
if (!isset($targets[$destination])) abort_request(422, 'Destino inválido.');
$target = $targets[$destination];
if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
    $_SESSION['app_return_to'] = $target;
    header('Location: /login.php', true, 303);
    exit;
}
require_role($pdo);
header('Location: /' . $target, true, 303);
