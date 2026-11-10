<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/backend/conexao.php';
if (!$is_local || $config['db_name'] !== 'serviconnect_dev' || $config['db_host'] !== '127.0.0.1') {
    throw new RuntimeException('Teste de contas restrito ao banco separado de desenvolvimento.');
}

$accountTestBase = 'http://127.0.0.1:8080';
$accountTestPassed = 0;
$accountTestDocuments = [];
$accountTestAdminIds = [];
$accountTestEmails = [];

function account_check(bool $condition, string $name): void {
    global $accountTestPassed;
    if (!$condition) throw new RuntimeException('FALHOU: '.$name);
    $accountTestPassed++;
    echo "OK: $name\n";
}

function account_client() {
    $client = curl_init();
    curl_setopt_array($client, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_COOKIEFILE=>'', CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>10]);
    return $client;
}

function account_request($client, string $path, $data = null, bool $json = false): array {
    global $accountTestBase;
    $headers = [];
    curl_setopt($client, CURLOPT_URL, $accountTestBase.$path);
    curl_setopt($client, CURLOPT_HTTPHEADER, $json ? ['Content-Type: application/json', 'Accept: application/json'] : []);
    curl_setopt($client, CURLOPT_HEADERFUNCTION, static function ($ch, $line) use (&$headers) { $headers[] = $line; return strlen($line); });
    if ($data === null) {
        curl_setopt($client, CURLOPT_HTTPGET, true);
    } else {
        curl_setopt($client, CURLOPT_POST, true);
        curl_setopt($client, CURLOPT_POSTFIELDS, $json ? json_encode($data) : $data);
    }
    $body = curl_exec($client);
    if ($body === false) throw new RuntimeException(curl_error($client));
    return ['status'=>curl_getinfo($client, CURLINFO_HTTP_CODE), 'body'=>$body, 'headers'=>implode('', $headers)];
}

function account_csrf(array $page): string {
    if ($page['status'] !== 200 || !preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $page['body'], $match)) {
        throw new RuntimeException('Tela de teste sem token CSRF ou indisponível: HTTP '.$page['status']);
    }
    return $match[1];
}

function account_cnpj_digits(string $base): string {
    for ($length = 12; $length <= 13; $length++) {
        $sum = 0;
        $weight = $length - 7;
        for ($i = 0; $i < $length; $i++) {
            $sum += (ord($base[$i]) - 48) * $weight;
            $weight = $weight === 2 ? 9 : $weight - 1;
        }
        $base .= $sum % 11 < 2 ? '0' : (string)(11 - $sum % 11);
    }
    return $base;
}

function account_document(string $type): string {
    global $pdo, $accountTestDocuments;
    do {
        if ($type === 'cpf') {
            $document = '7'.str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            for ($length = 9; $length <= 10; $length++) {
                $sum = 0;
                for ($i = 0; $i < $length; $i++) $sum += (int)$document[$i] * ($length + 1 - $i);
                $remainder = $sum % 11;
                $document .= $remainder < 2 ? '0' : (string)(11 - $remainder);
            }
        } elseif ($type === 'alpha') {
            $document = account_cnpj_digits('A'.strtoupper(bin2hex(random_bytes(5))).'1');
        } else {
            $document = account_cnpj_digits('7'.str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT).'00001');
        }
        $check = $pdo->prepare('SELECT id FROM usuarios WHERE cpf_cnpj = ?');
        $check->execute([$document]);
    } while ($check->fetchColumn() || in_array($document, $accountTestDocuments, true));
    $accountTestDocuments[] = $document;
    return $document;
}

function account_mask(string $document): string {
    if (strlen($document) === 11) return substr($document,0,3).'.'.substr($document,3,3).'.'.substr($document,6,3).'-'.substr($document,9);
    return substr($document,0,2).'.'.substr($document,2,3).'.'.substr($document,5,3).'/'.substr($document,8,4).'-'.substr($document,12);
}

