<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/backend/conexao.php';
require_once dirname(__DIR__) . '/backend/opencnpj.php';
$base = getenv('SERVICONNECT_TEST_BASE');
$fixturePath = getenv('OPENCNPJ_FIXTURE_FILE');
if (!$is_local || $config['db_host'] !== '127.0.0.1' || $config['db_name'] !== 'serviconnect_dev'
    || $config['db_port'] !== 3307 || $base !== 'http://127.0.0.1:8082' || !$fixturePath || !is_file($fixturePath)) {
    throw new RuntimeException('Use tools/test-cnpj.ps1; exige banco de desenvolvimento separado e simulador local.');
}
$passed = 0; $emails = [];
function cnpj_check(bool $condition, string $name): void {
    global $passed;
    if (!$condition) throw new RuntimeException('FALHOU: ' . $name);
    $passed++; echo "OK: $name\n";
}
function cnpj_fixture(array $response = []): void {
    global $fixturePath;
    file_put_contents($fixturePath, json_encode(['default'=>$response]));
}
function cnpj_client() {
    $client = curl_init();
    curl_setopt_array($client,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>'',CURLOPT_TIMEOUT=>20]);
    return $client;
}
function cnpj_request($client, string $path, ?array $data = null): array {
    global $base;
    curl_setopt($client,CURLOPT_URL,$base.$path);
    curl_setopt($client,CURLOPT_HTTPHEADER,$data === null ? [] : ['Content-Type: application/json','Accept: application/json']);
    if ($data === null) curl_setopt($client,CURLOPT_HTTPGET,true);
    else { curl_setopt($client,CURLOPT_POST,true); curl_setopt($client,CURLOPT_POSTFIELDS,json_encode($data)); }
    $body = curl_exec($client);
    if ($body === false) throw new RuntimeException(curl_error($client));
    return ['status'=>(int)curl_getinfo($client,CURLINFO_HTTP_CODE),'body'=>$body,'json'=>json_decode($body,true)];
}
function cnpj_csrf($client): string {
    $page = cnpj_request($client,'/cadastro_empresa.php');
    if (!preg_match('/name="csrf_token" value="([a-f0-9]{64})"/',$page['body'],$matches)) throw new RuntimeException('CSRF ausente.');
    return $matches[1];
}
function cnpj_document(): string {
    $document = '99'.str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT).'0001';
    foreach ([12,13] as $length) {
        $sum=0; $weight=$length-7;
        for ($i=0; $i<$length; $i++) { $sum+=(int)$document[$i]*$weight; $weight=$weight===2?9:$weight-1; }
        $document .= $sum%11<2?'0':(string)(11-$sum%11);
    }
    return $document;
}
function cnpj_payload(string $document, string $type = 'mei'): array {
    global $emails;
    $email='cnpjtest_'.bin2hex(random_bytes(6)).'@example.invalid'; $emails[]=$email;
    return ['tipo'=>$type,'tipo_pessoa'=>'juridica','documento'=>$document,'nome'=>'Empresa fictícia de teste',
        'email_acesso'=>$email,'senha'=>'Cnpj!2026','confirmar_senha'=>'Cnpj!2026'];
}
function cnpj_register($client, array $data): array {
    $data['csrf_token']=cnpj_csrf($client);
    return cnpj_request($client,'/backend/controllers/CadastroEmpresaController.php',$data);
}
function cnpj_exists(string $email): bool {
    global $pdo;
    $query=$pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email=?');$query->execute([$email]);
    return (int)$query->fetchColumn()>0;
}
try {
    $pdo->prepare('DELETE FROM login_tentativas WHERE chave=?')->execute([hash('sha256','opencnpj:127.0.0.1')]);
    cnpj_check(opencnpj_api_base()==='https://api.opencnpj.org','CLI nunca utiliza substituto da API de testes');
    $client=cnpj_client();
    cnpj_check(cnpj_request($client,'/backend/controllers/ConsultaCnpjController.php')['status']===405,'Consulta exige POST');
    cnpj_check(cnpj_request($client,'/backend/controllers/ConsultaCnpjController.php',['documento'=>'00000000000191'])['status']===403,'Consulta exige CSRF');
    $token=cnpj_csrf($client);
    cnpj_check(cnpj_request($client,'/backend/controllers/ConsultaCnpjController.php',['documento'=>'11111111111111','csrf_token'=>$token])['status']===422,'CNPJ inválido é rejeitado antes da API');
    foreach (['Baixada','Suspensa','Inapta','Nula'] as $situation) {
        cnpj_fixture(['situacao'=>$situation]);
        foreach (['mei','terceirizada','contratante'] as $type) {
            $data=cnpj_payload(cnpj_document(),$type);$result=cnpj_register($client,$data);
            cnpj_check($result['status']===422 && str_contains($result['json']['message']??'','ativa'),"$type com CNPJ $situation é recusado");
            cnpj_check(!cnpj_exists($data['email_acesso']),"Recusa $situation não grava conta $type");
        }
    }
    $cases=[
        'inexistente'=>[['status'=>404,'body'=>'{"error":"not found"}'],422],
        'API fora do ar'=>[['status'=>500,'body'=>'segredo de erro interno'],503],
        'limite da API'=>[['status'=>429],503],
        'redirecionamento'=>[['status'=>302],503],
        'JSON inválido'=>[['body'=>'<html>erro interno</html>'],503],
        'JSON nulo'=>[['body'=>'null'],503],
        'JSON com booleano'=>[['body'=>'true'],503],
        'situação ausente'=>[['body'=>'{"cnpj":"00000000000191"}'],503],
        'CNPJ divergente'=>[['body'=>'{"cnpj":"00000000000191","situacao_cadastral":"Ativa"}'],503],
        'resposta exagerada'=>[['body'=>str_repeat('x',262145)],503],
        'tempo esgotado'=>[['delay'=>13],503],
    ];
    foreach ($cases as $name=>[$fixture,$expected]) {
        cnpj_fixture($fixture);$data=cnpj_payload(cnpj_document());
        $result=cnpj_register($client,$data);
        cnpj_check($result['status']===$expected && ($result['json']['success']??null)===false,"Falha tratada: $name");
        cnpj_check(!cnpj_exists($data['email_acesso']),"Sem criação em $name");
        cnpj_check(!str_contains($result['body'],'erro interno') && !str_contains($result['body'],'segredo'),"Erro público seguro em $name");
    }
    // A consulta anterior, e flags forjadas no JSON, não autorizam o cadastro.
    cnpj_fixture();$document=cnpj_document();$token=cnpj_csrf($client);
    $preview=cnpj_request($client,'/backend/controllers/ConsultaCnpjController.php',['documento'=>$document,'csrf_token'=>$token]);
    cnpj_check($preview['status']===200 && $preview['json']['empresa']['situacao_cadastral']==='Ativa','Consulta informa CNPJ ativo');
    cnpj_fixture(['situacao'=>'Baixada']);$data=cnpj_payload($document);
    $data['cnpj_validado']=true;$data['situacao_cadastral']='Ativa';$data['empresa']=['situacao_cadastral'=>'Ativa'];
    cnpj_check(cnpj_register($client,$data)['status']===422 && !cnpj_exists($data['email_acesso']),'Servidor reconsulta e ignora confirmação forjada ou antiga');
    cnpj_fixture();
    foreach (['mei','terceirizada','contratante'] as $type) {
        $data=cnpj_payload(cnpj_document(),$type);$result=cnpj_register($client,$data);
        cnpj_check($result['status']===200 && cnpj_exists($data['email_acesso']),"CNPJ ativo permite criar $type");
    }
    cnpj_fixture(['status'=>404]);$data=cnpj_payload(cnpj_document(),'terceirizada');$data['cnpj']=$data['documento'];unset($data['documento']);
    cnpj_check(cnpj_register($client,$data)['status']===422 && !cnpj_exists($data['email_acesso']),'Payload legado também exige CNPJ existente');
    $cpf='8'.str_pad((string)random_int(0,99999999),8,'0',STR_PAD_LEFT);
    foreach ([9,10] as $length) {
        $sum=0;for ($i=0;$i<$length;$i++) $sum+=(int)$cpf[$i]*($length+1-$i);
        $cpf.=$sum%11<2?'0':(string)(11-$sum%11);
    }
    cnpj_fixture(['status'=>500]);$data=cnpj_payload($cpf,'contratante');$data['tipo_pessoa']='fisica';
    cnpj_check(cnpj_register($client,$data)['status']===200 && cnpj_exists($data['email_acesso']),'Pessoa física continua cadastrando sem consultar OpenCNPJ');
    $key=hash('sha256','opencnpj:127.0.0.1');
    $pdo->prepare('UPDATE login_tentativas SET tentativas=60,janela_inicio=NOW() WHERE chave=?')->execute([$key]);
    $data=cnpj_payload(cnpj_document());
    cnpj_check(cnpj_register(cnpj_client(),$data)['status']===429 && !cnpj_exists($data['email_acesso']),'Limite por IP bloqueia excesso mesmo com nova sessão');
    echo "\n$passed verificações OpenCNPJ aprovadas.\n";
} finally {
    cnpj_fixture();
    foreach ($emails as $email) $pdo->prepare('DELETE FROM usuarios WHERE email=?')->execute([$email]);
    $pdo->prepare('DELETE FROM login_tentativas WHERE chave=?')->execute([hash('sha256','opencnpj:127.0.0.1')]);
}
