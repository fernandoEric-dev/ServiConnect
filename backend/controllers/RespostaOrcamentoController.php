<?php
// backend/controllers/RespostaOrcamentoController.php

session_start();

require_once '../conexao.php';

// ⚠️ SEGURANÇA: Verifica se o usuário é Terceirizada
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user_role'] !== 'terceirizada') {
    header('Location: ../../login.php');
    exit;
}

$solicitacao_id = $_POST['solicitacao_id'] ?? null;
$action = $_POST['action'] ?? null; // 'orcamento' ou 'recusa'
$valor_orcamento = $_POST['valor_orcamento'] ?? null;

if (!$solicitacao_id || !is_numeric($solicitacao_id) || empty($action)) {
    header('Location: ../../dashboard.php?status=dados_invalidos');
    exit;
}

try {
    // 1. Prepara a base da query
    $sql = "UPDATE solicitacoes SET status = ?, valor_orcamento = ? WHERE id = ? AND terceirizada_id = ?";
    $params = [];
    $terceirizada_id = $_SESSION['user_id'];

    // 2. Determina a Ação (Orçar ou Recusar)
    if ($action === 'orcamento') {
        // Se orçar, deve haver um valor
        if (!is_numeric($valor_orcamento) || $valor_orcamento <= 0) {
            header('Location: ../../responder_orcamento.php?id=' . $solicitacao_id . '&status=valor_ausente');
            exit;
        }
        $status = 'orçada';
        $params = [$status, $valor_orcamento, $solicitacao_id, $terceirizada_id];
        $msg = 'orcamento_enviado';
        
    } elseif ($action === 'recusa') {
        // Se recusar, o valor é nulo (ou zero)
        $status = 'recusada';
        $params = [$status, null, $solicitacao_id, $terceirizada_id];
        $msg = 'solicitacao_recusada';
        
    } else {
        // Ação inválida
        header('Location: ../../dashboard.php?status=acao_invalida');
        exit;
    }

    // 3. Executa o UPDATE
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // 4. Redireciona com sucesso
    header('Location: ../../dashboard.php?status=' . $msg);
    exit;

} catch (\PDOException $e) {
    error_log("Erro ao responder solicitação: " . $e->getMessage());
    header('Location: ../../dashboard.php?status=erro_banco');
    exit;
}