<?php
require_once __DIR__ . '/backend/conexao.php';
$mensagem = ''; $tipo_msg = ''; $token_valido = false;
$token = $_GET['token'] ?? '';
if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D',$token)) abort_request(400,'Link de recuperação inválido.');
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token=? AND reset_expires>NOW() AND status='ativo'");
$stmt->execute([hash('sha256',$token)]);
$id = $stmt->fetchColumn();
$token_valido = (bool)$id;
if (!$token_valido) { $mensagem='Este link é inválido ou expirou. Solicite outro.'; $tipo_msg='error'; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $senha = $_POST['senha'] ?? '';
    if (!$token_valido) abort_request(400,'Este link é inválido ou expirou.');
    if (!is_string($senha) || strlen($senha)<6 || strlen($senha)>72 || $senha !== ($_POST['confirmar_senha'] ?? null)) {
        $mensagem='As senhas devem coincidir e ter entre 6 e 72 bytes.'; $tipo_msg='error';
    } else {
        $update = $pdo->prepare("UPDATE usuarios SET senha=?,reset_token=NULL,reset_expires=NULL WHERE id=? AND reset_token=? AND reset_expires>NOW() AND status='ativo'");
        $update->execute([password_hash($senha,PASSWORD_DEFAULT),$id,hash('sha256',$token)]);
        if ($update->rowCount()!==1) abort_request(409,'Este link já foi utilizado ou expirou.');
        $mensagem='Senha alterada com sucesso!'; $tipo_msg='success'; $token_valido=false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Nova Senha - ServiConnect</title>
    <link rel="stylesheet" href="css/app.css?v=4"><link rel="stylesheet" href="css/login.css?v=4">
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; text-align: left; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body><main class="auth-shell"><a href="index.php" class="auth-brand" aria-label="ServiConnect — página inicial">Servi<span>Connect</span></a>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        <h2>Criar Nova Senha</h2>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_msg; ?>">
                <?php echo $mensagem; ?>
            </div>
            <?php if ($tipo_msg === 'success'): ?>
                <a href="login.php" style="display:block; text-align:center; padding: 10px; background: #004b87; color: white; text-decoration: none; border-radius: 4px;">Ir para o Login</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($token_valido): ?>
            <p>Digite e confirme sua nova senha de acesso.</p>
            <div>
                <label for="senha">Nova Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Mínimo de 6 caracteres" required>
            </div>
            <div>
                <label for="confirmar_senha">Confirmar Nova Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita a nova senha" required>
            </div>

            <button type="submit" style="margin-top: 20px;">Salvar Nova Senha</button>
        <?php endif; ?>
    </form>
</main></body>
</html>