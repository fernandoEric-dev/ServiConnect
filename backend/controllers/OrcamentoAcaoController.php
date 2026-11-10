<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../orders.php';
require_post(); require_role($pdo, ['contratante','prestador']); require_csrf();
$id = filter_var($_POST['solicitacao_id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
try {
    $action = order_text($_POST, 'acao', 40, true);
    if (!$id) throw new OrderProblem('Pedido inválido.');
    order_transition($pdo, $id, (int)$_SESSION['user_id'], $action, $_POST);
    $_SESSION['order_feedback'] = ['message'=>'Atualização registrada. O outro participante pode consultá-la no histórico.', 'error'=>false];
    header('Location: /orcamento.php?id=' . $id . '#historico', true, 303);
} catch (Throwable $e) { order_fail($e, '/orcamento.php?id=' . $id, $_POST); }
