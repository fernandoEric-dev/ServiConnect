<?php
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo, ['admin']);
require_once __DIR__ . '/backend/admin_actions.php';

try {
    $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE status <> 'arquivado'")->fetchColumn();
    $totalEmpresas = $pdo->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
    $totalPedidos = $pdo->query("SELECT COUNT(*) FROM solicitacoes")->fetchColumn();
    $totalArquivados = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE status = 'arquivado'")->fetchColumn();

    $stmtUsuarios = $pdo->query("SELECT u.id,u.cpf_cnpj,u.email,u.tipo_conta,u.tipo_pessoa,u.status,e.tipo_empresa FROM usuarios u LEFT JOIN empresas e ON e.usuario_id=u.id WHERE u.status <> 'arquivado' ORDER BY u.id DESC");
    $listaUsuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log($e->getMessage()); abort_request(500,'Não foi possível carregar os dados.');
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
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; text-align: left; }
        .admin-table th, .admin-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .admin-table th { background-color: #f4f6f9; color: #333; font-weight: bold; }
        .btn-acao { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; font-size: 13px; margin-right: 5px; display: inline-block; }
        .btn-danger { background-color: #6c757d; }
        .btn-warning { background-color: #ffc107; color: #000; }
        .btn-success { background-color: #28a745; }
        .status-ativo { color: #28a745; font-weight: bold; }
        .status-bloqueado { color: #dc3545; font-weight: bold; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-warning { background-color: #fff3cd; color: #856404; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
    </style>
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
                    <li class="nav-item active"><a href="#"><i class="fa-solid fa-gauge"></i> Dashboard & Gestão</a></li>
                    <li> <a href="arquivados.php">Usuarios Arquivados</a></li>
                    <li class="nav-item logout-link"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
                </ul>
            </nav>
        </aside>

        <nav>

        </nav>

        <main class="main-content">
            <header class="content-header">
                <h1 id="pageTitle">Dashboard Geral</h1>
                <div class="user-info">
                    <span id="userName">Olá, Administrador!</span>
                    <i class="fa-solid fa-user-circle user-icon"></i>
                </div>
            </header>

            <?php echo $mensagem; ?>

            <section class="dashboard-widgets">
                <div class="widget total-users">
                    <i class="fa-solid fa-user-plus icon-widget"></i>
                    <h4 class="widget-title">Total de Usuários</h4>
                    <p class="widget-value"><?php echo $totalUsuarios; ?></p>
                </div>

                <div class="widget total-companies">
                    <i class="fa-solid fa-building icon-widget"></i>
                    <h4 class="widget-title">Empresas Cadastradas</h4>
                    <p class="widget-value"><?php echo $totalEmpresas; ?></p>
                </div>

                <div class="widget service-requests">
                    <i class="fa-solid fa-bell icon-widget"></i>
                    <h4 class="widget-title">Total de Pedidos</h4>
                    <p class="widget-value"><?php echo $totalPedidos; ?></p>
                </div>

                <div class="widget">
                    <i class="fa-solid fa-box-archive icon-widget" style="color: #6c757d;"></i>
                    <h4 class="widget-title">Contas Arquivadas</h4>
                    <p class="widget-value"><?php echo $totalArquivados; ?></p>
                </div>
            </section>

            <section class="recent-activity card-panel">
                <h2 class="section-title">Gestão de Usuários</h2>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>CNPJ/CPF</th>
                            <th>E-mail</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($listaUsuarios)): ?>
                            <tr><td colspan="6" style="text-align:center;">Nenhum usuário encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($listaUsuarios as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['cpf_cnpj']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars(account_type_label($user['tipo_empresa'] ?? $user['tipo_conta'], $user['tipo_pessoa'])); ?></td>
                                    <td class="<?php echo $user['status'] === 'bloqueado' ? 'status-bloqueado' : 'status-ativo'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['status'] ?? 'ativo')); ?>
                                    </td>
                                    <td>
                                        <?php if (!isset($user['status']) || $user['status'] !== 'bloqueado'): ?>
                                            <?php echo admin_action_form((int)$user['id'], 'bloquear', 'Bloquear', 'btn-warning'); ?>
                                        <?php else: ?>
                                            <?php echo admin_action_form((int)$user['id'], 'desbloquear', 'Desbloquear', 'btn-success'); ?>
                                        <?php endif; ?>

                                        <?php echo admin_action_form((int)$user['id'], 'arquivar', 'Arquivar', 'btn-danger'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

</body>
</html>