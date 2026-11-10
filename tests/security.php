<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/backend/conexao.php';
if (!$is_local || $config['db_name'] !== 'serviconnect_dev' || $config['db_host'] !== '127.0.0.1') {
    throw new RuntimeException('Estes testes só podem usar o banco local de desenvolvimento.');
}
$base = 'http://127.0.0.1:8080';
$passed = 0; $ids = []; $docs = []; $tempFiles = []; $uploadedFiles = [];
function check(bool $condition, string $name): void {
    global $passed;
    if (!$condition) throw new RuntimeException('FALHOU: '.$name);
    $passed++; echo "OK: $name\n";
}
function client() {
    $ch = curl_init();
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>'',CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>10]);
    return $ch;
}
function request($ch, string $path, $data = null, bool $json = false): array {
    global $base;
    $headers = [];
    curl_setopt($ch,CURLOPT_URL,$base.$path);
    curl_setopt($ch,CURLOPT_HTTPHEADER,$json ? ['Content-Type: application/json','Accept: application/json'] : []);
    curl_setopt($ch,CURLOPT_HEADERFUNCTION,static function($ch,$line) use (&$headers) { $headers[]=$line; return strlen($line); });
    if ($data !== null) {
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$json ? json_encode($data) : $data);
    } else curl_setopt($ch,CURLOPT_HTTPGET,true);
    $body = curl_exec($ch);
    if ($body === false) throw new RuntimeException(curl_error($ch));
    return ['status'=>curl_getinfo($ch,CURLINFO_HTTP_CODE),'body'=>$body,'headers'=>implode('',$headers)];
}
function token(array $response): string {
    if (!preg_match('/name="csrf_token" value="([a-f0-9]{64})"/',$response['body'],$m)) throw new RuntimeException('Token ausente na tela.');
    return $m[1];
}
function document_fixture(): string {
    $cnpj = '7'.str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT).'0001';
    $cnpj = '0'.$cnpj;
    foreach ([12,13] as $length) {
        $sum=0; $w=$length-7;
        for($i=0;$i<$length;$i++){ $sum+=(int)$cnpj[$i]*$w; $w=$w===2?9:$w-1; }
        $cnpj.= $sum%11<2?0:11-$sum%11;
    }
    return $cnpj;
}
function fixture(string $role): array {
    global $pdo,$ids,$docs;
    $doc=document_fixture(); $docs[]=$doc;
    $pdo->prepare("INSERT INTO usuarios(cpf_cnpj,email,senha,tipo_conta,status) VALUES (?,?,?,'empresa','ativo')")
        ->execute([$doc,'test_'.$doc.'@example.invalid',password_hash('Teste!2026',PASSWORD_DEFAULT)]);
    $id=(int)$pdo->lastInsertId(); $ids[]=$id;
    $pdo->prepare('INSERT INTO empresas(usuario_id,nome,tipo_empresa,cep,logradouro,numero,bairro,cidade,estado) VALUES (?,?,?,?,?,?,?,?,?)')
        ->execute([$id,'Teste automatizado',$role,'00000000','Rua Teste','0','Teste','Teste','SP']);
    return [$id,$doc];
}
function login($ch,string $doc,string $password='Teste!2026'): array {
    $csrf=token(request($ch,'/login.php'));
    return request($ch,'/backend/controllers/AuthController.php',['identificacao'=>$doc,'senha'=>$password,'csrf_token'=>$csrf],true);
}
try {
    [$contractor,$contractorDoc]=fixture('contratante');
    [$provider,$providerDoc]=fixture('terceirizada');
    [$other,$otherDoc]=fixture('terceirizada');
    $anonymous=client(); $contractorClient=client(); $providerClient=client(); $otherClient=client(); $admin=client();
    $page=request($anonymous,'/login.php');
    check($page['status']===200 && strpos($page['headers'],'HttpOnly')!==false && strpos($page['headers'],'SameSite=Lax')!==false,'Cookie de sessão protegido');
    check(request($anonymous,'/backend/controllers/AuthController.php',['identificacao'=>$contractorDoc,'senha'=>'Teste!2026'],true)['status']===403,'Login exige CSRF');
    check(login($contractorClient,$contractorDoc)['status']===200,'Login de contratante');
    check(login($providerClient,$providerDoc)['status']===200,'Login de prestador');
    check(login($otherClient,$otherDoc)['status']===200,'Login de segundo prestador');
    check(login($admin,'90000004000162','ServiDev!2026')['status']===200,'Login de administrador');
    $csrf=token(request($contractorClient,'/solicitacao_orcamento.php?terceirizada_id='.$provider));
    $order=['csrf_token'=>$csrf,'contratante_id'=>$other,'terceirizada_id'=>$provider,'descricao_servico'=>'Jardinagem de teste','localizacao_servico'=>'Local fictício','numero_funcionarios'=>'1','area_servico_solicitada'=>'Jardinagem'];
    $without=$order; unset($without['csrf_token']);
    check(request($contractorClient,'/backend/controllers/SolicitacaoController.php',$without)['status']===403,'Pedido sem CSRF é recusado');
    check(request($contractorClient,'/backend/controllers/SolicitacaoController.php',$order)['status']===303,'Pedido válido é enviado');
    $orderId=(int)$pdo->query('SELECT MAX(id) FROM solicitacoes WHERE contratante_id='.(int)$contractor)->fetchColumn();
    check($orderId>0,'Autoria do pedido vem da sessão e ignora ID adulterado');
    $reply=['csrf_token'=>token(request($otherClient,'/dashboard_tercerizada.php')),'solicitacao_id'=>$orderId,'action'=>'orcamento','valor_orcamento'=>'150.50'];
    check(request($otherClient,'/backend/controllers/RespostaOrcamentoController.php',$reply)['status']===409,'Outro prestador não responde ao pedido');
    $reply['csrf_token']=token(request($providerClient,'/responder_orcamento.php?id='.$orderId));
    check(request($providerClient,'/backend/controllers/RespostaOrcamentoController.php',$reply)['status']===303,'Prestador destinatário responde');
    check($pdo->query('SELECT status FROM solicitacoes WHERE id='.$orderId)->fetchColumn()==='orçada','Banco aceita o estado de orçamento utilizado pelo código');
    check(request($providerClient,'/backend/controllers/RespostaOrcamentoController.php',$reply)['status']===409,'Pedido não pode ser respondido duas vezes');
    $profile=['csrf_token'=>token(request($providerClient,'/dashboard_tercerizada.php')),'texto_perfil'=>'Perfil de teste','foto_atual'=>'foto/injetado.php'];
    check(request($providerClient,'/backend/controllers/PerfilTercerizadaController.php',$profile)['status']===303,'Perfil pode ser atualizado');
    check($pdo->query('SELECT foto_path FROM empresas WHERE usuario_id='.$provider)->fetchColumn()==='img/default_avatar.svg','Foto atual enviada pelo cliente é ignorada');
    $bad=tempnam(sys_get_temp_dir(),'servi_bad'); file_put_contents($bad,'<?php echo "invalid";'); $tempFiles[]=$bad;
    $profile['foto_perfil']=new CURLFile($bad,'image/png','arquivo.php');
    check(request($providerClient,'/backend/controllers/PerfilTercerizadaController.php',$profile)['status']===422,'Conteúdo PHP disfarçado de imagem é rejeitado');
    $png=tempnam(sys_get_temp_dir(),'servi_png'); $tempFiles[]=$png;
    file_put_contents($png,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jL1sAAAAASUVORK5CYII='));
    $profile['foto_perfil']=new CURLFile($png,'image/png','foto.php');
    check(request($providerClient,'/backend/controllers/PerfilTercerizadaController.php',$profile)['status']===303,'Imagem PNG real é aceita');
    $photo=$pdo->query('SELECT foto_path FROM empresas WHERE usuario_id='.$provider)->fetchColumn(); $uploadedFiles[]=$photo;
    check((bool)preg_match('~^foto/upload_\d+_[a-f0-9]{32}\.png$~D',$photo),'Nome e extensão do upload são gerados pelo servidor');
    check(request($anonymous,'/backend/config.local.php')['status']===404,'Configuração privada não pode ser baixada');
    check(request($anonymous,'/database/migrations/001_base.sql')['status']===404,'Esquema SQL não pode ser baixado pelo site');
    check(request($anonymous,'/backend/teste_conexao.php')['status']===404,'Diagnóstico de banco não fica público');
    $adminCsrf=token(request($admin,'/admin.php'));
    check(request($admin,'/admin.php?acao=bloquear&id='.$provider)['status']===405,'Administração não altera contas por GET');
    check(request($admin,'/admin.php',['acao'=>'bloquear','id'=>$provider])['status']===403,'Administração exige CSRF');
    check(request($admin,'/admin.php',['csrf_token'=>$adminCsrf,'acao'=>'bloquear','id'=>$provider])['status']===303,'Administrador bloqueia a conta');
    check(request($providerClient,'/dashboard_tercerizada.php')['status']===403,'Bloqueio invalida acesso de sessão anterior');
    check(login(client(),$providerDoc)['status']===401,'Conta bloqueada não consegue fazer login');
    $searchHtml=explode('<section id="pedidos"',request($contractorClient,'/dashboard_contratante.php')['body'])[0];
    check(strpos($searchHtml,'perfil_empresa.php?id='.$provider.'"')===false,'Conta bloqueada não aparece na lista de prestadores');
    check(request($contractorClient,'/perfil_empresa.php?id='.$provider)['status']===404,'Perfil indisponível não revela os dados');
    check(request($contractorClient,'/backend/controllers/SolicitacaoController.php',$order)['status']===422,'Prestador bloqueado não recebe novos pedidos');
    check(request($admin,'/admin.php',['csrf_token'=>$adminCsrf,'acao'=>'arquivar','id'=>$provider])['status']===303,'Conta é arquivada sem exclusão');
    check((int)$pdo->query('SELECT COUNT(*) FROM empresas WHERE usuario_id='.$provider)->fetchColumn()===1,'Arquivamento preserva o perfil');
    check((int)$pdo->query('SELECT COUNT(*) FROM solicitacoes WHERE id='.$orderId)->fetchColumn()===1,'Arquivamento preserva os pedidos');
    check(request($admin,'/arquivados.php',['csrf_token'=>$adminCsrf,'acao'=>'desarquivar','id'=>$provider])['status']===303,'Conta é restaurada com o mesmo ID');
    check(login($providerClient,$providerDoc)['status']===200,'Conta restaurada volta a acessar o perfil');
    $register=client(); $regToken=token(request($register,'/cadastro_empresa.php'));
    $registration=['csrf_token'=>$regToken,'cnpj'=>document_fixture(),'email_acesso'=>'invalid','senha'=>'123','tipo'=>'admin'];
    check(request($register,'/backend/controllers/CadastroEmpresaController.php',$registration,true)['status']===422,'Cadastro rejeita papel arbitrário e dados inválidos');
    $newDoc=document_fixture(); $docs[]=$newDoc;
    $registration=['csrf_token'=>$regToken,'cnpj'=>$newDoc,'email_acesso'=>'test_'.$newDoc.'@example.invalid','senha'=>'Teste!2026','tipo'=>'contratante','nome'=>'Teste de cadastro'];
    $registered=request($register,'/backend/controllers/CadastroEmpresaController.php',$registration,true);
    if ($registered['status']!==200) throw new RuntimeException('Cadastro de teste: HTTP '.$registered['status'].' '.$registered['body']);
    check(true,'Cadastro válido funciona com validação no servidor');
    $ids[]=(int)$pdo->query('SELECT id FROM usuarios WHERE cpf_cnpj='.$pdo->quote($newDoc))->fetchColumn();
    check(request($register,'/backend/controllers/CadastroEmpresaController.php',$registration,true)['status']===409,'Documento duplicado é recusado');
    $resetClient=client(); $resetCsrf=token(request($resetClient,'/esqueci_senha.php'));
    $reset=request($resetClient,'/esqueci_senha.php',['csrf_token'=>$resetCsrf,'email'=>'test_'.$contractorDoc.'@example.invalid']);
    check($reset['status']===200 && preg_match('/redefinir_senha.php\?token=([a-f0-9]{64})/',$reset['body'],$m)===1,'Recuperação local gera link de teste');
    $resetToken=$m[1];
    $stored=$pdo->query('SELECT reset_token FROM usuarios WHERE id='.$contractor)->fetchColumn();
    check($stored===hash('sha256',$resetToken) && $stored!==$resetToken,'Token de recuperação é armazenado como hash');
    $resetPath='/redefinir_senha.php?token='.$resetToken;
    $resetCsrf=token(request($resetClient,$resetPath));
    $resetData=['csrf_token'=>$resetCsrf,'senha'=>'NovaSenha!2026','confirmar_senha'=>'NovaSenha!2026'];
    check(request($resetClient,$resetPath,$resetData)['status']===200,'Redefinição de senha funciona');
    check(request($resetClient,$resetPath,$resetData)['status']===400,'Link de recuperação não pode ser reutilizado');
    check(login($contractorClient,$contractorDoc,'NovaSenha!2026')['status']===200,'Nova senha permite login');
    $limit=client();
    for($i=0;$i<10;$i++) login($limit,$otherDoc,'SenhaIncorreta');
    check(login($limit,$otherDoc,'SenhaIncorreta')['status']===429,'Tentativas de login têm limite no servidor');
    $denied=false;
    try { $pdo->exec('CREATE TABLE teste_permissao_ddl (id INT)'); } catch (PDOException $e) { $denied=true; }
    check($denied,'Usuário da aplicação não pode criar ou alterar tabelas');
    $logoutCsrf=token(request($contractorClient,'/dashboard_contratante.php'));
    check(request($contractorClient,'/logout.php')['status']===405,'Logout exige POST');
    check(request($contractorClient,'/logout.php',['csrf_token'=>$logoutCsrf])['status']===303,'Logout protegido funciona');
    check(request($contractorClient,'/dashboard_contratante.php')['status']===303,'Sessão encerrada não acessa o painel');
    echo "\n$passed verificações aprovadas.\n";
} finally {
    foreach($ids as $id) $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
    foreach($docs as $doc) {
        foreach(['127.0.0.1:'.$doc,'reset:127.0.0.1:test_'.$doc.'@example.invalid'] as $key) $pdo->prepare('DELETE FROM login_tentativas WHERE chave=?')->execute([hash('sha256',$key)]);
    }
    foreach($tempFiles as $file) if(is_file($file)) unlink($file);
    foreach($uploadedFiles as $file) if(preg_match('~^foto/upload_\d+_[a-f0-9]{32}\.png$~D',$file)) @unlink(dirname(__DIR__).'/'.$file);
}
