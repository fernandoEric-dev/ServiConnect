<?php
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo, ['admin']);
require_once __DIR__ . '/backend/admin_actions.php';
$listaArquivados = $pdo->query("SELECT u.id,u.cpf_cnpj,u.email,u.tipo_conta,u.tipo_pessoa,u.arquivado_em AS data_arquivamento,e.tipo_empresa FROM usuarios u LEFT JOIN empresas e ON e.usuario_id=u.id WHERE u.status='arquivado' ORDER BY u.id DESC")->fetchAll();
$legacyCount = (int)$pdo->query('SELECT COUNT(*) FROM usuarios_arquivados')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiConnect | Usuários Arquivados</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; text-align: left; }
        .admin-table th, .admin-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .admin-table th { background-color: #f4f6f9; color: #333; font-weight: bold; }
        .btn-acao { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; font-size: 13px; margin-right: 5px; display: inline-block; }
        .btn-success { background-color: #28a745; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; }
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
                    <li><a href="admin.php"><i class="fa-solid fa-gauge"></i> Dashboard & Gestão</a></li>
                    <li class="nav-item active"><a href="#"><i class="fa-solid fa-box-archive"></i> Usuarios Arquivados</a></li>
                    <li class="nav-item logout-link"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1 id="pageTitle">Contas Arquivadas (Acesso inativo)</h1>
                <div class="user-info">
                    <span id="userName">Olá, Administrador!</span>
                    <i class="fa-solid fa-user-circle user-icon"></i>
                </div>
            </header>

            <?php echo $mensagem; ?>

            <section class="recent-activity card-panel">
                <p style="color: #666; margin-bottom: 20px;">Estes usuários tiveram suas contas encerradas e bloqueadas. Clicar em "Restaurar" os devolverá à plataforma, permitindo que façam login novamente.</p>

                <?php if ($legacyCount): ?><p>Existem <?php echo $legacyCount; ?> registros do arquivamento antigo. Os perfis já excluídos exigem recuperação por backup; esta tela restaura os novos arquivamentos preservados.</p><?php endif; ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID (Arquivo)</th>
                            <th>CNPJ/CPF</th>
                            <th>E-mail</th>
                            <th>Tipo</th>
                            <th>Data do Arquivo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($listaArquivados)): ?>
                            <tr><td colspan="6" style="text-align:center;">Nenhum usuário arquivado no momento.</td></tr>
                        <?php else: ?>
                            <?php foreach ($listaArquivados as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['cpf_cnpj']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars(account_type_label($user['tipo_empresa'] ?? $user['tipo_conta'], $user['tipo_pessoa'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['data_arquivamento'])); ?></td>
                                    <td>
                                        <?php echo admin_action_form((int)$user['id'], 'desarquivar', 'Restaurar', 'btn-success'); ?>
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