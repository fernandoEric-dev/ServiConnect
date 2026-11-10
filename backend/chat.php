<?php
require_once __DIR__ . '/app.php';

function chat_id($value): int {
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
}

function chat_require_user(PDO $pdo): array {
    header('Cache-Control: no-store, private');
    if (empty($_SESSION['user_id']) && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        abort_request(401, 'Entre novamente para continuar a conversa.');
    }
    require_role($pdo, ['contratante', 'prestador']);
    return ['id' => (int)$_SESSION['user_id'], 'role' => $_SESSION['user_role']];
}

function chat_columns(array $viewer): array {
    return $viewer['role'] === 'contratante' ? ['contratante_id', 'prestador_id'] : ['prestador_id', 'contratante_id'];
}

function chat_context(PDO $pdo, array $viewer, int $id, int $provider): array {
    [$ownColumn, $peerColumn] = chat_columns($viewer);
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM chat_conversas WHERE id=? AND $ownColumn=?");
        $stmt->execute([$id, $viewer['id']]);
        $conversation = $stmt->fetch();
        if (!$conversation) throw new OrderProblem('Conversa indisponível.', 404);
    } elseif ($viewer['role'] === 'contratante' && $provider && $provider !== $viewer['id']) {
        $stmt = $pdo->prepare('SELECT * FROM chat_conversas WHERE contratante_id=? AND prestador_id=?');
        $stmt->execute([$viewer['id'], $provider]);
        $conversation = $stmt->fetch() ?: ['id' => 0, 'contratante_id' => $viewer['id'], 'prestador_id' => $provider];
    } else {
        throw new OrderProblem('Conversa indisponível.', 404);
    }

    $peerId = (int)$conversation[$peerColumn];
    $stmt = $pdo->prepare('SELECT u.status, e.nome, e.tipo_empresa, e.foto_path FROM usuarios u JOIN empresas e ON e.usuario_id=u.id WHERE u.id=?');
    $stmt->execute([$peerId]);
    $peer = $stmt->fetch();
    $expectedRole = $viewer['role'] === 'contratante' ? 'prestador' : 'contratante';
    $available = $peer && $peer['status'] === 'ativo' && account_role($peer['tipo_empresa']) === $expectedRole;
    if (!$available && !$conversation['id']) throw new OrderProblem('Prestador indisponível.', 404);

    return $conversation + [
        'peer_id' => $peerId,
        'peer_name' => $peer['nome'] ?? 'Perfil indisponível',
        'peer_photo' => $peer['foto_path'] ?? null,
        'peer_available' => $available,
    ];
}

