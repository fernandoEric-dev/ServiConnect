<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../orders.php';
require_post(); require_role($pdo, ['contratante']); require_csrf();
try {
    $id = order_create($pdo, (int)$_SESSION['user_id'], $_POST);
    $_SESSION['order_feedback'] = ['message'=>'Pedido enviado. Você pode acompanhar a resposta nesta página.', 'error'=>false];
    header('Location: /orcamento.php?id=' . $id, true, 303);
} catch (Throwable $e) {
    $provider = filter_var($_POST['terceirizada_id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
    order_fail($e, '/solicitacao_orcamento.php?terceirizada_id=' . $provider, $_POST);
}
