<?php
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo, ['contratante']);
require_once __DIR__ . '/backend/app.php';
$id = filter_var($_GET['terceirizada_id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$id) abort_request(404, 'Prestador indisponível.');
$stmt = $pdo->prepare("SELECT e.* FROM empresas e JOIN usuarios u ON u.id=e.usuario_id WHERE e.usuario_id=? AND e.tipo_empresa IN ('mei','terceirizada') AND u.status='ativo'");
$stmt->execute([$id]); $provider = $stmt->fetch();
if (!$provider) abort_request(404, 'Prestador indisponível.');
$stmt = $pdo->prepare('SELECT * FROM empresas WHERE usuario_id=?'); $stmt->execute([$_SESSION['user_id']]); $clientProfile = $stmt->fetch();
$address = trim(implode(', ', array_filter([$clientProfile['logradouro'] ?? '', $clientProfile['numero'] ?? '', $clientProfile['bairro'] ?? '', $clientProfile['cidade'] ?? '', $clientProfile['estado'] ?? ''])));
$old = app_old(); $nonce = isset($old['chave_criacao']) && is_string($old['chave_criacao']) ? $old['chave_criacao'] : bin2hex(random_bytes(32));
$services = provider_services($pdo, (int)$id); $catalog = service_catalog(); $activeNav = 'orcamentos';
$defaultService = $old['servico_id'] ?? ($services[0] ?? '');
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Solicitar orçamento | ServiConnect</title><link rel="stylesheet" href="css/app.css?v=4"></head><body>
<?php require __DIR__ . '/backend/views/app-header.php'; ?>
<main class="app-container app-narrow"><a href="perfil_empresa.php?id=<?php echo (int)$id; ?>" class="app-back">← Voltar ao perfil</a><div class="page-intro"><p class="eyebrow">NOVO PEDIDO</p><h1>Solicitar orçamento</h1><p>Para <strong><?php echo order_escape($provider['nome']); ?></strong>. Conte o que precisa para receber uma proposta com preço e condições.</p></div>
<?php app_feedback(); ?>
<form class="surface" action="backend/controllers/SolicitacaoController.php" method="post" id="formSolicitacao">
<?php echo csrf_field(); ?><input type="hidden" name="ui" value="1"><input type="hidden" name="terceirizada_id" value="<?php echo (int)$id; ?>"><input type="hidden" name="chave_criacao" value="<?php echo order_escape($nonce); ?>">
<fieldset class="form-section"><legend>Qual serviço você precisa?</legend><div class="form-grid">
<div class="field full-width"><label for="requestedService">Serviço</label><select id="requestedService" name="servico_id"><option value="">Outro serviço / descrever abaixo</option>
<?php foreach (service_groups() as $group=>$items): $allowed = $services ? array_intersect_key($items, array_flip($services)) : $items; if (!$allowed) continue; ?><optgroup label="<?php echo order_escape($group); ?>"><?php foreach ($allowed as $key=>$label): ?><option value="<?php echo $key; ?>"<?php if ($defaultService === $key) echo ' selected'; ?>><?php echo order_escape($label); ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select><small>Escolha um serviço oferecido pelo prestador ou descreva uma necessidade diferente.</small></div>
<div class="field full-width" id="customServiceField"><label for="customService">Se escolheu outro serviço, qual?</label><input id="customService" name="area_servico_solicitada" maxlength="100" value="<?php echo app_value($old,'area_servico_solicitada'); ?>" placeholder="Descreva o tipo de serviço"></div>
<div class="field full-width"><label for="descricaoServico">O que precisa ser feito?</label><textarea id="descricaoServico" name="descricao_servico" rows="5" required maxlength="10000" placeholder="Ex.: cortar a grama, podar seis plantas e retirar os resíduos."><?php echo app_value($old,'descricao_servico'); ?></textarea></div>
<div class="field"><label for="frequencia">Frequência</label><select id="frequencia" name="frequencia"><?php foreach (['a_combinar'=>'A combinar','unico'=>'Uma única vez','diario'=>'Diário','semanal'=>'Semanal','quinzenal'=>'Quinzenal','mensal'=>'Mensal'] as $key=>$label): ?><option value="<?php echo $key; ?>"<?php if (($old['frequencia'] ?? 'a_combinar') === $key) echo ' selected'; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="dataPreferida">Data desejada (opcional)</label><input type="date" id="dataPreferida" name="data_preferida" min="<?php echo date('Y-m-d'); ?>" value="<?php echo app_value($old,'data_preferida'); ?>"><small>A data final será combinada com o prestador.</small></div>
<?php if ($provider['tipo_empresa']==='mei'): ?><input type="hidden" name="numero_funcionarios" value="1"><?php else: ?><div class="field"><label for="numeroFuncionarios">Quantidade de profissionais</label><input type="number" id="numeroFuncionarios" name="numero_funcionarios" min="1" max="100000" value="<?php echo app_value($old,'numero_funcionarios','1'); ?>"><small>Se não souber, mantenha 1 e descreva sua necessidade.</small></div><?php endif; ?>
</div></fieldset>
<fieldset class="form-section"><legend>Local do atendimento</legend><div class="field"><label for="localServico">Endereço ou indicação de atendimento remoto</label><textarea id="localServico" name="localizacao_servico" rows="3" maxlength="255" required><?php echo app_value($old,'localizacao_servico',mb_substr($address,0,255)); ?></textarea><small>Este local será compartilhado apenas com o prestador destinatário do pedido.</small></div></fieldset>
<p class="form-help">Enviar um pedido não realiza pagamento. Aguarde a proposta e confira as condições antes de aceitar.</p><div class="actions" style="margin-top:20px"><button class="button" type="submit">Enviar solicitação</button><a href="busca.php" class="button-secondary">Voltar à busca</a></div>
</form></main>
<script>document.addEventListener('DOMContentLoaded',()=>{const service=document.getElementById('requestedService'),field=document.getElementById('customServiceField'),input=document.getElementById('customService');function update(){field.hidden=service.value!=='';input.required=service.value==='';}service.addEventListener('change',update);update();});</script>
</body></html>
