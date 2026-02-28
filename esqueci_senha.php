<?php
require_once 'backend/conexao.php';

// Auto-configuração: Cria as colunas de recuperação no banco de dados automaticamente
try {
    $checkToken = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'reset_token'")->rowCount();
    if ($checkToken == 0) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN reset_token VARCHAR(100) DEFAULT NULL, ADD COLUMN reset_expires DATETIME DEFAULT NULL");
    }
} catch (\PDOException $e) {}

$mensagem = '';
$tipo_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Verifica se o e-mail existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Gera um token secreto e define a validade para 1 hora
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE id = ?")
            ->execute([$token, $expires, $user['id']]);

        // Monta o link mágico
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $link = $protocolo . "://" . $_SERVER['HTTP_HOST'] . "/redefinir_senha.php?token=" . $token;

        // Se for no seu PC (XAMPP), mostra na tela. Se for online, envia o E-mail.
        if ($is_local) {
            $mensagem = "<strong>AMBIENTE LOCAL:</strong><br>O XAMPP não envia e-mails. Clique no link abaixo para testar:<br><br><a href='$link' style='color:#004b87; word-break: break-all;'>$link</a>";
            $tipo_msg = 'success';
        } else {
            $to = $email;
            $subject = "Recuperacao de Senha - ServiConnect";
            $message = "Olá!\n\nVocê solicitou a redefinição de senha.\nClique no link abaixo para criar uma nova senha:\n\n" . $link . "\n\nSe você não solicitou isso, ignore este e-mail.";
            $headers = "From: suporte@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "Reply-To: suporte@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            if (mail($to, $subject, $message, $headers)) {
                $mensagem = "Instruções foram enviadas para o seu e-mail! (Verifique a caixa de Spam)";
                $tipo_msg = 'success';
            } else {
                $mensagem = "Erro do servidor ao tentar enviar o e-mail.";
                $tipo_msg = 'error';
            }
        }
    } else {
        // Mensagem padrão por segurança (para hackers não descobrirem quais e-mails estão no banco)
        $mensagem = "Se o e-mail estiver cadastrado, as instruções foram enviadas.";
        $tipo_msg = 'success';
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