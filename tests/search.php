<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/backend/conexao.php';
require_once dirname(__DIR__) . '/backend/search.php';
if (!$is_local || $config['db_name'] !== 'serviconnect_dev' || $config['db_host'] !== '127.0.0.1') {
    throw new RuntimeException('Testes de busca restritos ao banco separado de desenvolvimento.');
}
$searchTestPassed = 0;
$searchTestIds = [];
$searchTestDocuments = [];
$marker = 'Busca' . bin2hex(random_bytes(4));
$testPassword = 'Busca!2026';
$testHash = password_hash($testPassword, PASSWORD_DEFAULT);

function search_check(bool $condition, string $name): void {
    global $searchTestPassed;
    if (!$condition) throw new RuntimeException('FALHOU: ' . $name);
    $searchTestPassed++;
    echo 'OK: ' . $name . "\n";
}
function search_client() {
    $client = curl_init();
    curl_setopt_array($client, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_COOKIEFILE=>'', CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>15]);
    return $client;
}
function search_request($client, string $path, ?array $data = null): array {
    curl_setopt($client, CURLOPT_URL, 'http://127.0.0.1:8080' . $path);
    curl_setopt($client, CURLOPT_HTTPHEADER, $data === null ? [] : ['Content-Type: application/json', 'Accept: application/json']);
    if ($data === null) curl_setopt($client, CURLOPT_HTTPGET, true);
    else { curl_setopt($client, CURLOPT_POST, true); curl_setopt($client, CURLOPT_POSTFIELDS, json_encode($data)); }
    $body = curl_exec($client);
    if ($body === false) throw new RuntimeException(curl_error($client));
    return ['status'=>curl_getinfo($client, CURLINFO_HTTP_CODE), 'body'=>$body];
}
function search_html(string $html): DOMXPath {
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
    return new DOMXPath($document);
}
function search_card_ids(string $html): array {
    $ids = [];
    foreach (search_html($html)->query('//article[@data-provider-id]') as $node) $ids[] = (int)$node->getAttribute('data-provider-id');
    return $ids;
}
function search_fixture(string $name, string $type = 'terceirizada', string $service = 'Jardinagem', string $city = 'Santo André', string $state = 'SP', string $region = 'Santo André', string $status = 'ativo'): array {
    global $pdo, $marker, $testHash, $searchTestIds, $searchTestDocuments;
    do {
        $document = '8' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT) . '0000';
        $exists = $pdo->prepare('SELECT 1 FROM usuarios WHERE cpf_cnpj=?'); $exists->execute([$document]);
    } while ($exists->fetchColumn());
    $pdo->prepare("INSERT INTO usuarios(cpf_cnpj,email,senha,tipo_conta,status) VALUES(?,?,?,'empresa',?)")
        ->execute([$document, 'search_' . $document . '@example.invalid', $testHash, $status]);
    $id = (int)$pdo->lastInsertId(); $searchTestIds[] = $id; $searchTestDocuments[] = $document;
    $pdo->prepare('INSERT INTO empresas(usuario_id,nome,tipo_empresa,area_atuacao,descricao,cidade,estado,regiao,cep,logradouro,numero,bairro,telefone,latitude,longitude) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$id, $name . ' ' . $marker, $type, $service, 'Atendimento de teste. <b>Sem HTML executável.</b>', $city, $state, $region, '00000000', 'ENDERECO_PRIVADO_TESTE', '987', 'Bairro privado', '11987654321', '-23.12345678', '-46.87654321']);
    return ['id'=>$id, 'document'=>$document];
}
function search_test_login($client, string $document, $provider = ''): array {
    global $testPassword;
    $page = search_request($client, '/login.php');
    if (!preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $page['body'], $matches)) throw new RuntimeException('Token de login ausente.');
    return search_request($client, '/backend/controllers/AuthController.php', ['identificacao'=>$document, 'senha'=>$testPassword, 'csrf_token'=>$matches[1], 'prestador'=>$provider]);
}

