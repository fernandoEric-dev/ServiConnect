<?php
require_once __DIR__ . '/../conexao.php';
require_post();
require_role($pdo, ['prestador']);
require_csrf();
$id = filter_var($_POST['solicitacao_id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$action = $_POST['action'] ?? '';
$value = $_POST['valor_orcamento'] ?? '';
if (!$id || !in_array($action, ['orcamento','recusa'], true)) abort_request(422, 'Resposta inválida.');
if ($action === 'orcamento' && (!is_string($value) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $value) || (float)$value <= 0)) abort_request(422, 'Informe um valor positivo de até duas casas decimais.');
$status = $action === 'orcamento' ? 'orçada' : 'recusada';
try {
    $stmt = $pdo->prepare("UPDATE solicitacoes SET status=?,valor_orcamento=? WHERE id=? AND terceirizada_id=? AND status='aberta'");
    $stmt->execute([$status,$action === 'orcamento' ? $value : null,$id,$_SESSION['user_id']]);
    if ($stmt->rowCount() !== 1) abort_request(409, 'Pedido indisponível ou já respondido.');
    header('Location: /dashboard_tercerizada.php?status=resposta_enviada', true, 303);
} catch (PDOException $e) {
    error_log('Resposta ao orçamento: '.$e->getMessage());
    abort_request(500, 'Não foi possível responder. Tente novamente.');
}
