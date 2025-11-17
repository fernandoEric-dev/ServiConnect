<?php
// dashboard_terceirizada.php
session_start();

// ⚠️ SEGURANÇA: Redireciona se não estiver logado ou não for Terceirizada
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'terceirizada') {
    header('Location: login.php');
    exit;
}

$nome_empresa = "Empresa Terceirizada (CNPJ: {$_SESSION['user_cnpj']})"; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Terceirizada - ServiConnect</title>
</head>
<body>
    <h1>Bem-vindo(a), <?php echo htmlspecialchars($nome_empresa); ?>!</h1>
    <p>Seu papel é **Prestar Serviços**. Gerencie seu perfil e orçamentos recebidos.</p>

    <h2>Gerenciamento do Perfil</h2>
    <a href="logout.php">Sair (Logout)</a>
</body>
</html>