function account_registration(string $tipo, string $pessoa, string $document, string $name): array {
    global $accountTestEmails;
    $email = 'accounts_'.strtolower($document).'@example.invalid';
    $accountTestEmails[] = $email;
    return ['tipo'=>$tipo, 'tipo_pessoa'=>$pessoa, 'documento'=>account_mask($document), 'nome'=>$name,
        'email_acesso'=>$email, 'senha'=>'Contas!2026', 'confirmar_senha'=>'Contas!2026'];
}

function account_register($client, array $data): array {
    $data['csrf_token'] = account_csrf(account_request($client, '/cadastro_empresa.php'));
    return account_request($client, '/backend/controllers/CadastroEmpresaController.php', $data, true);
}

function account_login($client, string $document): array {
    $token = account_csrf(account_request($client, '/login.php'));
    return account_request($client, '/backend/controllers/AuthController.php', ['identificacao'=>$document, 'senha'=>'Contas!2026', 'csrf_token'=>$token], true);
}

function account_row(string $document): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.id,u.cpf_cnpj,u.tipo_pessoa,u.tipo_conta,u.email,u.status,e.nome,e.tipo_empresa,e.telefone,e.responsavel,e.descricao,e.area_atuacao,e.regiao,e.horario FROM usuarios u JOIN empresas e ON e.usuario_id=u.id WHERE u.cpf_cnpj=?');
    $stmt->execute([$document]);
    $row = $stmt->fetch();
    if (!$row) throw new RuntimeException('Conta temporária não encontrada.');
    return $row;
}

function account_legacy_fixture(string $tipo): string {
    global $pdo, $accountTestEmails;
    $document = account_document('cnpj');
    $email = 'accounts_'.strtolower($document).'@example.invalid';
    $accountTestEmails[] = $email;
    // Omitir tipo_pessoa reproduz as contas anteriores à migração.
    $pdo->prepare("INSERT INTO usuarios(cpf_cnpj,email,senha,tipo_conta,status) VALUES(?,?,?,'empresa','ativo')")
        ->execute([$document, $email, password_hash('Contas!2026', PASSWORD_DEFAULT)]);
    $id = $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO empresas(usuario_id,nome,tipo_empresa,cep,logradouro,numero,bairro,cidade,estado) VALUES(?,?,?,?,?,?,?,?,?)')
        ->execute([$id,'Conta legada de teste',$tipo,'00000000','Rua fictícia','0','Teste','Teste','SP']);
    return $document;
}

