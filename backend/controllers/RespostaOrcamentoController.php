<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../orders.php';
require_post(); require_role($pdo, ['prestador']); require_csrf();
$id = filter_var($_POST['solicitacao_id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
try {
    $inputAction = order_text($_POST, 'action', 30, true);
    $action = ['orcamento'=>'enviar_proposta','recusa'=>'recusar_pedido','revisao'=>'revisar_proposta'][$inputAction] ?? '';
    if (!$id || $action === '') throw new OrderProblem('Resposta inválida.');
    order_transition($pdo, $id, (int)$_SESSION['user_id'], $action, $_POST);
    $_SESSION['order_feedback'] = ['message'=>'Resposta registrada no histórico do pedido.', 'error'=>false];
    header('Location: /orcamento.php?id=' . $id, true, 303);
} catch (Throwable $e) {
    if ($e instanceof OrderProblem && $e->status === 404) $e = new OrderProblem('Pedido indisponível ou já respondido.', 409);
    order_fail($e, '/orcamento.php?id=' . $id, $_POST);
}
