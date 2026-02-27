<?php
session_start();

// SEGURANÇA: Verifica se o usuário está logado e se é um administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'backend/conexao.php';

try {
    // 1. Conta o total de usuários
    $stmtUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $totalUsuarios = $stmtUsuarios->fetchColumn();

    // 2. Conta o total de empresas
    $stmtEmpresas = $pdo->query("SELECT COUNT(*) FROM empresas");
    $totalEmpresas = $stmtEmpresas->fetchColumn();

    // 3. Conta o total de solicitações (pedidos)
    $stmtPedidos = $pdo->query("SELECT COUNT(*) FROM solicitacoes");
    $totalPedidos = $stmtPedidos->fetchColumn();

    // 4. Busca os últimos 5 usuários cadastrados para a "Atividade Recente"
    $stmtRecentes = $pdo->query("SELECT cpf_cnpj, email, tipo_conta FROM usuarios ORDER BY id DESC LIMIT 5");
    $atividades = $stmtRecentes->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    die("Erro ao carregar dados do painel: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiConnect | Painel de Administração</title>
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/admin.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="admin-container">
        
        <aside class="sidebar">
            <div class="logo-area">
                <img src="foto/logo.jpg" alt="Logo ServiConnect" class="logo-admin"> 
                <h3>Painel de Gestão</h3>
            </div>
            
            <nav class="admin-nav">
                <ul id="adminNavigation">
                    <li class="nav-item active"><a href="#dashboard"><i class="fa-solid fa-gauge"></i> Dashboard Geral</a></li>
                    <li class="nav-item"><a href="#users"><i class="fa-solid fa-users"></i> Gestão de Usuários</a></li>
                    <li class="nav-item"><a href="#companies"><i class="fa-solid fa-building"></i> Gestão de Empresas</a></li>
                    <li class="nav-item"><a href="#services"><i class="fa-solid fa-list-check"></i> Pedidos de Serviço</a></li>
                    <li class="nav-item logout-link"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1 id="pageTitle">Dashboard Geral</h1>
                <div class="user-info">
                    <span id="userName">Olá, Administrador!</span>
                    <i class="fa-solid fa-user-circle user-icon"></i>
                </div>
            </header>

            <section class="dashboard-widgets">
                <div class="widget total-users">
                    <i class="fa-solid fa-user-plus icon-widget"></i>
                    <h4 class="widget-title">Total de Usuários</h4>
                    <p class="widget-value"><?php echo $totalUsuarios; ?></p>
                </div>
                
                <div class="widget total-companies">
                    <i class="fa-solid fa-briefcase icon-widget"></i>
                    <h4 class="widget-title">Empresas Cadastradas</h4>
                    <p class="widget-value"><?php echo $totalEmpresas; ?></p>
                </div>
                
                <div class="widget service-requests">
                    <i class="fa-solid fa-bell icon-widget"></i>
                    <h4 class="widget-title">Total de Pedidos</h4>
                    <p class="widget-value"><?php echo $totalPedidos; ?></p>
                </div>
            </section>

            <section class="recent-activity card-panel">
                <h2 class="section-title">Últimos Cadastros</h2>
                <div class="activity-list">
                    <?php if (empty($atividades)): ?>
                        <p class="activity-item">Nenhuma atividade recente.</p>
                    <?php else: ?>
                        <?php foreach ($atividades as $ativ): ?>
                            <p class="activity-item">
                                <strong>Novo <?php echo htmlspecialchars($ativ['tipo_conta']); ?>:</strong> 
                                CNPJ/CPF: <?php echo htmlspecialchars($ativ['cpf_cnpj']); ?> - 
                                Email: <?php echo htmlspecialchars($ativ['email']); ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

</body>
</html>