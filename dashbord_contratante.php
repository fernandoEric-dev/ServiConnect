<?php
// dashboard_contratante.php
session_start();

// ⚠️ SEGURANÇA: Redireciona se não estiver logado ou não for Contratante
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'contratante') {
    header('Location: login.php');
    exit;
}

$nome_empresa = "Empresa Contratante (CNPJ: {$_SESSION['user_cnpj']})"; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Contratante - ServiConnect</title>
</head>
<body>
    <h1>Bem-vindo(a), <?php echo htmlspecialchars($nome_empresa); ?>!</h1>
    <p>Seu papel é **Contratar**. Use esta área para encontrar serviços terceirizados.</p>

    <h2>Procurar Empresas Terceirizadas</h2>
    <a href="logout.php">Sair (Logout)</a>
</body>
</html>