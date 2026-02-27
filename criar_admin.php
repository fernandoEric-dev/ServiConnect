<?php
require_once 'backend/conexao.php';

$mensagem = "";

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega os dados digitados e remove a pontuação do CNPJ
    $login_cnpj = preg_replace('/\D/', '', $_POST['cnpj']);
    $email_admin = $_POST['email'];
    $senha_plana = $_POST['senha'];

    // Criptografa a senha escolhida
    $senha_hash = password_hash($senha_plana, PASSWORD_DEFAULT);

    try {
        // Insere na tabela administradores
        $stmt = $pdo->prepare("INSERT INTO administradores (login_cnpj, email, senha) VALUES (?, ?, ?)");
        $stmt->execute([$login_cnpj, $email_admin, $senha_hash]);
        
        $mensagem = "<div style='color: green; font-weight: bold; margin-bottom: 20px;'>
                        Administrador criado com sucesso!<br>
                        Você já pode fazer <a href='login.php'>login</a>.
                     </div>";
    } catch (\PDOException $e) {
        // Se der erro de duplicação, avisa o usuário
        if ($e->getCode() == 23000) {
            $mensagem = "<div style='color: red; margin-bottom: 20px;'>Erro: Este CNPJ ou E-mail já está cadastrado.</div>";
        } else {
            $mensagem = "<div style='color: red; margin-bottom: 20px;'>Erro ao criar admin: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Criar Novo Administrador</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; }
        .form-container h2 { margin-top: 0; text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Criar Admin</h2>
    
    <?php echo $mensagem; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="cnpj">CNPJ (apenas números ou com pontuação):</label>
            <input type="text" id="cnpj" name="cnpj" required placeholder="00.000.000/0000-00">
        </div>

        <div class="form-group">
            <label for="email">E-mail do Admin:</label>
            <input type="email" id="email" name="email" required placeholder="admin@serviconnect.com">
        </div>

        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required placeholder="Digite a senha">
        </div>

        <button type="submit">Criar Administrador</button>
    </form>
</div>

</body>
</html>