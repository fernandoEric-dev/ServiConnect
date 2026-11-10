<?php
require_once __DIR__ . '/backend/conexao.php';
$mensagem = ''; $tipo_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = field_text($_POST,'email',100,true);
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) abort_request(422,'Informe um e-mail válido.');
    $key = hash('sha256','reset:'.($_SERVER['REMOTE_ADDR'] ?? '').':'.strtolower($email));
    $check = $pdo->prepare('SELECT tentativas FROM login_tentativas WHERE chave=? AND janela_inicio>DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $check->execute([$key]);
    if ((int)$check->fetchColumn() >= 5) abort_request(429,'Aguarde 15 minutos para uma nova solicitação.');
    $pdo->prepare('INSERT INTO login_tentativas (chave,tentativas,janela_inicio) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE tentativas=IF(janela_inicio<DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,tentativas+1),janela_inicio=IF(janela_inicio<DATE_SUB(NOW(),INTERVAL 15 MINUTE),NOW(),janela_inicio)')->execute([$key]);
    $mensagem = 'Se o e-mail estiver cadastrado e a conta disponível, as instruções serão enviadas.';
    $tipo_msg = 'success';
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email=? AND status='ativo'");
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE usuarios SET reset_token=?,reset_expires=DATE_ADD(NOW(),INTERVAL 1 HOUR) WHERE id=?')->execute([hash('sha256',$token),$id]);
        $url = app_config()['app_url'];
        if ($url === '') { error_log('Configure APP_URL para recuperação de senha.'); }
        else {
            $link = $url.'/redefinir_senha.php?token='.$token;
            if ($is_local && in_array($_SERVER['REMOTE_ADDR'] ?? '',['127.0.0.1','::1'],true)) {
                $mensagem .= '<br>Teste local: <a href="'.htmlspecialchars($link,ENT_QUOTES,'UTF-8').'">Criar nova senha</a>';
            } else {
                $domain = parse_url($url,PHP_URL_HOST);
                if ($domain && preg_match('/^[a-z0-9.-]+$/iD',$domain)) {
                    $sent = mail($email,'Recuperacao de Senha - ServiConnect',"Use este link para criar uma nova senha:\n".$link,"From: suporte@".$domain."\r\n");
                    if (!$sent) error_log('Falha no envio de recuperação de senha.');
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - ServiConnect</title>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; text-align: left; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <form action="" method="post">
        <?php echo csrf_field(); ?>
        <h2>Recuperar Senha</h2>
        <p>Digite seu e-mail cadastrado para receber o link de redefinição.</p>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_msg; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <div>
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="email@empresa.com" required>
        </div>

        <button type="submit" style="margin-top: 20px;">Enviar Link de Recuperação</button>

        <p class="cadastro-link" style="margin-top: 20px;">
            <a href="login.php">Voltar para o Login</a>
        </p>
    </form>
</body>
</html>