function chat_inbox(PDO $pdo, array $viewer, int $before): array {
    [$ownColumn, $peerColumn] = chat_columns($viewer);
    $cursor = $before ? ' AND c.ultima_mensagem_id < ?' : '';
    $stmt = $pdo->prepare("SELECT c.id, m.id AS mensagem_id, m.mensagem, m.criado_em, m.autor_id,
        COALESCE(e.nome, 'Perfil indisponível') AS nome, e.foto_path
        FROM chat_conversas c
        JOIN chat_mensagens m ON m.id=c.ultima_mensagem_id AND m.conversa_id=c.id
        LEFT JOIN empresas e ON e.usuario_id=c.$peerColumn
        WHERE c.$ownColumn=?$cursor ORDER BY c.ultima_mensagem_id DESC LIMIT 21");
    $stmt->execute($before ? [$viewer['id'], $before] : [$viewer['id']]);
    return $stmt->fetchAll();
}

function chat_messages(PDO $pdo, int $id, int $viewerId, int $before = 0, ?int $after = null): array {
    $cursor = $after ?? $before;
    $condition = $after !== null ? ' AND id > ?' : ($before ? ' AND id < ?' : '');
    $direction = $after !== null ? 'ASC' : 'DESC';
    $stmt = $pdo->prepare("SELECT id, autor_id, mensagem, solicitacao_id, criado_em FROM chat_mensagens
        WHERE conversa_id=?$condition ORDER BY id $direction LIMIT 41");
    $stmt->execute($condition ? [$id, $cursor] : [$id]);
    $rows = $stmt->fetchAll();
    $more = count($rows) > 40;
    $rows = array_slice($rows, 0, 40);
    if ($after === null) $rows = array_reverse($rows);

    return ['more' => $more, 'messages' => array_map(static function (array $row) use ($viewerId): array {
        return [
            'id' => (string)$row['id'],
            'mine' => (int)$row['autor_id'] === $viewerId,
            'text' => $row['mensagem'],
            'order' => (int)$row['solicitacao_id'],
            'time' => app_date($row['criado_em'], true),
            'datetime' => str_replace(' ', 'T', $row['criado_em']) . 'Z',
        ];
    }, $rows)];
}

function chat_create_proposal(PDO $pdo, array $conversation, array $data): int {
    $description = order_text($data, 'descricao_servico', 10000, true);
    $service = order_text($data, 'area_servico_solicitada', 100, true);
    $location = order_text($data, 'localizacao_servico', 255);
    $proposal = order_proposal_fields($data);
    if (!$proposal['prazo_execucao'] || !$proposal['condicoes_pagamento'] || !$proposal['itens_inclusos'] || !$proposal['validade_proposta']) {
        throw new OrderProblem('Preencha o prazo, a validade, as condições de pagamento e os itens incluídos.');
    }

    $stmt = $pdo->prepare("INSERT INTO solicitacoes
        (contratante_id, terceirizada_id, descricao_servico, area_servico_solicitada, localizacao_servico,
         valor_orcamento, prazo_execucao, condicoes_pagamento, itens_inclusos, itens_exclusos, mensagem_proposta,
         validade_proposta, status, atualizada_em)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'orçada',NOW())");
    $stmt->execute(array_merge([
        $conversation['contratante_id'], $conversation['prestador_id'], $description, $service, $location ?: null,
    ], array_values($proposal)));
    $id = (int)$pdo->lastInsertId();
    $order = order_fetch($pdo, $id, (int)$conversation['prestador_id']);
    $order['status'] = null;
    order_event($pdo, $order, (int)$conversation['prestador_id'], 'enviar_proposta', 'orçada', 'Proposta enviada pela conversa.', $proposal);
    return $id;
}

function chat_send(PDO $pdo, array $viewer, int $id, int $provider, array $data): int {
    $action = $data['acao'] ?? 'mensagem';
    if (!in_array($action, ['mensagem', 'proposta'], true)) throw new OrderProblem('Ação inválida.');
    if ($action === 'proposta' && ($viewer['role'] !== 'prestador' || !$id)) {
        throw new OrderProblem('Apenas o prestador da conversa pode enviar uma proposta.', 403);
    }
    $key = $data['chave_envio'] ?? '';
    if (!is_string($key) || !preg_match('/^[a-f0-9]{32}$/D', $key)) throw new OrderProblem('Atualize a conversa e tente novamente.');
    $message = $action === 'mensagem' ? order_text($data, 'mensagem', 2000, true) : 'Proposta de serviço enviada.';

    $conversation = chat_context($pdo, $viewer, $id, $provider);
    $id = (int)$conversation['id'];
    if (!$conversation['peer_available']) throw new OrderProblem('A outra conta está indisponível para receber mensagens.', 409);

    $pdo->beginTransaction();
    try {
        if (!$id) {
            $stmt = $pdo->prepare('INSERT INTO chat_conversas (contratante_id, prestador_id) VALUES (?,?)
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
            $stmt->execute([$conversation['contratante_id'], $conversation['prestador_id']]);
            $id = (int)$pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare('SELECT id FROM chat_conversas WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);
            if (!$stmt->fetchColumn()) throw new OrderProblem('Conversa indisponível.', 404);
        }

        $stmt = $pdo->prepare('SELECT id FROM chat_mensagens WHERE conversa_id=? AND autor_id=? AND chave_envio=?');
        $stmt->execute([$id, $viewer['id'], $key]);
        if (!$stmt->fetchColumn()) {
            $stmt = $pdo->prepare('SELECT id FROM chat_mensagens WHERE conversa_id=? AND autor_id=?
                AND criado_em >= NOW() - INTERVAL 1 MINUTE LIMIT 12');
            $stmt->execute([$id, $viewer['id']]);
            if (count($stmt->fetchAll()) >= 12) throw new OrderProblem('Aguarde um pouco antes de enviar outra mensagem.', 429);

            $orderId = $action === 'proposta' ? chat_create_proposal($pdo, $conversation, $data) : null;
            $stmt = $pdo->prepare('INSERT INTO chat_mensagens (conversa_id, autor_id, mensagem, solicitacao_id, chave_envio) VALUES (?,?,?,?,?)');
            $stmt->execute([$id, $viewer['id'], $message, $orderId, $key]);
            $messageId = $pdo->lastInsertId();
            $pdo->prepare('UPDATE chat_conversas SET ultima_mensagem_id=? WHERE id=?')->execute([$messageId, $id]);
        }
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function chat_problem(Throwable $error): OrderProblem {
    if ($error instanceof OrderProblem) return $error;
    error_log('Chat: ' . $error->getMessage());
    return new OrderProblem('O chat está temporariamente indisponível. Tente novamente em instantes.', 503);
}
