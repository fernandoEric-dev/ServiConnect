<?php
// Incluído somente após require_role($pdo, ['admin']).
$mensagem = '';
if (isset($_GET['acao'])) abort_request(405, 'Use os botões do painel para alterar uma conta.');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    $action = $_POST['acao'] ?? '';
    $transitions = ['bloquear'=>['ativo','bloqueado'],'desbloquear'=>['bloqueado','ativo'],
        'arquivar'=>[null,'arquivado'],'desarquivar'=>['arquivado','ativo']];
    if (!$id || !is_string($action) || !isset($transitions[$action])) abort_request(422, 'Ação inválida.');
    if (($action === 'desarquivar') !== (basename($_SERVER['SCRIPT_NAME']) === 'arquivados.php')) abort_request(422, 'Use a seção apropriada do painel.');
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT status FROM usuarios WHERE id=? FOR UPDATE');
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        [$from,$to] = $transitions[$action];
        if ($current === false || ($from !== null && $current !== $from) || ($action === 'arquivar' && !in_array($current,['ativo','bloqueado'],true))) {
            $pdo->rollBack();
            abort_request(409, 'Conta indisponível para esta ação. Atualize a página.');
        }
        $pdo->prepare('UPDATE usuarios SET status=?,arquivado_em=?,reset_token=NULL,reset_expires=NULL WHERE id=?')
            ->execute([$to,$to === 'arquivado' ? date('Y-m-d H:i:s') : null,$id]);
        $pdo->commit();
        header('Location: /' . basename($_SERVER['SCRIPT_NAME']) . '?status=conta_atualizada',true,303);
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Administração: '.$e->getMessage());
        abort_request(500,'Não foi possível alterar a conta.');
    }
}
if (($_GET['status'] ?? '') === 'conta_atualizada') $mensagem = '<div class="alert alert-success">Conta atualizada com sucesso.</div>';

function admin_action_form(int $id, string $action, string $label, string $class): string {
    $confirmation = $action === 'arquivar' || $action === 'desarquivar' ? ' onsubmit="return confirm(\'Confirmar esta alteração de acesso?\');"' : '';
    return '<form method="post" style="display:inline"'.$confirmation.'>'.csrf_field()
        .'<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="acao" value="'.$action.'">'
        .'<button type="submit" class="btn-acao '.$class.'">'.$label.'</button></form>';
}