try {
    $mei = search_fixture('B Jardins', 'mei', 'Jardinagem e poda', 'São Paulo', 'SP', 'Suzano e Poá');
    $company = search_fixture('A Manutenção', 'terceirizada', 'Manutenção de jardins', 'Suzano', 'SP', 'Suzano e Ferraz de Vasconcelos');
    $cleaner = search_fixture('C Limpeza', 'terceirizada', 'Limpeza', 'Rio de Janeiro', 'RJ', 'Rio de Janeiro');
    $literal = search_fixture('D Literal%_!', 'mei', 'Jardinagem');
    for ($i = 0; $i < 12; $i++) search_fixture('E Jardins ' . str_pad((string)$i, 2, '0', STR_PAD_LEFT));
    $blocked = search_fixture('Bloqueado', 'mei', 'Jardinagem', 'Suzano', 'SP', 'Suzano', 'bloqueado');
    $archived = search_fixture('Arquivado', 'terceirizada', 'Jardinagem', 'Suzano', 'SP', 'Suzano', 'arquivado');
    $contractor = search_fixture('Contratante', 'contratante');
    $anonymous = search_client();

    $all = search_providers($pdo, search_filters(['q'=>$marker]));
    search_check($all['total'] === 16 && $all['pages'] === 2 && count($all['items']) === 12, 'Busca real conta e pagina os prestadores ativos');
    search_check(!in_array($blocked['id'], array_column($all['items'], 'usuario_id')), 'Conta bloqueada não aparece');
    search_check(!in_array($archived['id'], array_column($all['items'], 'usuario_id')), 'Conta arquivada não aparece');
    search_check(!in_array($contractor['id'], array_column($all['items'], 'usuario_id')), 'Contratantes ficam fora dos resultados');
    $second = search_providers($pdo, search_filters(['q'=>$marker, 'pagina'=>'2']));
    search_check(count($second['items']) === 4 && $second['from'] === 13 && $second['to'] === 16, 'Última página apresenta o intervalo correto');
    search_check(!array_intersect(array_column($all['items'], 'usuario_id'), array_column($second['items'], 'usuario_id')), 'Paginação não repete resultados');
    search_check(array_column($all['items'], 'usuario_id') === array_column(search_providers($pdo, search_filters(['q'=>$marker]))['items'], 'usuario_id'), 'Ordenação é estável entre requisições');

    $combination = ['q'=>$marker . ' jardinagem', 'local'=>'suzano', 'uf'=>'sp', 'tipo'=>'mei'];
    $combined = search_providers($pdo, search_filters($combination));
    search_check($combined['total'] === 1 && (int)$combined['items'][0]['usuario_id'] === $mei['id'], 'Combina serviço, região atendida, UF e tipo MEI');
    search_check(search_providers($pdo, search_filters(['q'=>$marker, 'local'=>'Suzano']))['total'] === 2, 'Local pesquisa cidade e regiões atendidas');
    search_check(search_providers($pdo, search_filters(['q'=>$marker, 'uf'=>'RJ']))['total'] === 1, 'Filtro de estado considera a sede');
    search_check(search_providers($pdo, search_filters(['q'=>$marker, 'tipo'=>'terceirizada']))['total'] === 14, 'Filtro de empresa terceirizada funciona');
    search_check(search_providers($pdo, search_filters(['q'=>$marker . ' manutencao']))['total'] === 1, 'Busca ignora diferenças de acento e maiúsculas');
    search_check(search_providers($pdo, search_filters(['q'=>$marker . ' poda Suzano']))['total'] === 1, 'Múltiplos termos podem combinar serviço e região');
    search_check(search_providers($pdo, search_filters(['q'=>$marker, 'servico'=>'poda']))['total'] === 1, 'Filtro de serviço respeita ofertas descritas nos perfis anteriores');
    search_check(search_providers($pdo, search_filters(['q'=>$marker . ' %_!']))['total'] === 1, 'Porcentagem, sublinhado e escape são literais');
    search_check(search_providers($pdo, search_filters(['q'=>"' OR 1=1 --"]))['total'] === 0, 'Texto de SQL não altera a consulta');
    search_check(search_providers($pdo, search_filters(['q'=>$marker, 'pagina'=>'999999999']))['page'] === 2, 'Página além do fim usa a última página válida');
    foreach (['-1', '0', 'abc', ['2'], '9999999999999999999999999'] as $page) {
        search_check(search_providers($pdo, search_filters(['q'=>$marker, 'pagina'=>$page]))['page'] === 1, 'Página inválida usa a primeira página');
    }
    $desc = search_providers($pdo, search_filters(['q'=>$marker, 'ordem'=>'nome_desc']));
    search_check($desc['items'][0]['nome_empresa'] > $desc['items'][1]['nome_empresa'], 'Ordenação de Z a A funciona');
    $relevance = search_providers($pdo, search_filters(['q'=>$marker . ' jardins']));
    search_check((int)$relevance['items'][0]['usuario_id'] === $company['id'], 'Relevância prioriza correspondência nos serviços');
    search_check($combined['items'][0]['latitude'] === null && $combined['items'][0]['longitude'] === null, 'Busca pública não seleciona coordenadas');
    $mapMei = search_providers($pdo, search_filters($combination), true);
    search_check($mapMei['items'][0]['latitude'] === null && $mapMei['items'][0]['longitude'] === null, 'Mapa preserva coordenadas privadas do MEI');

    $page = search_request($anonymous, '/busca.php?q=' . $marker);
    search_check($page['status'] === 200 && count(search_card_ids($page['body'])) === 12, 'Página pública renderiza 12 resultados reais sem login');
    search_check(strpos($page['body'], '16 prestadores encontrados') !== false, 'Contagem total aparece na tela');
    $xpath = search_html($page['body']);
    $next = $xpath->query('//nav[@aria-label="Paginação dos prestadores"]/a[@rel="next"]')->item(0);
    search_check($next && strpos($next->getAttribute('href'), 'q=' . $marker . '&pagina=2') !== false, 'Link de próxima página preserva a busca');
    $lastPage = search_request($anonymous, '/' . $next->getAttribute('href'));
    search_check(count(search_card_ids($lastPage['body'])) === 4 && strpos($lastPage['body'], 'Mostrando 13–16') !== false, 'Paginação HTTP mostra os resultados restantes');
    search_check($xpath->query('//form[@role="search"]/input[@name="pagina"]')->length === 0, 'Nova pesquisa não reaproveita a página anterior');
    $pagedFilters = ['q'=>$marker, 'uf'=>'SP', 'tipo'=>'terceirizada', 'ordem'=>'nome'];
    $filteredPaging = search_request($anonymous, '/busca.php?' . http_build_query($pagedFilters));
    $filteredNext = search_html($filteredPaging['body'])->query('//nav/a[@rel="next"]')->item(0);
    parse_str(parse_url($filteredNext->getAttribute('href'), PHP_URL_QUERY), $retained);
    search_check(array_diff_assoc($pagedFilters, $retained) === [] && $retained['pagina'] === '2', 'Paginação mantém tipo, UF, texto e ordenação combinados');
    $combinedPage = search_request($anonymous, '/busca.php?' . http_build_query($combination));
    search_check(search_card_ids($combinedPage['body']) === [$mei['id']], 'Formulário público aplica filtros combinados no servidor');
    search_check(strpos($combinedPage['body'], 'Atende em') !== false && strpos($combinedPage['body'], 'Suzano e Poá') !== false, 'Resultado mostra região atendida e tipo do prestador');
    foreach ([$mei['document'], 'ENDERECO_PRIVADO_TESTE', '11987654321', '-23.12345678', '-46.87654321', 'search_' . $mei['document']] as $private) {
        search_check(strpos($combinedPage['body'], $private) === false, 'Resultado público não expõe dados privados');
    }
    search_check(strpos($combinedPage['body'], '<b>Sem HTML') === false && strpos($combinedPage['body'], '&lt;b&gt;') !== false, 'HTML do perfil é escapado no resultado');
    $xss = search_request($anonymous, '/busca.php?q=' . rawurlencode('<img src=x onerror=alert(1)>'));
    search_check($xss['status'] === 200 && search_html($xss['body'])->query('//img[@onerror]')->length === 0, 'Texto da busca não injeta HTML');
    foreach (['q%5B%5D=x', 'tipo=admin', 'uf=XX', 'ordem=RAND()', 'q=' . str_repeat('x', 101), 'q=%FF'] as $invalid) {
        $invalidPage = search_request($anonymous, '/busca.php?' . $invalid);
        search_check($invalidPage['status'] === 400 && strpos($invalidPage['body'], 'Revise os filtros') !== false, 'Filtro inválido recebe mensagem clara sem erro interno');
    }
    $empty = search_request($anonymous, '/busca.php?q=' . $marker . 'inexistente');
    search_check($empty['status'] === 200 && strpos($empty['body'], 'Nenhum resultado para esta busca') !== false, 'Estado vazio explica como refazer a busca');
    $home = search_html(search_request($anonymous, '/')['body']);
    search_check($home->query('//form[@action="busca.php"]//input[@name="q"]')->length === 1, 'Busca da página inicial envia o texto ao servidor');

    $intent = search_request($anonymous, '/login.php?prestador=' . $mei['id']);
    search_check(search_html($intent['body'])->query('//input[@name="prestador" and @value="' . $mei['id'] . '"]')->length === 1, 'Login mantém o prestador escolhido na busca');
    $signedIn = search_client();
    $auth = search_test_login($signedIn, $contractor['document'], $mei['id']);
    search_check($auth['status'] === 200 && json_decode($auth['body'], true)['redirect'] === 'perfil_empresa.php?id=' . $mei['id'], 'Login retorna ao perfil escolhido');
    search_check(search_request($signedIn, '/perfil_empresa.php?id=' . $mei['id'])['status'] === 200, 'Perfil escolhido pode ser aberto após login');
    $dashboard = search_request($signedIn, '/dashboard_contratante.php?' . http_build_query($combination));
    search_check($dashboard['status'] === 200 && search_card_ids($dashboard['body']) === [$mei['id']], 'Painel aplica a mesma busca e os mesmos filtros');
    search_check(strpos($dashboard['body'], 'solicitacao_orcamento.php?terceirizada_id=' . $mei['id']) !== false, 'Contratante pode solicitar orçamento do resultado');
    search_check(strpos($dashboard['body'], '-23.12345678') === false, 'Resultado de MEI não vaza coordenadas no JavaScript do mapa');
    $panelPaging = search_request($signedIn, '/dashboard_contratante.php?' . http_build_query($pagedFilters));
    $panelNext = search_html($panelPaging['body'])->query('//nav[@aria-label="Paginação dos prestadores"]/a[@rel="next"]')->item(0);
    search_check($panelNext && strpos($panelNext->getAttribute('href'), 'dashboard_contratante.php?') === 0, 'Paginação do painel permanece no painel com os filtros');
    search_check(count(search_card_ids(search_request($signedIn, '/' . $panelNext->getAttribute('href'))['body'])) === 1, 'Segunda página do painel contém somente o resultado restante');
    $signedPublic = search_request($signedIn, '/busca.php?' . http_build_query($combination));
    search_check(strpos($signedPublic['body'], 'Solicitar orçamento') !== false && strpos($signedPublic['body'], 'Entrar para ver perfil') === false, 'Busca reconhece contratante autenticado');
    $maliciousIntent = search_test_login($signedIn, $contractor['document'], 'https://example.invalid/');
    search_check(json_decode($maliciousIntent['body'], true)['redirect'] === 'dashboard_contratante.php', 'Intenção de login não permite redirecionamento externo');
    echo "\n$searchTestPassed verificações de busca aprovadas.\n";
} finally {
    foreach ($searchTestIds as $id) $pdo->prepare('DELETE FROM usuarios WHERE id=? AND email LIKE ?')->execute([$id, 'search_%@example.invalid']);
    foreach ($searchTestDocuments as $document) $pdo->prepare('DELETE FROM login_tentativas WHERE chave=?')->execute([hash('sha256', '127.0.0.1:' . $document)]);
}
