<?php
// Compatibilidade com links antigos; os painéis oficiais concentram as regras.
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo);
$routes = ['admin'=>'admin.php','contratante'=>'dashboard_contratante.php','prestador'=>'dashboard_tercerizada.php'];
header('Location: /'.$routes[$_SESSION['user_role']],true,303);
