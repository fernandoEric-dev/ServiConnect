<?php
session_start();
require_once 'backend/conexao.php';

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$mensagem = '';

// --- LÓGICA PARA ARQUIVAR OU BLOQUEAR ---
if (isset($_GET['acao']) && isset($_GET['id'])) {
    $id_alvo = (int)$_GET['id'];
    $acao = $_GET['acao'];

    try {
        if ($acao === 'arquivar') {
            // Busca o usuário
            $stmtBusca = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmtBusca->execute([$id_alvo]);
            $user = $stmtBusca->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Inicia uma transação para garantir que tudo seja feito com segurança
                $pdo->beginTransaction();

                // 1. Insere na tabela de arquivados
                $stmtArq = $pdo->prepare("INSERT INTO usuarios_arquivados (id_original, cpf_cnpj, email, senha, tipo_conta) VALUES (?, ?, ?, ?, ?)");
                $stmtArq->execute([$user['id'], $user['cpf_cnpj'], $user['email'], $user['senha'], $user['tipo_conta']]);

                // 2. Remove o vínculo da empresa (para não dar erro de Chave Estrangeira)
                $stmtDelEmpresa = $pdo->prepare("DELETE FROM empresas WHERE usuario_id = ?");
                $stmtDelEmpresa->execute([$id_alvo]);

                // 3. Deleta o usuário da tabela ativa
                $stmtDelUser = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmtDelUser->execute([$id_alvo]);

                // Confirma as exclusões
                $pdo->commit();
                $mensagem = "<div class='alert success'>Usuário movido para os Arquivados com sucesso (LGPD)!</div>";
            } else {
                $mensagem = "<div class='alert error'>Usuário não encontrado.</div>";
            }

        } elseif ($acao === 'bloquear') {
            $stmt = $pdo->prepare("UPDATE usuarios SET status = 'bloqueado' WHERE id = ?");
            $stmt->execute([$id_alvo]);
            $mensagem = "<div class='alert warning'>Usuário bloqueado com sucesso!</div>";
        } elseif ($acao === 'desbloquear') {
            $stmt = $pdo->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?");
            $stmt->execute([$id_alvo]);
            $mensagem = "<div class='alert success'>Usuário desbloqueado com sucesso!</div>";
        }
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // Desfaz se algo der errado
        }
        $mensagem = "<div class='alert error'>Erro ao executar ação: " . $e->getMessage() . "</div>";
    }
}

// --- BUSCAR DADOS PARA O DASHBOARD ---
try {
    // Conta usuários ativos
    $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    // Conta empresas
    $totalEmpresas = $pdo->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
    // Conta arquivados/banidos
    $totalArquivados = $pdo->query("SELECT COUNT(*) FROM usuarios_arquivados")->fetchColumn();

    // Busca a lista para a tabela
    $stmtUsuarios = $pdo->query("SELECT id, cpf_cnpj, email, tipo_conta, status FROM usuarios ORDER BY id DESC");
    $listaUsuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Erro ao carregar dados. Verifique a conexão com o banco.");
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
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; text-align: left;}
        .admin-table th, .admin-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .admin-table th { background-color: #f4f6f9; color: #333; font-weight: bold; }
        .btn-acao { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; font-size: 13px; margin-right: 5px; display: inline-block;}
        .btn-danger { background-color: #6c757d; } 
        .btn-warning { background-color: #ffc107; color: #000; }
        .btn-success { background-color: #28a745; }
        .status-ativo { color: #28a745; font-weight: bold; }
        .status-bloqueado { color: #dc3545; font-weight: bold; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert.success { background-color: #d4edda; color: #155724; }
        .alert.warning { background-color: #fff3cd; color: #856404; }
        .alert.error { background-color: #f8d7da; color: #721c24; }
        
        /* Estilos para os cards do dashboard */
        .dashboard-widgets { display: flex; gap: 20px; margin-bottom: 30px; }
        .widget { flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; }
        .widget h4 { margin: 10px 0 5px; color: #666; font-size: 14px; }
        .widget p { margin: 0; font-size: 24px; font-weight: bold; color: #004b87; }
        .icon-widget { font-size: 30px; color: #004b87; }
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
                    <li class="nav-item active"><a href="#"><i class="fa-solid fa-users"></i> Gestão de Usuários</a></li>
                    <li class="nav-item logout-link"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1 id="pageTitle">Gestão de Usuários</h1>
                <div class="user-info">
                    <span id="userName">Olá, Administrador!</span>
                    <i class="fa-solid fa-user-circle user-icon"></i>
                </div>
            </header>

            <section class="dashboard-widgets">
                <div class="widget">
                    <i class="fa-solid fa-users icon-widget"></i>
                    <h4>Total de Usuários Ativos</h4>
                    <p><?php echo $totalUsuarios; ?></p>
                </div>
                <div class="widget">
                    <i class="fa-solid fa-building icon-widget"></i>
                    <h4>Empresas Registradas</h4>
                    <p><?php echo $totalEmpresas; ?></p>
                </div>
                <div class="widget">
                    <i class="fa-solid fa-box-archive icon-widget" style="color: #6c757d;"></i>
                    <h4>Contas Arquivadas/Banidas</h4>
                    <p><?php echo $totalArquivados; ?></p>
                </div>
            </section>

            <section class="card-panel">
                <?php echo $mensagem; ?>

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
                                    <td><?php echo htmlspecialchars(ucfirst($user['tipo_conta'])); ?></td>
                                    <td class="<?php echo $user['status'] === 'bloqueado' ? 'status-bloqueado' : 'status-ativo'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['status'] ?? 'ativo')); ?>
                                    </td>
                                    <td>
                                        <?php if (!isset($user['status']) || $user['status'] !== 'bloqueado'): ?>
                                            <a href="adm.php?acao=bloquear&id=<?php echo $user['id']; ?>" class="btn-acao btn-warning" onclick="return confirm('Bloquear este usuário?')">Bloquear</a>
                                        <?php else: ?>
                                            <a href="adm.php?acao=desbloquear&id=<?php echo $user['id']; ?>" class="btn-acao btn-success">Desbloquear</a>
                                        <?php endif; ?>

                                        <a href="adm.php?acao=arquivar&id=<?php echo $user['id']; ?>" class="btn-acao btn-danger" onclick="return confirm('Tem certeza que deseja ARQUIVAR este usuário? O login será desativado e os dados guardados (LGPD).')"><i class="fa-solid fa-box-archive"></i> Arquivar</a>
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