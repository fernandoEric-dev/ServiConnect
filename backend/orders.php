<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/catalog.php';

class OrderProblem extends RuntimeException {
    public int $status;
    public function __construct(string $message, int $status = 422) { parent::__construct($message); $this->status = $status; }
}
function order_escape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function order_statuses(): array {
    return ['aberta'=>'Aguardando proposta','orçada'=>'Proposta recebida','respondida'=>'Proposta recebida','aceita'=>'Proposta aceita','em_execucao'=>'Em execução','aguardando_confirmacao'=>'Aguardando confirmação','concluida'=>'Concluído','recusada'=>'Recusado pelo prestador','recusada_cliente'=>'Proposta recusada','cancelada'=>'Cancelado','em_negociacao'=>'Em negociação','fechada'=>'Encerrado (anterior)'];
}
function order_status($value): string { return order_statuses()[$value ?? ''] ?? 'Situação anterior não informada'; }
function order_money($value): string {
    if (!is_string($value)) throw new OrderProblem('Informe um valor válido.');
    $value = str_replace(['R$', ' ', "\xc2\xa0"], '', trim($value));
    if (strpos($value, ',') !== false) {
        if (!preg_match('/^(?:\d{1,8}|\d{1,3}(?:\.\d{3}){1,2}),\d{1,2}$/D', $value)) throw new OrderProblem('Use um valor como 150,00 ou 1.250,50.');
        $value = str_replace(',', '.', str_replace('.', '', $value));
    }
    if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/D', $value, $match)) throw new OrderProblem('Informe um valor positivo com até duas casas decimais.');
    $whole = ltrim($match[1], '0') ?: '0'; $cents = str_pad($match[2] ?? '', 2, '0');
    if ($whole === '0' && $cents === '00') throw new OrderProblem('O valor deve ser maior que zero.');
    return $whole . '.' . $cents;
}
function order_brl($value): string { return $value === null ? 'A combinar' : 'R$ ' . number_format((float)$value, 2, ',', '.'); }
function order_text(array $data, string $key, int $max, bool $required = false): string {
    $value = $data[$key] ?? '';
    if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) throw new OrderProblem('Revise o campo informado.');
    $value = trim($value);
    if (($required && $value === '') || mb_strlen($value) > $max) throw new OrderProblem('Revise o campo ' . str_replace('_', ' ', $key) . '.');
    return $value;
}
function order_date(array $data, string $key): ?string {
    $value = order_text($data, $key, 10);
    if ($value === '') return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value || $value < date('Y-m-d')) throw new OrderProblem('Escolha uma data válida a partir de hoje.');
    return $value;
}
function order_expired(array $order): bool { return !empty($order['validade_proposta']) && $order['validade_proposta'] < date('Y-m-d'); }
function order_fetch(PDO $pdo, int $id, int $userId, bool $lock = false): array {
    $stmt = $pdo->prepare('SELECT s.*,c.nome AS nome_contratante,p.nome AS nome_prestador,c.foto_path AS foto_contratante,p.foto_path AS foto_prestador,p.tipo_empresa,uc.status AS status_contratante,up.status AS status_prestador FROM solicitacoes s JOIN empresas c ON c.usuario_id=s.contratante_id LEFT JOIN empresas p ON p.usuario_id=s.terceirizada_id JOIN usuarios uc ON uc.id=s.contratante_id LEFT JOIN usuarios up ON up.id=s.terceirizada_id WHERE s.id=? AND (s.contratante_id=? OR s.terceirizada_id=?)' . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([$id, $userId, $userId]);
    $order = $stmt->fetch();
    if (!$order) throw new OrderProblem('Pedido não encontrado ou indisponível para sua conta.', 404);
    return $order;
}
function order_event(PDO $pdo, array $order, int $actor, string $type, ?string $newStatus, string $message = '', ?array $snapshot = null): void {
    $isClient = (int)$order['contratante_id'] === $actor;
    $pdo->prepare('INSERT INTO solicitacoes_eventos(solicitacao_id,autor_id,autor_nome,papel,tipo,status_de,status_para,mensagem,dados_json) VALUES(?,?,?,?,?,?,?,?,?)')
        ->execute([$order['id'], $actor, $isClient ? $order['nome_contratante'] : $order['nome_prestador'], $isClient ? 'contratante' : 'prestador', $type, $order['status'], $newStatus, $message ?: null, $snapshot === null ? null : json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
}
function order_create(PDO $pdo, int $userId, array $data): int {
    $provider = filter_var($data['terceirizada_id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    $people = filter_var($data['numero_funcionarios'] ?? '1', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>100000]]);
    if (!$provider || !$people) throw new OrderProblem('Revise o prestador e a quantidade de profissionais.');
    $description = order_text($data, 'descricao_servico', 10000, true);
    $location = order_text($data, 'localizacao_servico', 255, true);
    $service = order_text($data, 'servico_id', 60);
    $catalog = service_catalog();
    if ($service !== '' && !isset($catalog[$service])) throw new OrderProblem('Selecione um serviço da lista.');
    $area = $service !== '' ? $catalog[$service] : order_text($data, 'area_servico_solicitada', 100, true);
    $frequency = order_text($data, 'frequencia', 30);
    if (!in_array($frequency, ['', 'unico','diario','semanal','quinzenal','mensal','a_combinar'], true)) throw new OrderProblem('Selecione uma frequência válida.');
    $preferred = order_date($data, 'data_preferida');
    $key = order_text($data, 'chave_criacao', 64);
    if ($key !== '' && !preg_match('/^[a-f0-9]{64}$/D', $key)) throw new OrderProblem('Atualize a página para enviar o pedido.');
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT e.nome,u.status FROM empresas e JOIN usuarios u ON u.id=e.usuario_id WHERE e.usuario_id=? AND e.tipo_empresa IN ('mei','terceirizada') FOR UPDATE");
        $stmt->execute([$provider]); $other = $stmt->fetch();
        if (!$other || $other['status'] !== 'ativo') throw new OrderProblem('Prestador indisponível.');
        if ($key !== '') {
            $existing = $pdo->prepare('SELECT id,contratante_id FROM solicitacoes WHERE chave_criacao=?'); $existing->execute([$key]); $row = $existing->fetch();
            if ($row) {
                if ((int)$row['contratante_id'] !== $userId) throw new OrderProblem('Atualize a página para enviar um novo pedido.', 409);
                $pdo->commit(); return (int)$row['id'];
            }
        }
        $pdo->prepare("INSERT INTO solicitacoes(contratante_id,terceirizada_id,descricao_servico,localizacao_servico,numero_funcionarios,area_servico_solicitada,servico_id,frequencia,data_preferida,chave_criacao,status,atualizada_em) VALUES(?,?,?,?,?,?,?,?,?,?,'aberta',NOW())")
            ->execute([$userId,$provider,$description,$location,$people,$area,$service ?: null,$frequency ?: null,$preferred,$key ?: null]);
        $id = (int)$pdo->lastInsertId(); $order = order_fetch($pdo, $id, $userId);
        order_event($pdo, $order, $userId, 'pedido_criado', 'aberta', 'Solicitação enviada ao prestador.');
        $pdo->commit(); return $id;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function order_transition(PDO $pdo, int $id, int $userId, string $action, array $data): void {
    try {
        $pdo->beginTransaction(); $order = order_fetch($pdo, $id, $userId, true);
        $client = (int)$order['contratante_id'] === $userId;
        $provider = !$client;
        if (array_key_exists('versao', $data) && (!is_scalar($data['versao']) || (string)$order['versao'] !== (string)$data['versao'])) throw new OrderProblem('Este pedido foi atualizado. Confira o histórico e tente novamente.', 409);
        $status = $order['status']; $next = $status; $message = ''; $changes = []; $snapshot = null;
        if (in_array($action, ['enviar_proposta','revisar_proposta'], true)) {
            $allowed = $action === 'enviar_proposta' ? ['aberta'] : ['orçada','respondida','em_negociacao'];
            if (!$provider || !in_array($status, $allowed, true)) throw new OrderProblem('O pedido não permite enviar esta proposta agora.', 409);
            if ($order['status_contratante'] !== 'ativo') throw new OrderProblem('O contratante está indisponível.', 409);
            $changes['valor_orcamento'] = order_money($data['valor_orcamento'] ?? '');
            foreach (['prazo_execucao'=>150, 'condicoes_pagamento'=>1000, 'itens_inclusos'=>3000, 'itens_exclusos'=>3000, 'mensagem_proposta'=>3000] as $field=>$max) $changes[$field] = order_text($data, $field, $max);
            $changes['validade_proposta'] = order_date($data, 'validade_proposta');
            $next = 'orçada'; $snapshot = $changes;
            $message = $action === 'enviar_proposta' ? 'Proposta enviada ao contratante.' : 'Proposta revisada. As condições anteriores permanecem no histórico.';
        } elseif ($action === 'recusar_pedido') {
            if (!$provider || $status !== 'aberta') throw new OrderProblem('Pedido indisponível ou já respondido.', 409);
            $next = 'recusada'; $message = order_text($data, 'mensagem', 2000) ?: 'O prestador não poderá atender este pedido.';
        } elseif ($action === 'aceitar_proposta') {
            if (($data['confirmacao'] ?? '') !== '1') throw new OrderProblem('Confirme que leu o valor e as condições antes de aceitar.');
            if (!$client || !in_array($status, ['orçada','respondida','em_negociacao'], true) || $order['valor_orcamento'] === null) throw new OrderProblem('Nenhuma proposta disponível para aceite.', 409);
            if (order_expired($order)) throw new OrderProblem('A validade da proposta terminou. Peça ao prestador uma revisão.', 409);
            if ($order['status_prestador'] !== 'ativo') throw new OrderProblem('Prestador indisponível. A proposta não foi aceita.', 409);
            $next = 'aceita'; $message = 'O contratante aceitou a proposta. O pagamento deve ser combinado diretamente entre as partes.';
            $snapshot = array_intersect_key($order, array_flip(['valor_orcamento','prazo_execucao','condicoes_pagamento','itens_inclusos','itens_exclusos','validade_proposta']));
        } elseif ($action === 'recusar_proposta') {
            if (!$client || !in_array($status, ['orçada','respondida','em_negociacao'], true)) throw new OrderProblem('A proposta não permite esta ação.', 409);
            $next = 'recusada_cliente'; $message = order_text($data, 'mensagem', 2000) ?: 'O contratante não aceitou a proposta.';
        } elseif ($action === 'iniciar_servico') {
            if (!$provider || $status !== 'aceita') throw new OrderProblem('O serviço precisa de uma proposta aceita para começar.', 409);
            $next = 'em_execucao'; $message = 'O prestador informou o início do serviço.';
        } elseif ($action === 'finalizar_servico') {
            if (!$provider || $status !== 'em_execucao') throw new OrderProblem('Este serviço não está em execução.', 409);
            $next = 'aguardando_confirmacao'; $message = order_text($data, 'mensagem', 2000) ?: 'O prestador informou a finalização. Aguardando confirmação do contratante.';
        } elseif ($action === 'confirmar_conclusao') {
            if (!$client || $status !== 'aguardando_confirmacao') throw new OrderProblem('A conclusão ainda não está disponível para confirmação.', 409);
            $next = 'concluida'; $message = 'O contratante confirmou a conclusão do serviço.';
        } elseif ($action === 'solicitar_ajuste') {
            if (!$client || $status !== 'aguardando_confirmacao') throw new OrderProblem('Não é possível solicitar um ajuste neste momento.', 409);
            $next = 'em_execucao'; $message = order_text($data, 'mensagem', 2000, true);
        } elseif ($action === 'cancelar') {
            $allowed = $client ? ['aberta','orçada','respondida','em_negociacao','aceita'] : ['aceita','em_execucao'];
            if (!in_array($status, $allowed, true)) throw new OrderProblem('Este pedido não permite cancelamento neste momento.', 409);
            $next = 'cancelada'; $message = order_text($data, 'mensagem', 2000, true);
        } elseif ($action === 'mensagem') {
            $message = order_text($data, 'mensagem', 2000, true);
        } else throw new OrderProblem('Ação inválida.');
        $changes['status'] = $next;
        $sets = implode(',', array_map(static fn($field)=>$field . '=?', array_keys($changes)));
        $pdo->prepare('UPDATE solicitacoes SET ' . $sets . ',atualizada_em=NOW(),versao=versao+1 WHERE id=?')->execute(array_merge(array_values($changes), [$id]));
        order_event($pdo, $order, $userId, $action, $next, $message, $snapshot);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function order_fail(Throwable $error, string $back, array $data): void {
    if (!$error instanceof OrderProblem) error_log('Orçamento: ' . $error->getMessage());
    $message = $error instanceof OrderProblem ? $error->getMessage() : 'Não foi possível salvar. Tente novamente em instantes.';
    if (($data['ui'] ?? '') === '1') {
        $_SESSION['order_feedback'] = ['message'=>$message, 'error'=>true];
        $_SESSION['order_old'] = array_intersect_key($data, array_flip(['descricao_servico','localizacao_servico','servico_id','area_servico_solicitada','numero_funcionarios','frequencia','data_preferida','valor_orcamento','prazo_execucao','condicoes_pagamento','itens_inclusos','itens_exclusos','mensagem_proposta','validade_proposta','mensagem','chave_criacao']));
        header('Location: ' . $back, true, 303); exit;
    }
    abort_request($error instanceof OrderProblem ? $error->status : 500, $message);
}
function order_list(PDO $pdo, int $userId, string $role, array $input): array {
    $column = $role === 'contratante' ? 'contratante_id' : 'terceirizada_id';
    $other = $role === 'contratante' ? 'terceirizada_id' : 'contratante_id';
    $groups = ['aberta'=>['aberta'], 'orçada'=>['orçada','respondida','em_negociacao'], 'aceita'=>['aceita'], 'em_execucao'=>['em_execucao'], 'aguardando_confirmacao'=>['aguardando_confirmacao'], 'concluida'=>['concluida','fechada'], 'recusada'=>['recusada','recusada_cliente'], 'cancelada'=>['cancelada']];
    $filter = isset($input['estado']) && is_string($input['estado']) && isset($groups[$input['estado']]) ? $input['estado'] : '';
    $q = isset($input['q']) && is_string($input['q']) ? mb_substr(trim($input['q']), 0, 100) : '';
    $params = [$userId]; $where = ['s.' . $column . '=?'];
    if ($filter !== '') { $where[] = 's.status IN (' . implode(',', array_fill(0, count($groups[$filter]), '?')) . ')'; array_push($params, ...$groups[$filter]); }
    if ($q !== '') { $where[] = "(e.nome LIKE ? ESCAPE '!' OR s.area_servico_solicitada LIKE ? ESCAPE '!' OR CAST(s.id AS CHAR)=?)"; $like = '%' . strtr($q, ['!'=>'!!','%'=>'!%','_'=>'!_']) . '%'; array_push($params,$like,$like,ltrim($q,'#')); }
    $from = ' FROM solicitacoes s LEFT JOIN empresas e ON e.usuario_id=s.' . $other . ' WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare('SELECT COUNT(*)' . $from); $stmt->execute($params); $total = (int)$stmt->fetchColumn();
    $pages = max(1, (int)ceil($total/10)); $page = min($pages, max(1, (int)(is_scalar($input['pagina'] ?? 1) ? ($input['pagina'] ?? 1) : 1)));
    $stmt = $pdo->prepare('SELECT s.*,e.nome AS nome_outro,e.foto_path' . $from . ' ORDER BY s.id DESC LIMIT 10 OFFSET ' . (($page-1)*10)); $stmt->execute($params);
    return ['items'=>$stmt->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>$pages,'filter'=>$filter,'q'=>$q];
}
