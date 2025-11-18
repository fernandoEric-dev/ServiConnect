<?php
// responder_orcamento.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'terceirizada') {
    header('Location: login.php');
    exit;
}

$terceirizada_id = $_SESSION['user_id'];
$solicitacao_id = $_GET['id'] ?? null;

if (!$solicitacao_id || !is_numeric($solicitacao_id)) {
    header('Location: dashboard.php?status=solicitacao_invalida');
    exit;
}

require_once 'backend/conexao.php';

// Busca os detalhes da solicitação
$sql = "
    SELECT 
        s.*, 
        e.nome AS nome_contratante,
        u.email AS email_contratante
    FROM solicitacoes s
    JOIN empresas e ON s.contratante_id = e.usuario_id
    JOIN usuarios u ON s.contratante_id = u.id
    WHERE s.id = ? AND s.terceirizada_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$solicitacao_id, $terceirizada_id]);
$solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitacao) {
    header('Location: dashboard.php?status=nao_autorizado');
    exit;
}

// Verifica se já foi respondida
$status_respondida = ($solicitacao['status'] !== 'aberta');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Responder Orçamento #<?php echo htmlspecialchars($solicitacao_id); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css"> 
</head>
<body>
    <div class="dashboard-box" style="margin-top: 50px;">
        <h2 class="section-title">Responder Solicitação de Orçamento #<?php echo htmlspecialchars($solicitacao_id); ?></h2>
        
        <div class="widget-card" style="margin-bottom: 20px;">
            <h3>Detalhes do Pedido</h3>
            <p><strong>Contratante:</strong> <?php echo htmlspecialchars($solicitacao['nome_contratante']); ?> (<?php echo htmlspecialchars($solicitacao['email_contratante']); ?>)</p>
            <p><strong>Área Solicitada:</strong> <?php echo htmlspecialchars($solicitacao['area_servico_solicitada']); ?></p>
            <p><strong>Funcionários Desejados:</strong> <?php echo htmlspecialchars($solicitacao['numero_funcionarios']); ?></p>
            <p><strong>Localização:</strong> <?php echo htmlspecialchars($solicitacao['localizacao_servico']); ?></p>
            <p><strong>Descrição:</strong> <br><?php echo nl2br(htmlspecialchars($solicitacao['descricao_servico'])); ?></p>
            <p><strong>Status Atual:</strong> <span style="font-weight: bold; color: <?php echo $status_respondida ? 'blue' : 'red'; ?>"><?php echo htmlspecialchars(ucfirst($solicitacao['status'])); ?></span></p>

            <?php if ($solicitacao['status'] === 'orçada'): ?>
                <p><strong>Valor Orçado:</strong> R$ <?php echo number_format($solicitacao['valor_orcamento'], 2, ',', '.'); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($solicitacao['status'] === 'aberta'): ?>
            <h3 class="section-title">Ação da Terceirizada</h3>
            
            <form action="backend/controllers/RespostaOrcamentoController.php" method="POST" class="widget-card">
                <input type="hidden" name="solicitacao_id" value="<?php echo $solicitacao_id; ?>">
                
                <p>O que você deseja fazer com esta solicitação?</p>

                <div>
                    <label for="valor_orcamento">Valor do Orçamento (R$):</label>
                    <input type="number" id="valor_orcamento" name="valor_orcamento" min="0" step="0.01" placeholder="Informe o valor total. Ex: 5500.00">
                    <small>Deixe em branco se for recusar.</small>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" name="action" value="orcamento" class="btn-primary search-btn" style="flex: 1;">
                        <i class="fa-solid fa-money-bill-wave"></i> Enviar Orçamento
                    </button>
                    <button type="submit" name="action" value="recusa" class="btn-secondary" style="flex: 1;" onclick="return confirm('Tem certeza que deseja recusar este orçamento? Essa ação não pode ser desfeita.');">
                        <i class="fa-solid fa-times-circle"></i> Recusar Solicitação
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">Esta solicitação já foi respondida e está com status: **<?php echo htmlspecialchars(ucfirst($solicitacao['status'])); ?>**.</div>
        <?php endif; ?>

        <div style="margin-top: 15px; text-align: center;">
            <a href="dashboard.php" class="btn-link">Voltar para a Lista de Solicitações</a>
        </div>
    </div>
</body>
</html>