try {
    $definitions = [
        'pf'=>['contratante','fisica','cpf','Pessoa física de teste'],
        'pj'=>['contratante','juridica','cnpj','Empresa contratante de teste'],
        'mei'=>['mei','juridica','cnpj','Jardinagem MEI de teste'],
        'terceirizada'=>['terceirizada','juridica','cnpj','Terceirizada de teste'],
        'alpha'=>['mei','juridica','alpha','MEI alfanumérico de teste'],
    ];
    $accounts = [];
    foreach ($definitions as $key=>[$tipo,$pessoa,$docType,$name]) {
        $document = account_document($docType);
        $client = account_client();
        $data = account_registration($tipo,$pessoa,$document,$name);
        if ($key === 'alpha') $data['documento'] = strtolower($data['documento']);
        $created = account_register($client,$data);
        account_check($created['status'] === 200, 'Cadastro de '.$key.' com documento formatado');
        $row = account_row($document);
        account_check($row['cpf_cnpj'] === $document && $row['tipo_pessoa'] === $pessoa && $row['tipo_empresa'] === $tipo,
            'Documento normalizado e modalidade corretos em '.$key);
        account_check($row['nome'] === $name && $row['tipo_conta'] === 'empresa', 'Perfil e compatibilidade dos vínculos de '.$key);
        $login = account_login($client,$key === 'alpha' ? strtolower(account_mask($document)) : account_mask($document));
        account_check($login['status'] === 200, 'Login com documento formatado de '.$key);
        $dashboard = $tipo === 'contratante' ? '/dashboard_contratante.php' : '/dashboard_tercerizada.php';
        $page = account_request($client,$dashboard);
        account_check($page['status'] === 200 && !preg_match('/(?:Fatal error|Warning):/', $page['body']), 'Painel acessível para '.$key);
        $accounts[$key] = ['client'=>$client,'document'=>$document,'row'=>$row,'data'=>$data,'dashboard'=>$dashboard];
    }

    $anonymous = account_client();
    $invalidBase = account_registration('contratante','fisica',account_document('cpf'),'Nome de teste');
    account_check(account_request($anonymous,'/backend/controllers/CadastroEmpresaController.php',$invalidBase,true)['status'] === 403,'Novo cadastro continua exigindo CSRF');
    $invalid = $invalidBase; $invalid['documento'] = '111.111.111-11';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'CPF repetido é recusado no servidor');
    $invalid = $invalidBase;
    $digits = preg_replace('/\D/','',$invalid['documento']);
    $invalid['documento'] = substr($digits,0,-1).((int)substr($digits,-1)+1)%10;
    account_check(account_register($anonymous,$invalid)['status'] === 422,'CPF com dígito incorreto é recusado');
    $invalid = $invalidBase; $invalid['documento'] = 'A'.$invalid['documento'];
    account_check(account_register($anonymous,$invalid)['status'] === 422,'Letras em CPF não são removidas para aceitar o cadastro');
    $invalid = $invalidBase; $invalid['tipo_pessoa'] = 'juridica';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'CPF não pode ser cadastrado como pessoa jurídica');
    $invalid = $invalidBase; $invalid['tipo'] = 'mei';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'MEI não pode ser cadastrado com CPF');
    $invalid = $invalidBase; $invalid['tipo'] = 'terceirizada';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'Terceirizada não pode ser cadastrada com CPF');
    $invalid = $accounts['mei']['data']; $invalid['tipo_pessoa'] = 'fisica';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'Prestador com CNPJ também exige modalidade jurídica');
    $invalid = $invalidBase; $invalid['tipo'] = 'admin';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'Cadastro não aceita papel administrativo');
    $invalid = $invalidBase; $invalid['tipo_pessoa'] = 'inexistente';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'Tipo de pessoa arbitrário é recusado');
    $invalid = $invalidBase; $invalid['nome'] = '';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'Nome de apresentação é obrigatório no novo cadastro');
    $invalid = $invalidBase; $invalid['confirmar_senha'] = 'Diferente!2026';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'Confirmação divergente é recusada no servidor');
    $invalid = $invalidBase; unset($invalid['confirmar_senha']);
    account_check(account_register($anonymous,$invalid)['status'] === 422,'Payload novo exige confirmação da senha');
    $invalid = $accounts['mei']['data']; $invalid['documento'] = '11.111.111/1111-11';
    account_check(account_register($anonymous,$invalid)['status'] === 422,'CNPJ repetido é recusado');
    $invalid = $accounts['alpha']['data'];
    $invalid['documento'] = substr($accounts['alpha']['document'],0,-1).((int)substr($accounts['alpha']['document'],-1)+1)%10;
    account_check(account_register($anonymous,$invalid)['status'] === 422,'CNPJ alfanumérico com dígito incorreto é recusado');
    $duplicate = $accounts['pf']['data']; $duplicate['email_acesso'] = 'duplicate_'.bin2hex(random_bytes(5)).'@example.invalid';
    account_check(account_register($anonymous,$duplicate)['status'] === 409,'CPF existente é único entre contas');
    $duplicate = $accounts['alpha']['data']; $duplicate['documento'] = strtolower($duplicate['documento']);
    account_check(account_register($anonymous,$duplicate)['status'] === 409,'CNPJ alfanumérico não duplica por diferença de caixa');
    $duplicate = $invalidBase; $duplicate['email_acesso'] = $accounts['pf']['data']['email_acesso'];
    account_check(account_register($anonymous,$duplicate)['status'] === 409,'E-mail existente é único entre CPF e CNPJ');

    foreach (['pf','pj'] as $key) {
        $account = $accounts[$key];
        $before = account_row($account['document']);
        $profile = ['csrf_token'=>account_csrf(account_request($account['client'],$account['dashboard'])),
            'nome'=>'Nome atualizado '.$key,'responsavel'=>'Contato '.$key,'telefone'=>'11999990000',
            'cep'=>'00000000','logradouro'=>'Rua de teste','numero'=>'10','bairro'=>'Bairro de teste','cidade'=>'Ferraz de Vasconcelos','estado'=>'SP',
            'usuario_id'=>$accounts['mei']['row']['id'],'tipo_empresa'=>'mei','tipo_pessoa'=>'juridica','documento'=>$accounts['mei']['document']];
        account_check(account_request($account['client'],'/backend/controllers/PerfilContratanteController.php',$profile)['status'] === 303,'Edição de perfil de contratante '.$key);
        $after = account_row($account['document']);
        account_check($after['nome'] === $profile['nome'] && $after['telefone'] === $profile['telefone'], 'Nome e telefone de '.$key.' foram salvos');
        account_check($after['id'] === $before['id'] && $after['cpf_cnpj'] === $before['cpf_cnpj'] && $after['tipo_pessoa'] === $before['tipo_pessoa'] && $after['tipo_empresa'] === 'contratante',
            'Documento, tipo e proprietário de '.$key.' não são alterados pelo POST');
        account_check(account_row($accounts['mei']['document'])['nome'] === $accounts['mei']['row']['nome'],'Edição de '.$key.' não altera perfil de outra conta');
    }

    foreach (['mei','terceirizada','alpha'] as $key) {
        $account = $accounts[$key];
        $before = account_row($account['document']);
        $profile = ['csrf_token'=>account_csrf(account_request($account['client'],$account['dashboard'])),
            'nome'=>'Serviços atualizados '.$key,'responsavel'=>'Responsável de teste','telefone'=>'11988880000',
            'texto_perfil'=>'Jardinagem e manutenção de jardins.','area_atuacao'=>'Jardinagem','regiao'=>'Ferraz de Vasconcelos e Suzano','horario'=>'Segunda a sexta, 8h às 18h',
            'cep_empresa'=>'00000000','logradouro_empresa'=>'Rua de teste','numero_empresa'=>'20','bairro_empresa'=>'Teste','cidade_empresa'=>'Ferraz de Vasconcelos','estado_empresa'=>'SP',
            'usuario_id'=>$accounts['pf']['row']['id'],'tipo_empresa'=>'contratante','tipo_pessoa'=>'fisica','documento'=>$accounts['pf']['document']];
        if ($key === 'terceirizada') $profile['num_funcionarios'] = '4';
        account_check(account_request($account['client'],'/backend/controllers/PerfilTercerizadaController.php',$profile)['status'] === 303,'Edição de perfil de prestador '.$key.' sem exigir equipe de MEI');
        $after = account_row($account['document']);
        account_check($after['nome'] === $profile['nome'] && $after['telefone'] === $profile['telefone'] && $after['area_atuacao'] === 'Jardinagem' && $after['regiao'] === $profile['regiao'],
            'Oferta e contato de '.$key.' foram salvos');
        account_check($after['id'] === $before['id'] && $after['cpf_cnpj'] === $before['cpf_cnpj'] && $after['tipo_pessoa'] === 'juridica' && $after['tipo_empresa'] === $before['tipo_empresa'],
            'Documento, modalidade e proprietário de '.$key.' não são alterados pelo POST');
        $view = account_request($accounts['pf']['client'],'/perfil_empresa.php?id='.$after['id']);
        account_check($view['status'] === 200 && strpos($view['body'],'Jardinagem') !== false,'Perfil de '.$key.' mostra os serviços oferecidos');
        if ($key !== 'terceirizada') {
            account_check(strpos($view['body'],'Rua de teste') === false && strpos($view['body'],'Bairro de teste') === false,
                'Endereço base de '.$key.' não é exposto a outro usuário');
        }
    }
    $pf = $accounts['pf']; $mei = $accounts['mei'];
    $pfView = account_request($mei['client'],'/perfil_empresa.php?id='.$pf['row']['id']);
    account_check(strpos($pfView['body'],$pf['document']) === false && strpos($pfView['body'],account_mask($pf['document'])) === false
        && strpos($pfView['body'],$pf['data']['email_acesso']) === false, 'CPF e e-mail de pessoa física não são expostos a outro usuário');
    account_check(strpos($pfView['body'],'Rua de teste') === false && strpos($pfView['body'],'Ferraz de Vasconcelos') !== false,
        'Perfil de pessoa física mostra cidade, preservando o endereço residencial');
    account_check(strpos($pfView['body'],'11999990000') === false, 'Telefone de pessoa física fica privado antes de haver um pedido');
    $ownPfView = account_request($pf['client'],'/perfil_empresa.php?id='.$pf['row']['id']);
    account_check(strpos($ownPfView['body'],'Rua de teste') !== false, 'Titular continua vendo o próprio endereço');
    $pdo->prepare('UPDATE empresas SET latitude=-23.58,longitude=-46.33 WHERE usuario_id=?')->execute([$mei['row']['id']]);
    $nameStmt=$pdo->prepare('SELECT nome FROM empresas WHERE usuario_id=?'); $nameStmt->execute([$mei['row']['id']]);
    $listing=account_request($pf['client'],$pf['dashboard'].'?q='.rawurlencode($nameStmt->fetchColumn()));
    preg_match('/<script type="application\/json" id="mapProviders">([\s\S]*?)<\/script>/', $listing['body'], $mapData);
    $mapRows=json_decode($mapData[1] ?? '',true) ?: [];
    $meiMap=array_values(array_filter($mapRows,static fn($row)=>(int)$row['usuario_id']===(int)$mei['row']['id']));
    account_check(count($meiMap)===1 && $meiMap[0]['latitude']===null && $meiMap[0]['longitude']===null
        && !array_key_exists('logradouro',$meiMap[0]) && !array_key_exists('bairro',$meiMap[0]),
        'Lista inclui MEI sem enviar coordenadas ou endereço residencial ao navegador');
    account_check(account_request($pf['client'],'/backend/controllers/PerfilTercerizadaController.php',['csrf_token'=>account_csrf(account_request($pf['client'],$pf['dashboard']))])['status'] === 403,
        'Contratante não acessa edição de perfil de prestador');
    account_check(account_request($mei['client'],'/backend/controllers/PerfilContratanteController.php',['csrf_token'=>account_csrf(account_request($mei['client'],$mei['dashboard']))])['status'] === 403,
        'MEI não acessa edição de perfil de contratante');
    account_check(account_request($mei['client'],'/backend/controllers/PerfilTercerizadaController.php',['nome'=>'Sem token'])['status'] === 403,
        'Edição de perfil de MEI continua exigindo CSRF');

    foreach (['contratante','terceirizada'] as $legacyRole) {
        $legacyDoc = account_legacy_fixture($legacyRole);
        $legacy = account_client();
        account_check(account_login($legacy,$legacyDoc)['status'] === 200,'Conta legada de '.$legacyRole.' continua fazendo login');
        account_check(account_row($legacyDoc)['tipo_pessoa'] === 'juridica','Conta legada de '.$legacyRole.' mantém tipo jurídico padrão');
    }

    $orderPage = account_request($pf['client'],'/solicitacao_orcamento.php?terceirizada_id='.$mei['row']['id']);
    account_check($orderPage['status'] === 200,'Pessoa física pode abrir solicitação para um MEI');
    $order = ['csrf_token'=>account_csrf($orderPage),'terceirizada_id'=>$mei['row']['id'],'contratante_id'=>$accounts['pj']['row']['id'],
        'descricao_servico'=>'Jardinagem fictícia','localizacao_servico'=>'Endereço de teste','numero_funcionarios'=>'1','area_servico_solicitada'=>'Jardinagem'];
    account_check(account_request($pf['client'],'/backend/controllers/SolicitacaoController.php',$order)['status'] === 303,'Pessoa física solicita orçamento a MEI');
    $orderId = (int)$pdo->query('SELECT MAX(id) FROM solicitacoes WHERE contratante_id='.(int)$pf['row']['id'])->fetchColumn();
    account_check($orderId > 0,'Orçamento de CPF pertence ao contratante autenticado');
    $assignedView=account_request($mei['client'],'/perfil_empresa.php?id='.$pf['row']['id']);
    account_check(strpos($assignedView['body'],'11999990000') !== false, 'Prestador destinatário do pedido pode consultar o contato da pessoa física');
    $unrelatedView=account_request($accounts['terceirizada']['client'],'/perfil_empresa.php?id='.$pf['row']['id']);
    account_check(strpos($unrelatedView['body'],'11999990000') === false, 'Outro prestador sem vínculo não acessa o contato da pessoa física');
    $replyPage = account_request($mei['client'],'/responder_orcamento.php?id='.$orderId);
    account_check($replyPage['status'] === 200,'MEI pode abrir a resposta ao seu próprio pedido');
    $reply = ['csrf_token'=>account_csrf($replyPage),'solicitacao_id'=>$orderId,'action'=>'orcamento','valor_orcamento'=>'120.00'];
    account_check(account_request($mei['client'],'/backend/controllers/RespostaOrcamentoController.php',$reply)['status'] === 303,'MEI responde ao orçamento da pessoa física');
    account_check($pdo->query('SELECT status FROM solicitacoes WHERE id='.$orderId)->fetchColumn() === 'orçada','Resposta de MEI é registrada');

    $adminDoc = account_document('cnpj');
    $pdo->prepare('INSERT INTO administradores(login_cnpj,email,senha) VALUES(?,?,?)')
        ->execute([$adminDoc,'accounts_admin_'.strtolower($adminDoc).'@example.invalid',password_hash('Contas!2026',PASSWORD_DEFAULT)]);
    $accountTestAdminIds[] = (int)$pdo->lastInsertId();
    $admin = account_client();
    account_check(account_login($admin,$adminDoc)['status'] === 200,'Administrador temporário acessa gestão');
    $adminToken = account_csrf(account_request($admin,'/admin.php'));
    account_check(account_request($admin,'/admin.php',['csrf_token'=>$adminToken,'acao'=>'bloquear','id'=>$mei['row']['id']])['status'] === 303,'Administrador pode bloquear um MEI');
    account_check(account_request($mei['client'],$mei['dashboard'])['status'] === 403,'Sessão de MEI bloqueado perde acesso ao painel');
    account_check(account_login(account_client(),$mei['document'])['status'] === 401,'MEI bloqueado não pode entrar novamente');
    account_check(account_request($pf['client'],'/perfil_empresa.php?id='.$mei['row']['id'])['status'] === 404,'Perfil bloqueado de MEI fica indisponível');

    echo "\n$accountTestPassed verificações de cadastro e perfis aprovadas.\n";
} finally {
    // Somente documentos gerados por esta execução, sem usar contas de demonstração.
    foreach ($accountTestDocuments as $document) {
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE cpf_cnpj=? AND email LIKE ?');
        $stmt->execute([$document,'accounts_%@example.invalid']);
        $pdo->prepare('DELETE FROM login_tentativas WHERE chave=?')->execute([hash('sha256','127.0.0.1:'.$document)]);
    }
    foreach ($accountTestAdminIds as $id) $pdo->prepare('DELETE FROM administradores WHERE id=?')->execute([$id]);
    foreach (array_unique($accountTestEmails) as $email) {
        $pdo->prepare('DELETE FROM login_tentativas WHERE chave=?')->execute([hash('sha256','reset:127.0.0.1:'.$email)]);
    }
}
