<?php
// backend/controllers/SolicitacaoController.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
session_start();

require_once '../conexao.php';

// ⚠️ SEGURANÇA: Verifica se a requisição é válida e o usuário é Contratante
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['user_role'] !== 'contratante') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado ou método inválido.']);
    exit;
}

$dados = $_POST; 

$contratante_id = $dados['contratante_id'] ?? null;
$terceirizada_id = $dados['terceirizada_id'] ?? null; 

// Validação de segurança básica do servidor
if (empty($contratante_id) || empty($dados['descricao_servico']) || empty($terceirizada_id) || empty($dados['numero_funcionarios']) || empty($dados['area_servico_solicitada'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios (funcionários, área e descrição) estão faltando.']);
    exit;
}

try {
    // 1. Sanitização
    $descricao = filter_var($dados['descricao_servico'], FILTER_SANITIZE_STRING);
    $localizacao = filter_var($dados['localizacao_servico'], FILTER_SANITIZE_STRING);
    $num_func = (int)$dados['numero_funcionarios'];
    $area_servico = filter_var($dados['area_servico_solicitada'], FILTER_SANITIZE_STRING);


    // 2. Query de Inserção (COM OS NOVOS CAMPOS)
    $sql = "INSERT INTO solicitacoes (contratante_id, terceirizada_id, descricao_servico, localizacao_servico, numero_funcionarios, area_servico_solicitada, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'aberta')";
    
    $stmt = $pdo->prepare($sql);
    
    // 3. Execução
    $stmt->execute([$contratante_id, $terceirizada_id, $descricao, $localizacao, $num_func, $area_servico]);

    // Redireciona o Contratante de volta para o dashboard com uma mensagem de sucesso
    header('Location: ../../dashboard.php?status=solicitacao_enviada');
    exit;

} catch (\PDOException $e) {
    error_log("Erro ao salvar solicitação: " . $e->getMessage());
    // Em caso de erro, redireciona para o dashboard com uma mensagem de falha
    header('Location: ../../dashboard.php?status=solicitacao_erro');
    exit;
}