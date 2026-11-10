<?php
require_once __DIR__ . '/../conexao.php';
require_post();
require_role($pdo, ['contratante']);
require_csrf();
$id = filter_var($_POST['terceirizada_id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$descricao = field_text($_POST, 'descricao_servico', 10000, true);
$local = field_text($_POST, 'localizacao_servico', 255, true);
$area = field_text($_POST, 'area_servico_solicitada', 100, true);
$people = filter_var($_POST['numero_funcionarios'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>100000]]);
if (!$id || !$people) abort_request(422, 'Revise o prestador e a quantidade de pessoas.');
$stmt = $pdo->prepare("SELECT e.usuario_id FROM empresas e JOIN usuarios u ON u.id=e.usuario_id WHERE e.usuario_id=? AND e.tipo_empresa IN ('terceirizada','mei') AND u.status='ativo'");
$stmt->execute([$id]);
if (!$stmt->fetchColumn()) abort_request(422, 'Prestador indisponível.');
try {
    $pdo->prepare("INSERT INTO solicitacoes (contratante_id,terceirizada_id,descricao_servico,localizacao_servico,numero_funcionarios,area_servico_solicitada,status) VALUES (?,?,?,?,?,?,'aberta')")
        ->execute([$_SESSION['user_id'],$id,$descricao,$local,$people,$area]);
    header('Location: /dashboard_contratante.php?status=solicitacao_enviada', true, 303);
} catch (PDOException $e) {
    error_log('Solicitação: '.$e->getMessage());
    abort_request(500, 'Não foi possível enviar o pedido. Tente novamente.');
}
