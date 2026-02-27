<?php
// dashboard_contratante.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'contratante') {
    header('Location: login.php');
    exit;
}

require_once 'backend/conexao.php';
$user_id = $_SESSION['user_id'];

// Busca dados do perfil
$stmt = $pdo->prepare("SELECT u.email, e.nome AS nome_empresa FROM usuarios u JOIN empresas e ON u.id = e.usuario_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$dados_bd = $stmt->fetch(PDO::FETCH_ASSOC);
$nome_usuario = $dados_bd ? htmlspecialchars($dados_bd['nome_empresa']) : 'Contratante';

// Busca lista de Terceirizadas para o FEED
$stmtTerceirizadas = $pdo->prepare("
    SELECT u.id as usuario_id, e.nome AS nome_empresa, e.descricao AS descricao_servicos, e.regiao AS regioes_atendidas
    FROM usuarios u JOIN empresas e ON u.id = e.usuario_id
    WHERE u.tipo_conta = 'empresa' AND e.tipo_empresa = 'terceirizada'
");
$stmtTerceirizadas->execute();
$terceirizadas = $stmtTerceirizadas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiConnect | Área do Contratante</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/contratante.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-header contratante-header">
        <div class="header-container">
            <h1 class="logo-title">ServiConnect <span class="badge-role">Contratante</span></h1>
            <div class="user-control">
                <span class="user-display">Olá, <?php echo $nome_usuario; ?></span>
                <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
            </div>
        </div>
    </div>

    <main class="main-content-dashboard">
        <section class="content-section active-section">
            <h2 class="section-title"><i class="fa-solid fa-magnifying-glass"></i> Encontre o Serviço Perfeito</h2>
            <div class="search-module widget-card">
                <p class="search-tip">Empresas terceirizadas disponíveis para contratação:</p>
                <div class="terceirizadas-list">
                    <?php if (empty($terceirizadas)): ?>
                        <p>Nenhuma empresa terceirizada encontrada no momento.</p>
                    <?php else: ?>
                        <?php foreach ($terceirizadas as $t): ?>
                            <div class="terceirizada-card widget-card">
                                <div class="profile-info">
                                    <img src="img/default_avatar.png" alt="Logo" class="terceirizada-logo">
                                    <div class="text-info">
                                        <h4><?php echo htmlspecialchars($t['nome_empresa']); ?></h4>
                                        <p class="service-area"><i class="fa-solid fa-location-dot"></i> Região: <?php echo htmlspecialchars($t['regioes_atendidas']); ?></p>
                                        <p class="description-short"><?php echo substr(htmlspecialchars($t['descricao_servicos']), 0, 100); ?>...</p>
                                    </div>
                                </div>
                                <a href="solicitacao_orcamento.php?terceirizada_id=<?php echo $t['usuario_id']; ?>" class="btn-primary request-btn contratante-btn">
                                    <i class="fa-solid fa-comments-dollar"></i> Solicitar Orçamento
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>