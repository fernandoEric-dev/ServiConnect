<?php
session_start();
require_once 'backend/conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_arquivados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_original INT NOT NULL,
        cpf_cnpj VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        senha VARCHAR(255) NOT NULL,
        tipo_conta VARCHAR(50) NOT NULL,
        data_arquivamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $checkStatus = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'status'")->rowCount();
    if ($checkStatus == 0) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN status VARCHAR(20) DEFAULT 'ativo'");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS solicitacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contratante_id INT,
        terceirizada_id INT,
        descricao_servico TEXT,
        localizacao_servico VARCHAR(255),
        numero_funcionarios INT,
        area_servico_solicitada VARCHAR(255),
        status VARCHAR(50) DEFAULT 'aberta'
    )");

} catch (\PDOException $e) {
    die("Erro de configuracao automatica: " . $e->getMessage());
}

$mensagem = '';

if (isset($_GET['acao']) && isset($_GET['id'])) {
    $id_alvo = (int)$_GET['id'];
    $acao = $_GET['acao'];

    try {
        if ($acao === 'arquivar') {
            $stmtBusca = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmtBusca->execute([$id_alvo]);
            $user = $stmtBusca->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $pdo->beginTransaction();
                
                $stmtArq = $pdo->prepare("INSERT INTO usuarios_arquivados (id_original, cpf_cnpj, email, senha, tipo_conta) VALUES (?, ?, ?, ?, ?)");
                $stmtArq->execute([$user['id'], $user['cpf_cnpj'], $user['email'], $user['senha'], $user['tipo_conta']]);
                
                $stmtDelEmpresa = $pdo->prepare("DELETE FROM empresas WHERE usuario_id = ?");
                $stmtDelEmpresa->execute([$id_alvo]);
                
                $stmtDelUser = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmtDelUser->execute([$id_alvo]);
                
                $pdo->commit();
                $mensagem = "<div class='alert alert-success'>Usuário arquivado com sucesso (LGPD)!</div>";
            }
        } elseif ($acao === 'bloquear') {
            $stmt = $pdo->prepare("UPDATE usuarios SET status = 'bloqueado' WHERE id = ?");
            $stmt->execute([$id_alvo]);
            $mensagem = "<div class='alert alert-warning'>Usuário bloqueado com sucesso!</div>";
        } elseif ($acao === 'desbloquear') {
            $stmt = $pdo->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?");
            $stmt->execute([$id_alvo]);
            $mensagem = "<div class='alert alert-success'>Usuário desbloqueado com sucesso!</div>";
        }
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $mensagem = "<div class='alert alert-error'>Erro ao executar ação: " . $e->getMessage() . "</div>";
    }
}

try {
    $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $totalEmpresas = $pdo->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
    $totalPedidos = $pdo->query("SELECT COUNT(*) FROM solicitacoes")->fetchColumn();
    $totalArquivados = $pdo->query("SELECT COUNT(*) FROM usuarios_arquivados")->fetchColumn();
    
    $stmtUsuarios = $pdo->query("SELECT id, cpf_cnpj, email, tipo_conta, status FROM usuarios ORDER BY id DESC");
    $listaUsuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Erro ao carregar dados para a tabela: " . $e->getMessage());
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
                                    <td><?php echo htmlspecialchars(ucfirst($user['tipo_conta'])); ?></td>
                                    <td class="<?php echo $user['status'] === 'bloqueado' ? 'status-bloqueado' : 'status-ativo'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['status'] ?? 'ativo')); ?>
                                    </td>
                                    <td>
                                        <?php if (!isset($user['status']) || $user['status'] !== 'bloqueado'): ?>
                                            <a href="admin.php?acao=bloquear&id=<?php echo $user['id']; ?>" class="btn-acao btn-warning" onclick="return confirm('Bloquear este usuário?')">Bloquear</a>
                                        <?php else: ?>
                                            <a href="admin.php?acao=desbloquear&id=<?php echo $user['id']; ?>" class="btn-acao btn-success">Desbloquear</a>
                                        <?php endif; ?>

                                        <a href="admin.php?acao=arquivar&id=<?php echo $user['id']; ?>" class="btn-acao btn-danger" onclick="return confirm('Tem certeza que deseja ARQUIVAR este usuário? O login será desativado e os dados guardados (LGPD).')"><i class="fa-solid fa-box-archive"></i> Arquivar</a>
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