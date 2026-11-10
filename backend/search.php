<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/catalog.php';

function search_states(): array {
    return ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
}

function search_filters(array $input): array {
    $filters = ['q'=>'', 'local'=>'', 'uf'=>'', 'tipo'=>'', 'servico'=>'', 'ordem'=>'relevancia', 'pagina'=>1, 'errors'=>[]];
    foreach (['q'=>100, 'local'=>100, 'uf'=>2, 'tipo'=>20, 'servico'=>60, 'ordem'=>20] as $key=>$max) {
        if (!isset($input[$key])) continue;
        if (!is_string($input[$key]) || !mb_check_encoding($input[$key], 'UTF-8')) {
            $filters['errors'][] = 'Os filtros devem conter apenas texto válido.';
            continue;
        }
        $value = preg_replace('/\s+/u', ' ', trim($input[$key]));
        if (mb_strlen($value) > $max) {
            $filters['errors'][] = in_array($key, ['q','local'], true)
                ? 'Use até 100 caracteres em cada campo de busca.' : 'Selecione uma opção válida nos filtros.';
            continue;
        }
        $filters[$key] = $value;
    }
    $filters['uf'] = strtoupper($filters['uf']);
    if ($filters['servico'] !== '' && !isset(service_catalog()[$filters['servico']])) {
        $filters['errors'][] = 'Selecione um serviço válido.';
        $filters['servico'] = '';
    }
    if ($filters['uf'] !== '' && !in_array($filters['uf'], search_states(), true)) {
        $filters['errors'][] = 'Selecione um estado válido.';
        $filters['uf'] = '';
    }
    if (!in_array($filters['tipo'], ['', 'mei', 'terceirizada'], true)) {
        $filters['errors'][] = 'Selecione MEI, empresa terceirizada ou todos os tipos.';
        $filters['tipo'] = '';
    }
    if (!in_array($filters['ordem'], ['relevancia', 'nome', 'nome_desc'], true)) {
        $filters['errors'][] = 'Selecione uma ordenação válida.';
        $filters['ordem'] = 'relevancia';
    }
    if (isset($input['pagina']) && is_scalar($input['pagina'])) {
        $filters['pagina'] = filter_var($input['pagina'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 1;
    }
    $filters['errors'] = array_values(array_unique($filters['errors']));
    return $filters;
}

function search_like(string $value): string {
    // ! é o escape explícito: % e _ digitados pelo usuário são caracteres literais.
    return '%' . strtr($value, ['!'=>'!!', '%'=>'!%', '_'=>'!_']) . '%';
}

function search_providers(PDO $pdo, array $filters, bool $includeMap = false): array {
    $result = ['filters'=>$filters, 'items'=>[], 'total'=>0, 'page'=>1, 'pages'=>1, 'per_page'=>12, 'from'=>0, 'to'=>0];
    if ($filters['errors']) return $result;
    $where = ["u.tipo_conta='empresa'", "u.status='ativo'", "e.tipo_empresa IN ('mei','terceirizada')"];
    $params = [];
    if ($filters['servico'] !== '') {
        $where[] = "(EXISTS(SELECT 1 FROM empresa_servicos es WHERE es.usuario_id=u.id AND es.servico_id=?) OR (NOT EXISTS(SELECT 1 FROM empresa_servicos es WHERE es.usuario_id=u.id) AND (e.area_atuacao LIKE ? ESCAPE '!' OR e.descricao LIKE ? ESCAPE '!' OR e.area_atuacao LIKE ? ESCAPE '!' OR e.descricao LIKE ? ESCAPE '!')))";
        $name = search_like(str_replace('-',' ',$filters['servico']));
        $label = search_like(service_catalog()[$filters['servico']]);
        array_push($params,$filters['servico'],$name,$name,$label,$label);
    }
    $terms = $filters['q'] === '' ? [] : explode(' ', $filters['q']);
    foreach ($terms as $term) {
        $where[] = "(e.nome LIKE ? ESCAPE '!' OR e.area_atuacao LIKE ? ESCAPE '!' OR e.descricao LIKE ? ESCAPE '!' OR e.cidade LIKE ? ESCAPE '!' OR e.regiao LIKE ? ESCAPE '!' OR e.cidades_atendidas LIKE ? ESCAPE '!' OR EXISTS(SELECT 1 FROM empresa_servicos es WHERE es.usuario_id=u.id AND es.nome LIKE ? ESCAPE '!'))";
        array_push($params, ...array_fill(0, 7, search_like($term)));
        $related = service_matching_ids($term);
        if ($related) {
            $last = array_pop($where);
            $where[] = '(' . $last . ' OR EXISTS(SELECT 1 FROM empresa_servicos es WHERE es.usuario_id=u.id AND es.servico_id IN (' . implode(',',array_fill(0,count($related),'?')) . ')))';
            array_push($params,...$related);
        }
    }
    if ($filters['local'] !== '') {
        $where[] = "(e.cidade LIKE ? ESCAPE '!' OR e.regiao LIKE ? ESCAPE '!' OR e.cidades_atendidas LIKE ? ESCAPE '!')";
        array_push($params, search_like($filters['local']), search_like($filters['local']), search_like($filters['local']));
    }
    if ($filters['uf'] !== '') { $where[] = 'e.estado=?'; $params[] = $filters['uf']; }
    if ($filters['tipo'] !== '') { $where[] = 'e.tipo_empresa=?'; $params[] = $filters['tipo']; }
    $from = ' FROM usuarios u JOIN empresas e ON e.usuario_id=u.id WHERE ' . implode(' AND ', $where);
    $count = $pdo->prepare('SELECT COUNT(*)' . $from);
    $count->execute($params);
    $result['total'] = (int)$count->fetchColumn();
    $result['pages'] = max(1, (int)ceil($result['total'] / $result['per_page']));
    $result['page'] = min($filters['pagina'], $result['pages']);
    $offset = ($result['page'] - 1) * $result['per_page'];
    $order = $filters['ordem'] === 'nome_desc' ? 'e.nome DESC,u.id DESC' : 'e.nome,u.id';
    if ($terms && $filters['ordem'] === 'relevancia') {
        $scores = [];
        foreach ($terms as $term) {
            $scores[] = "CASE WHEN e.area_atuacao LIKE ? ESCAPE '!' THEN 3 WHEN e.nome LIKE ? ESCAPE '!' THEN 2 ELSE 1 END";
            array_push($params, search_like($term), search_like($term));
        }
        $order = '(' . implode('+', $scores) . ') DESC,e.nome,u.id';
    }
    // Não selecionar documento, contato ou endereço residencial. MEI nunca tem coordenadas públicas.
    $coordinates = $includeMap ? "CASE WHEN e.tipo_empresa='terceirizada' AND e.mapa_publico=1 THEN e.latitude ELSE NULL END AS latitude, CASE WHEN e.tipo_empresa='terceirizada' AND e.mapa_publico=1 THEN e.longitude ELSE NULL END AS longitude" : 'NULL AS latitude,NULL AS longitude';
    $query = $pdo->prepare('SELECT u.id AS usuario_id,e.nome AS nome_empresa,e.descricao AS descricao_servicos,e.area_atuacao,e.regiao AS regioes_atendidas,e.cidade,e.estado,e.tipo_empresa,e.foto_path,' . $coordinates . $from . ' ORDER BY ' . $order . ' LIMIT ? OFFSET ?');
    foreach ($params as $index=>$value) $query->bindValue($index + 1, $value, PDO::PARAM_STR);
    $query->bindValue(count($params) + 1, $result['per_page'], PDO::PARAM_INT);
    $query->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $query->execute();
    $result['items'] = $query->fetchAll();
    $serviceNames = [];
    if ($result['items']) {
        $ids = array_column($result['items'],'usuario_id');
        $services = $pdo->prepare('SELECT usuario_id,nome FROM empresa_servicos WHERE usuario_id IN (' . implode(',',array_fill(0,count($ids),'?')) . ') ORDER BY nome');
        $services->execute($ids);
        foreach ($services->fetchAll() as $item) $serviceNames[$item['usuario_id']][] = $item['nome'];
    }
    foreach ($result['items'] as &$provider) { $provider['foto_path'] = safe_photo($provider['foto_path']); $provider['servicos_nomes'] = $serviceNames[$provider['usuario_id']] ?? []; }
    unset($provider);
    if ($result['total']) {
        $result['from'] = $offset + 1;
        $result['to'] = $offset + count($result['items']);
    }
    return $result;
}

function search_url(string $path, array $filters, int $page = 1, string $anchor = ''): string {
    $query = [];
    foreach (['q','local','uf','tipo','servico','ordem'] as $key) {
        if ($filters[$key] !== '' && !($key === 'ordem' && $filters[$key] === 'relevancia')) $query[$key] = $filters[$key];
    }
    if ($page > 1) $query['pagina'] = $page;
    return $path . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '') . $anchor;
}

function search_escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function search_excerpt(?string $value, int $max = 180): string {
    $text = preg_replace('/\s+/u', ' ', trim($value ?? ''));
    return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
}
