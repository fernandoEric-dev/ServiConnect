<?php
require_once 'backend/conexao.php';

$mensagem = '';
$tipo_msg = '';
$token = $_GET['token'] ?? '';
$token_valido = false;

if ($token) {
    // Verifica se o token existe e ainda não expirou (valido por 1 hora)
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token_valido = true;
    } else {
        $mensagem = "Este link de recuperação é inválido ou já expirou. Solicite um novo.";
        $tipo_msg = 'error';
    }
} else {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (strlen($senha) < 6) {
        $mensagem = "A senha deve ter pelo menos 6 caracteres.";
        $tipo_msg = 'error';
    } elseif ($senha !== $confirmar_senha) {
        $mensagem = "As senhas não coincidem.";
        $tipo_msg = 'error';
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        // Atualiza a senha e apaga o token para que o link pare de funcionar por segurança
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        $mensagem = "Senha alterada com sucesso!";
        $tipo_msg = 'success';
        $token_valido = false; // Esconde o formulário de senha após o sucesso
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Nova Senha - ServiConnect</title>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; text-align: left; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <form action="" method="post">
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
</body>
</html>