<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/backend/conexao.php';
require_once dirname(__DIR__) . '/backend/orders.php';
if (!$is_local || $config['db_name'] !== 'serviconnect_dev' || $config['db_host'] !== '127.0.0.1') throw new RuntimeException('Somente banco separado de desenvolvimento.');
$wfIds=[];$wfDocs=[];$wfPassed=0;$wfMarker='Fluxo'.bin2hex(random_bytes(4));$wfPassword='Fluxo!2026';$wfHash=password_hash($wfPassword,PASSWORD_DEFAULT);
function wf_check(bool $ok,string $label):void{global $wfPassed;if(!$ok)throw new RuntimeException('FALHOU: '.$label);$wfPassed++;echo "OK: $label\n";}
function wf_request($client,string $path,?array $data=null,bool $json=false):array{
    $headers=[];curl_setopt($client,CURLOPT_URL,'http://127.0.0.1:8080'.$path);curl_setopt($client,CURLOPT_HTTPHEADER,$json?['Content-Type: application/json','Accept: application/json']:[]);
    curl_setopt($client,CURLOPT_HEADERFUNCTION,static function($ch,$line)use(&$headers){$headers[]=$line;return strlen($line);});
    if($data===null)curl_setopt($client,CURLOPT_HTTPGET,true);else{curl_setopt($client,CURLOPT_POST,true);curl_setopt($client,CURLOPT_POSTFIELDS,$json?json_encode($data):http_build_query($data));}
    $body=curl_exec($client);if($body===false)throw new RuntimeException(curl_error($client));return ['status'=>curl_getinfo($client,CURLINFO_HTTP_CODE),'body'=>$body,'headers'=>implode('',$headers)];
}
function wf_token(array $page):string{if(!preg_match('/name="csrf_token" value="([a-f0-9]{64})"/',$page['body'],$m))throw new RuntimeException('Token ausente.');return $m[1];}
function wf_actor(string $role):array{
    global $pdo,$wfIds,$wfDocs,$wfMarker,$wfHash,$wfPassword;
    do{$doc='8'.str_pad((string)random_int(0,999999999),9,'0',STR_PAD_LEFT).'0000';$s=$pdo->prepare('SELECT 1 FROM usuarios WHERE cpf_cnpj=?');$s->execute([$doc]);}while($s->fetchColumn());
    $pdo->prepare("INSERT INTO usuarios(cpf_cnpj,email,senha,tipo_conta,status) VALUES(?,?,?,'empresa','ativo')")->execute([$doc,'workflow_'.$doc.'@example.invalid',$wfHash]);
    $id=(int)$pdo->lastInsertId();$wfIds[]=$id;$wfDocs[]=$doc;
    $name=$role.' '.$wfMarker.' '.$id;
    $pdo->prepare('INSERT INTO empresas(usuario_id,nome,tipo_empresa,cep,logradouro,numero,bairro,cidade,estado) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$id,$name,$role,'01001000','Rua de teste','100','Centro','São Paulo','SP']);
    $client=curl_init();curl_setopt_array($client,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>'',CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15]);
    $csrf=wf_token(wf_request($client,'/login.php'));$login=wf_request($client,'/backend/controllers/AuthController.php',['identificacao'=>$doc,'senha'=>$wfPassword,'csrf_token'=>$csrf],true);
    wf_check($login['status']===200,'Conta de teste autenticada: '.$role);
    return ['id'=>$id,'doc'=>$doc,'name'=>$name,'client'=>$client,'csrf'=>wf_token(wf_request($client,'/'.($role==='contratante'?'dashboard_contratante.php':'dashboard_tercerizada.php')))];
}
function wf_create(array $client,array $provider,array $overrides=[]):array{
    global $wfMarker;
    $data=array_replace(['csrf_token'=>$client['csrf'],'terceirizada_id'=>(string)$provider['id'],'servico_id'=>'jardinagem','area_servico_solicitada'=>'Nome forjado','descricao_servico'=>$wfMarker.' <script>alert(1)</script> pedido de jardim','localizacao_servico'=>'Local privado do pedido','numero_funcionarios'=>'1','frequencia'=>'semanal','data_preferida'=>date('Y-m-d',strtotime('+10 days')),'chave_criacao'=>bin2hex(random_bytes(32))],$overrides);
    $response=wf_request($client['client'],'/backend/controllers/SolicitacaoController.php',$data);preg_match('/Location: \/orcamento.php\?id=(\d+)/i',$response['headers'],$m);
    return ['response'=>$response,'id'=>(int)($m[1]??0),'data'=>$data];
}
function wf_row(int $id):array{global $pdo;$s=$pdo->prepare('SELECT * FROM solicitacoes WHERE id=?');$s->execute([$id]);return $s->fetch();}
function wf_action(array $actor,int $id,string $action,array $data=[]):array{
    return wf_request($actor['client'],'/backend/controllers/OrcamentoAcaoController.php',array_replace(['csrf_token'=>$actor['csrf'],'solicitacao_id'=>(string)$id,'versao'=>(string)wf_row($id)['versao'],'acao'=>$action],$data));
}
function wf_proposal(array $actor,int $id,array $data=[]):array{
    return wf_request($actor['client'],'/backend/controllers/RespostaOrcamentoController.php',array_replace(['csrf_token'=>$actor['csrf'],'solicitacao_id'=>(string)$id,'versao'=>(string)wf_row($id)['versao'],'action'=>'orcamento','valor_orcamento'=>'1.250,50','prazo_execucao'=>'2 dias após agendamento','condicoes_pagamento'=>'Pix após concluir','itens_inclusos'=>'Ferramentas e mão de obra','itens_exclusos'=>'Materiais adicionais','mensagem_proposta'=>'Proposta de teste','validade_proposta'=>date('Y-m-d',strtotime('+7 days'))],$data));
}
try{
    $client=wf_actor('contratante');$provider=wf_actor('terceirizada');$mei=wf_actor('mei');$outsider=wf_actor('terceirizada');$otherClient=wf_actor('contratante');
    $created=wf_create($client,$provider,['contratante_id'=>(string)$otherClient['id']]);$id=$created['id'];
    wf_check($created['response']['status']===303&&$id>0,'Pedido real é criado com redirecionamento ao acompanhamento');
    wf_check((int)wf_row($id)['contratante_id']===$client['id'],'Identidade do contratante vem da sessão');
    wf_check(wf_row($id)['area_servico_solicitada']==='Jardinagem','Serviço selecionado usa o nome canônico do catálogo');
    wf_check(wf_row($id)['frequencia']==='semanal'&&wf_row($id)['data_preferida']!==null,'Frequência e data desejada são preservadas');
    $again=wf_request($client['client'],'/backend/controllers/SolicitacaoController.php',$created['data']);
    wf_check($again['status']===303&&strpos($again['headers'],'id='.$id)!==false,'Reenvio do mesmo formulário reutiliza o pedido');
    $s=$pdo->prepare('SELECT COUNT(*) FROM solicitacoes WHERE chave_criacao=?');$s->execute([$created['data']['chave_criacao']]);wf_check((int)$s->fetchColumn()===1,'Reenvio não duplica a solicitação');
    wf_check(wf_create($client,$provider,['servico_id'=>'inexistente'])['response']['status']===422,'Serviço forjado é rejeitado');
    wf_check(wf_create($client,$provider,['frequencia'=>'forjada'])['response']['status']===422,'Frequência inválida é rejeitada');
    wf_check(wf_create($client,$provider,['data_preferida'=>'2020-02-30'])['response']['status']===422,'Data inválida é rejeitada');
    wf_check(wf_request($outsider['client'],'/orcamento.php?id='.$id)['status']===404,'Terceiro não acessa detalhes do pedido');
    wf_check(wf_request($otherClient['client'],'/orcamento.php?id='.$id)['status']===404,'Outro contratante não acessa o pedido');
    wf_check(wf_action($outsider,$id,'mensagem',['mensagem'=>'Invasão'])['status']===404,'Terceiro não envia mensagens ao pedido');
    wf_check(wf_proposal($outsider,$id)['status']===409,'Outro prestador não envia proposta');
    wf_check(wf_action($client,$id,'iniciar_servico')['status']===409,'Contratante não inicia execução');
    wf_check(wf_proposal($provider,$id,['valor_orcamento'=>'-1'])['status']===422,'Proposta negativa é rejeitada');
    wf_check(wf_proposal($provider,$id)['status']===303,'Prestador envia proposta completa');
    wf_check(wf_row($id)['valor_orcamento']==='1250.50'&&wf_row($id)['status']==='orçada','Valor brasileiro é salvo sem ambiguidade');
    wf_check(wf_row($id)['condicoes_pagamento']==='Pix após concluir','Condições de pagamento são preservadas');
    wf_check(wf_proposal($provider,$id)['status']===409,'Resposta repetida não sobrescreve proposta');
    wf_check(wf_proposal($provider,$id,['action'=>'revisao','versao'=>'0'])['status']===409,'Versão antiga não sobrescreve atualização');
    wf_check(wf_proposal($provider,$id,['action'=>'revisao','valor_orcamento'=>'1.300,00'])['status']===303,'Proposta pendente pode ser revisada');
    $s=$pdo->prepare("SELECT dados_json FROM solicitacoes_eventos WHERE solicitacao_id=? AND tipo IN ('enviar_proposta','revisar_proposta') ORDER BY id");$s->execute([$id]);$snapshots=array_map(static fn($json)=>json_decode($json,true),$s->fetchAll(PDO::FETCH_COLUMN));
    wf_check(count($snapshots)===2&&$snapshots[0]['valor_orcamento']==='1250.50'&&$snapshots[1]['valor_orcamento']==='1300.00','Histórico preserva valor e condições de cada revisão');
    wf_check(wf_action($provider,$id,'mensagem',['mensagem'=>'Posso atender na próxima semana.'])['status']===303,'Prestador pode esclarecer o pedido pelo histórico');
    wf_check(wf_action($client,$id,'aceitar_proposta')['status']===422,'Aceite exige confirmação das condições');
    $pdo->prepare('UPDATE solicitacoes SET validade_proposta=? WHERE id=?')->execute([date('Y-m-d',strtotime('-1 day')),$id]);
    wf_check(wf_action($client,$id,'aceitar_proposta',['confirmacao'=>'1'])['status']===409,'Proposta vencida não pode ser aceita');
    $pdo->prepare('UPDATE solicitacoes SET validade_proposta=? WHERE id=?')->execute([date('Y-m-d',strtotime('+7 days')),$id]);
    wf_check(wf_action($client,$id,'aceitar_proposta',['confirmacao'=>'1'])['status']===303&&wf_row($id)['status']==='aceita','Contratante aceita a proposta atual');
    wf_check(wf_proposal($provider,$id,['action'=>'revisao','valor_orcamento'=>'5000'])['status']===409,'Proposta aceita não pode mudar de preço');
    wf_check(wf_action($provider,$id,'iniciar_servico')['status']===303&&wf_row($id)['status']==='em_execucao','Prestador informa início da execução');
    wf_check(wf_action($client,$id,'confirmar_conclusao')['status']===409,'Conclusão não pode ser antecipada pelo cliente');
    wf_check(wf_action($provider,$id,'finalizar_servico')['status']===303&&wf_row($id)['status']==='aguardando_confirmacao','Finalização aguarda confirmação do cliente');
    wf_check(wf_action($client,$id,'solicitar_ajuste')['status']===422,'Pedido de ajuste exige descrição');
    wf_check(wf_action($client,$id,'solicitar_ajuste',['mensagem'=>'Faltou retirar os resíduos.'])['status']===303&&wf_row($id)['status']==='em_execucao','Ajuste devolve o serviço à execução');
    wf_check(wf_action($provider,$id,'finalizar_servico',['mensagem'=>'Resíduos retirados.'])['status']===303,'Prestador informa a correção');
    wf_check(wf_action($client,$id,'confirmar_conclusao')['status']===303&&wf_row($id)['status']==='concluida','Cliente confirma a conclusão');
    wf_check(wf_action($provider,$id,'cancelar',['mensagem'=>'Cancelar após concluir'])['status']===409,'Pedido concluído não pode ser cancelado');
    wf_check(wf_action($client,$id,'mensagem',['mensagem'=>'<img src=x onerror=alert(1)> Obrigado!'])['status']===303,'Observações finais permanecem no histórico');
    $detail=wf_request($client['client'],'/orcamento.php?id='.$id);
    wf_check($detail['status']===200&&strpos($detail['body'],'Conclusão confirmada')!==false,'Cliente consulta o histórico completo');
    wf_check(strpos(wf_request($provider['client'],'/orcamento.php?id='.$id)['body'],'Faltou retirar os resíduos.')!==false,'Prestador consulta os mesmos movimentos');
    wf_check(strpos($detail['body'],'<script>alert(1)')===false&&strpos($detail['body'],'<img src=x')===false,'Descrição e mensagens não executam HTML');
    wf_check(strpos($detail['body'],'1.250,50')!==false&&strpos($detail['body'],'1.300,00')!==false,'Histórico mostra propostas antigas e atuais');
    wf_check(wf_request($client['client'],'/backend/controllers/OrcamentoAcaoController.php')['status']===405,'Mudanças de orçamento exigem POST');
    wf_check(wf_request($client['client'],'/backend/controllers/OrcamentoAcaoController.php',['solicitacao_id'=>$id,'acao'=>'mensagem','mensagem'=>'Sem token'])['status']===403,'Mudanças de orçamento exigem CSRF');
    $cancel=wf_create($client,$provider);wf_check(wf_action($client,$cancel['id'],'cancelar',['mensagem'=>'Não preciso mais do serviço.'])['status']===303,'Cliente cancela um pedido antes da proposta');
    $reject=wf_create($client,$provider);wf_check(wf_proposal($provider,$reject['id'],['action'=>'recusa','mensagem'=>'Agenda indisponível.'])['status']===303,'Prestador pode recusar uma solicitação');
    $decline=wf_create($client,$provider);wf_proposal($provider,$decline['id']);wf_check(wf_action($client,$decline['id'],'recusar_proposta',['mensagem'=>'Valor fora do orçamento.'])['status']===303,'Cliente pode recusar uma proposta');
    for($i=0;$i<11;$i++)wf_create($client,$provider);
    $list=wf_request($client['client'],'/orcamentos.php');
    wf_check(substr_count($list['body'],'class="surface order-card"')===10&&strpos($list['body'],'rel="next"')!==false,'Histórico lista até 10 pedidos por página');
    wf_check(wf_request($client['client'],'/orcamentos.php?pagina=2')['status']===200,'Segunda página do histórico está acessível');
    wf_check(strpos(wf_request($client['client'],'/orcamentos.php?estado=concluida')['body'],'Concluído')!==false,'Filtro do histórico encontra pedido concluído');
    wf_check(strpos(wf_request($outsider['client'],'/orcamentos.php')['body'],'Local privado do pedido')===false,'Listagem não expõe pedidos de outros participantes');

    $sp=json_decode(file_get_contents(dirname(__DIR__).'/data/cidades/SP.json'),true);
    $saoPaulo=array_values(array_filter($sp,static fn($city)=>$city['nome']==='São Paulo'))[0];
    $selected=array_slice(array_keys(service_catalog()),0,20);$regions=array_map(static fn($city)=>['id'=>$city['id'],'uf'=>'SP','cidade'=>$city['nome']],array_slice($sp,0,20));
    $profileData=['csrf_token'=>$provider['csrf'],'perfil_estruturado'=>'1','servicos_selecionados'=>'1','servicos'=>$selected,'nome'=>$provider['name'],'texto_perfil'=>'Atendimento de teste para serviços com experiência e organização.','telefone'=>'11999990000','website'=>'https://example.invalid/empresa','experiencia_anos'=>'7','pagamentos'=>['pix','credito'],'modalidades'=>['domicilio','remoto'],'dias_atendimento'=>['seg','qua','sex'],'horario_inicio'=>'08:00','horario_fim'=>'18:00','emite_nota'=>'1','cep_empresa'=>'01001000','logradouro_empresa'=>'Rua de teste','numero_empresa'=>'100','bairro_empresa'=>'Centro','cidade_empresa'=>'São Paulo','estado_empresa'=>'SP','municipio_ibge'=>$saoPaulo['id'],'cidades_atendidas'=>json_encode($regions),'mapa_publico'=>'1','mapa_alterado'=>'1','latitude'=>'-23.55','longitude'=>'-46.63'];
    $profileResponse=wf_request($provider['client'],'/backend/controllers/PerfilTercerizadaController.php',$profileData);
    wf_check($profileResponse['status']===303,'Perfil salva serviços, cidades, horários e localização');
    wf_check(count(provider_services($pdo,$provider['id']))===20,'Serviços ficam armazenados separadamente, sem truncar a lista');
    $s=$pdo->prepare('SELECT * FROM empresas WHERE usuario_id=?');$s->execute([$provider['id']]);$stored=$s->fetch();
    wf_check($stored['experiencia_anos']==7&&$stored['website']==='https://example.invalid/empresa','Experiência e site são preservados');
    wf_check(selected_options($stored['pagamentos'])===['pix','credito']&&selected_options($stored['modalidades'])===['domicilio','remoto'],'Pagamentos e modalidades usam opções válidas');
    wf_check(count(selected_options($stored['cidades_atendidas']))===20&&mb_strlen($stored['regiao'])<=255,'Cidades completas não são perdidas na descrição resumida');
    wf_check((int)$stored['mapa_publico']===1&&$stored['latitude']==='-23.55000000','Ponto da empresa é salvo com autorização explícita');
    $page=wf_request($client['client'],'/perfil_empresa.php?id='.$provider['id']);
    wf_check(strpos($page['body'],'data-provider-map')!==false&&strpos($page['body'],'OpenStreetMap')!==false,'Perfil comercial autorizado inclui mapa');
    wf_check(strpos($page['body'],'Pix')!==false&&strpos($page['body'],'Remoto')!==false,'Perfil mostra informações estruturadas');
    $lastService=service_catalog()[$selected[19]];
    require_once dirname(__DIR__).'/backend/search.php';
    $found=search_providers($pdo,search_filters(['q'=>$provider['name'].' '.$lastService]));
    wf_check($found['total']===1,'Busca encontra serviço além dos 100 caracteres do resumo');
    wf_check(search_providers($pdo,search_filters(['q'=>$provider['name'],'servico'=>$selected[19]]))['total']===1,'Filtro exato de serviço encontra o prestador');
    wf_check(search_providers($pdo,search_filters(['q'=>$provider['name'].' jardim']))['total']===1,'Busca relaciona jardim ao serviço selecionado de jardinagem');
    wf_check(search_providers($pdo,search_filters(['q'=>$provider['name'].' eletricista']))['total']===1,'Busca reconhece o nome popular de um serviço do catálogo');
    wf_check(search_providers($pdo,search_filters(['q'=>$provider['name'],'local'=>$regions[19]['cidade']]))['total']===1,'Busca encontra todas as cidades selecionadas');
    $bad=$profileData;$bad['servicos']=['servico-inventado'];wf_check(wf_request($provider['client'],'/backend/controllers/PerfilTercerizadaController.php',$bad)['status']===422,'Serviço fora do catálogo é rejeitado');
    $bad=$profileData;$bad['servicos']=array_slice(array_keys(service_catalog()),0,21);wf_check(wf_request($provider['client'],'/backend/controllers/PerfilTercerizadaController.php',$bad)['status']===422,'Limite de serviços é validado no servidor');
    $bad=$profileData;$bad['website']='javascript:alert(1)';wf_check(wf_request($provider['client'],'/backend/controllers/PerfilTercerizadaController.php',$bad)['status']===422,'Perfil não aceita site com protocolo executável');
    $bad=$profileData;$bad['pagamentos']=['fraude'];wf_check(wf_request($provider['client'],'/backend/controllers/PerfilTercerizadaController.php',$bad)['status']===422,'Opções de pagamento forjadas são rejeitadas');
    $bad=$profileData;$bad['latitude']='91';wf_check(wf_request($provider['client'],'/backend/controllers/PerfilTercerizadaController.php',$bad)['status']===422,'Latitude fora dos limites é rejeitada');
    $bad=$profileData;$bad['municipio_ibge']='0000000';wf_check(wf_request($provider['client'],'/backend/controllers/PerfilTercerizadaController.php',$bad)['status']===422,'Cidade que não existe no catálogo é rejeitada');
    $m=$profileData;$m['csrf_token']=$mei['csrf'];$m['nome']=$mei['name'];
    wf_check(wf_request($mei['client'],'/backend/controllers/PerfilTercerizadaController.php',$m)['status']===303,'MEI pode completar perfil usando os mesmos seletores');
    $s->execute([$mei['id']]);$meiStored=$s->fetch();
    wf_check((int)$meiStored['mapa_publico']===0&&$meiStored['latitude']===null&&$meiStored['longitude']===null,'MEI não publica coordenadas mesmo com POST forjado');
    $meiView=wf_request($client['client'],'/perfil_empresa.php?id='.$mei['id']);
    wf_check(strpos($meiView['body'],'Rua de teste')===false&&strpos($meiView['body'],'data-provider-map')===false,'Perfil de MEI preserva endereço e ponto residencial');
    $changed=$profileData;$changed['numero_empresa']='200';$changed['mapa_alterado']='0';
    wf_check(wf_request($provider['client'],'/backend/controllers/PerfilTercerizadaController.php',$changed)['status']===303,'Endereço da empresa pode ser atualizado');
    $s->execute([$provider['id']]);$after=$s->fetch();
    wf_check($after['latitude']===null&&(int)$after['mapa_publico']===0,'Trocar o endereço invalida o ponto anterior');
    wf_check(count(provider_services($pdo,$provider['id']))===20,'Salvar novamente não duplica os serviços');
    $chatController = '/backend/controllers/ChatController.php';
    $providerProfile = wf_request($client['client'], '/perfil_empresa.php?id=' . $provider['id']);
    $draft = '/conversas.php?prestador=' . $provider['id'];
    wf_check(strpos($providerProfile['body'], substr($draft, 1)) !== false, 'Perfil permite conversar antes de pedir orçamento');
    wf_check(wf_request($client['client'], $draft)['status'] === 200, 'Contratante abre conversa pelo perfil');
    $firstMessage = [
        'csrf_token' => $client['csrf'], 'prestador_id' => $provider['id'], 'chave_envio' => bin2hex(random_bytes(16)),
        'mensagem' => 'Vocês atendem neste bairro? <b>texto</b>',
    ];
    $started = wf_request($client['client'], $chatController, $firstMessage);
    preg_match('~Location: /conversas\.php\?id=(\d+)~i', $started['headers'], $match);
    $conversation = (int)($match[1] ?? 0);
    wf_check($started['status'] === 303 && $conversation > 0, 'Primeira mensagem cria conversa sem criar orçamento');
    wf_check(wf_request($client['client'], $chatController, $firstMessage)['status'] === 303, 'Reenvio da primeira mensagem é aceito');
    $pollUrl = $chatController . '?id=' . $conversation;
    $providerMessages = json_decode(wf_request($provider['client'], $pollUrl)['body'], true, 512, JSON_THROW_ON_ERROR)['messages'];
    wf_check(count($providerMessages) === 1 && !$providerMessages[0]['mine'], 'Prestador recebe a mensagem uma vez');
    $chatUrl = '/conversas.php?id=' . $conversation;
    $inbox = wf_request($provider['client'], '/conversas.php');
    wf_check(strpos($inbox['body'], substr($chatUrl, 1)) !== false, 'Caixa de conversas do prestador mostra o contato');
    $conversationPage = wf_request($provider['client'], $chatUrl);
    wf_check(strpos($conversationPage['body'], '&lt;b&gt;texto&lt;/b&gt;') !== false, 'Conversa mostra o texto da mensagem como texto');
    wf_check(wf_request($outsider['client'], $chatUrl)['status'] === 404 && wf_request($otherClient['client'], $pollUrl)['status'] === 404, 'Somente participantes podem ler a conversa');
    $reply = [
        'conversa_id' => $conversation, 'chave_envio' => bin2hex(random_bytes(16)), 'mensagem' => 'Sim, podemos atender.',
    ];
    wf_check(wf_request($provider['client'], $chatController, $reply)['status'] === 403, 'Envio de chat exige CSRF');
    wf_check(wf_request($outsider['client'], $chatController, $reply + ['csrf_token' => $outsider['csrf']])['status'] === 404, 'Só participantes podem responder');
    wf_check(wf_request($provider['client'], $chatController, $reply + ['csrf_token' => $provider['csrf']])['status'] === 303, 'Prestador responde pela conversa');
    $updates = json_decode(wf_request($client['client'], $pollUrl . '&depois=' . $providerMessages[0]['id'])['body'], true, 512, JSON_THROW_ON_ERROR)['messages'];
    wf_check(count($updates) === 1 && $updates[0]['text'] === $reply['mensagem'] && !$updates[0]['mine'], 'Atualização retorna apenas novas mensagens');

    $proposal = [
        'acao' => 'proposta', 'conversa_id' => $conversation, 'chave_envio' => bin2hex(random_bytes(16)),
        'area_servico_solicitada' => 'Jardinagem', 'descricao_servico' => 'Poda combinada na conversa.',
        'valor_orcamento' => '249,90', 'prazo_execucao' => 'Dois dias', 'condicoes_pagamento' => 'A combinar',
        'itens_inclusos' => 'Poda e limpeza', 'validade_proposta' => date('Y-m-d', strtotime('+7 days')),
    ];
    wf_check(wf_request($client['client'], $chatController, $proposal + ['csrf_token' => $client['csrf']])['status'] === 403, 'Contratante não pode enviar proposta como prestador');
    $postedProposal = $proposal + ['csrf_token' => $provider['csrf']];
    wf_check(wf_request($provider['client'], $chatController, $postedProposal)['status'] === 303, 'Prestador envia proposta pelo chat');
    wf_check(wf_request($provider['client'], $chatController, $postedProposal)['status'] === 303, 'Reenvio de proposta é aceito');
    $updates = json_decode(wf_request($client['client'], $pollUrl . '&depois=' . $updates[0]['id'])['body'], true, 512, JSON_THROW_ON_ERROR)['messages'];
    $chatOrder = $updates[0]['order'] ?? 0;
    wf_check(count($updates) === 1 && $chatOrder > 0 && wf_row($chatOrder)['valor_orcamento'] === '249.90', 'Chat recebe uma proposta com o valor esperado');
    wf_check(wf_action($client, $chatOrder, 'aceitar_proposta', ['confirmacao' => '1'])['status'] === 303, 'Contratante aceita proposta do chat pelo fluxo de orçamento');
    echo "\n$wfPassed verificações de orçamento, perfil, mapa e chat aprovadas.\n";
}finally{
    foreach($wfIds as $id)$pdo->prepare('DELETE FROM usuarios WHERE id=? AND email LIKE ?')->execute([$id,'workflow_%@example.invalid']);
    foreach($wfDocs as $doc)$pdo->prepare('DELETE FROM login_tentativas WHERE chave=?')->execute([hash('sha256','127.0.0.1:'.$doc)]);
}
