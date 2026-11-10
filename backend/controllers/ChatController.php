<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../chat.php';

$viewer = chat_require_user($pdo);
$method = $_SERVER['REQUEST_METHOD'] ?? '';
$json = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    abort_request(405, 'Método não permitido.');
}

if ($method === 'GET') {
    $id = chat_id($_GET['id'] ?? null);
    $after = chat_id($_GET['depois'] ?? null);
    session_write_close();
    try {
        chat_context($pdo, $viewer, $id, 0);
        $result = chat_messages($pdo, $id, $viewer['id'], 0, $after);
    } catch (Throwable $e) {
        $problem = chat_problem($e);
        abort_request($problem->status, $problem->getMessage());
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

require_csrf();
$id = chat_id($_POST['conversa_id'] ?? null);
$provider = chat_id($_POST['prestador_id'] ?? null);
try {
    $saved = chat_send($pdo, $viewer, $id, $provider, $_POST);
} catch (Throwable $e) {
    $problem = chat_problem($e);
    if ($json || in_array($problem->status, [403, 404, 503], true)) abort_request($problem->status, $problem->getMessage());
    $_SESSION['chat_feedback'] = [
        'target' => $id ? 'id=' . $id : 'prestador=' . $provider,
        'message' => $problem->getMessage(),
        'data' => array_intersect_key($_POST, array_flip([
            'acao', 'chave_envio', 'mensagem', 'descricao_servico', 'area_servico_solicitada', 'localizacao_servico',
            'valor_orcamento', 'prazo_execucao', 'condicoes_pagamento', 'itens_inclusos', 'itens_exclusos',
            'mensagem_proposta', 'validade_proposta',
        ])),
    ];
    header('Location: /conversas.php?' . ($id ? 'id=' . $id : 'prestador=' . $provider), true, 303);
    exit;
}

if ($json) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['id' => $saved, 'next_key' => bin2hex(random_bytes(16))], JSON_THROW_ON_ERROR);
} else {
    header('Location: /conversas.php?id=' . $saved . '#mensagens', true, 303);
}
