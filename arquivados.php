<?php
session_start();
require_once 'backend/conexao.php';

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$mensagem = '';

// --- LÓGICA PARA RESTAURAR (DESARQUIVAR) ---
if (isset($_GET['acao']) && isset($_GET['id'])) {
    $id_alvo = (int)$_GET['id'];
    $acao = $_GET['acao'];

    try {
        if ($acao === 'desarquivar') {
            // 1. Busca o usuário nos arquivados
            $stmtBusca = $pdo->prepare("SELECT * FROM usuarios_arquivados WHERE id = ?");
            $stmtBusca->execute([$id_alvo]);
            $userArq = $stmtBusca->fetch(PDO::FETCH_ASSOC);

            if ($userArq) {
                $pdo->beginTransaction();
                
                // 2. Insere ele de volta na tabela principal de usuários (com status ativo)
                $stmtInsert = $pdo->prepare("INSERT INTO usuarios (cpf_cnpj, email, senha, tipo_conta, status) VALUES (?, ?, ?, ?, 'ativo')");
                $stmtInsert->execute([
                    $userArq['cpf_cnpj'], 
                    $userArq['email'], 
                    $userArq['senha'], 
                    $userArq['tipo_conta']
                ]);
                
                // 3. Deleta da tabela de arquivados
                $stmtDel = $pdo->prepare("DELETE FROM usuarios_arquivados WHERE id = ?");
                $stmtDel->execute([$id_alvo]);
                
                $pdo->commit();
                $mensagem = "<div class='alert alert-success'>Usuário restaurado com sucesso! O acesso dele foi liberado.</div>";
            } else {
                $mensagem = "<div class='alert alert-error'>Usuário não encontrado nos arquivos.</div>";
            }
        }
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $mensagem = "<div class='alert alert-error'>Erro ao restaurar ação: " . $e->getMessage() . "</div>";
    }
}

// --- BUSCAR DADOS PARA A TABELA DE ARQUIVADOS ---
try {
    $stmtArquivados = $pdo->query("SELECT id, cpf_cnpj, email, tipo_conta, data_arquivamento FROM usuarios_arquivados ORDER BY id DESC");
    $listaArquivados = $stmtArquivados->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Erro ao carregar dados para a tabela: " . $e->getMessage());
}
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
                <h1 id="pageTitle">Contas Arquivadas (Restrição LGPD)</h1>
                <div class="user-info">
                    <span id="userName">Olá, Administrador!</span>
                    <i class="fa-solid fa-user-circle user-icon"></i>
                </div>
            </header>

            <?php echo $mensagem; ?>

            <section class="recent-activity card-panel">
                <p style="color: #666; margin-bottom: 20px;">Estes usuários tiveram suas contas encerradas e bloqueadas. Clicar em "Restaurar" os devolverá à plataforma, permitindo que façam login novamente.</p>
                
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
                                    <td><?php echo htmlspecialchars(ucfirst($user['tipo_conta'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['data_arquivamento'])); ?></td>
                                    <td>
                                        <a href="arquivados.php?acao=desarquivar&id=<?php echo $user['id']; ?>" class="btn-acao btn-success" onclick="return confirm('Tem certeza que deseja RESTAURAR este usuário? O login dele voltará a funcionar.')"><i class="fa-solid fa-rotate-left"></i> Restaurar</a>
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