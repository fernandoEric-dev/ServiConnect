<?php
require_once __DIR__ . '/backend/conexao.php'; require_role($pdo);
require_once __DIR__ . '/backend/app.php';
$id=filter_var($_GET['id']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id)abort_request(404,'Perfil indisponível.');
$stmt=$pdo->prepare("SELECT u.tipo_pessoa,e.* FROM usuarios u JOIN empresas e ON e.usuario_id=u.id WHERE u.id=? AND u.status='ativo'");$stmt->execute([$id]);$company=$stmt->fetch();
if(!$company)abort_request(404,'Perfil indisponível.');
$own=($_SESSION['user_role']??'')!=='admin'&&(int)$_SESSION['user_id']===(int)$company['usuario_id'];
$provider=in_array($company['tipo_empresa'],['mei','terceirizada'],true);
$privateAddress=!$own&&($company['tipo_pessoa']==='fisica'||$company['tipo_empresa']==='mei');
$contact=$own||$provider||$company['tipo_pessoa']==='juridica'||$_SESSION['user_role']==='admin';
if(!$contact&&$_SESSION['user_role']==='prestador'){$stmt=$pdo->prepare('SELECT 1 FROM solicitacoes WHERE contratante_id=? AND terceirizada_id=? LIMIT 1');$stmt->execute([$id,$_SESSION['user_id']]);$contact=(bool)$stmt->fetchColumn();}
$services=$provider?provider_services($pdo,(int)$id):[];$catalog=service_catalog();
$location=trim(($company['cidade']??'').(!empty($company['estado'])?' / '.$company['estado']:''));
$hasMap=$company['tipo_empresa']==='terceirizada'&&!empty($company['mapa_publico'])&&$company['latitude']!==null&&$company['longitude']!==null;
$activeNav='';$regions=selected_options($company['cidades_atendidas']??'');
$website=$company['website']??'';$safeWebsite=filter_var($website,FILTER_VALIDATE_URL)&&in_array(strtolower(parse_url($website,PHP_URL_SCHEME)??''),['http','https'],true);
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo order_escape($company['nome']); ?> | ServiConnect</title><link rel="stylesheet" href="css/app.css?v=4"><?php if($hasMap): ?><link rel="stylesheet" href="vendor/leaflet/leaflet.css"><script src="vendor/leaflet/leaflet.js" defer></script><script src="js/maps.js?v=4" defer></script><?php endif; ?></head><body>
<?php require __DIR__ . '/backend/views/app-header.php'; ?>
<main class="app-container app-narrow"><a href="busca.php" class="app-back">← Voltar à busca</a>
<article class="stack"><section class="surface"><div class="profile-overview"><img src="<?php echo order_escape(safe_photo($company['foto_path']??null)); ?>" alt="Foto de <?php echo order_escape($company['nome']); ?>"><div><span class="tag"><?php echo order_escape(account_type_label($company['tipo_empresa'],$company['tipo_pessoa'])); ?></span><h1 style="margin-top:10px"><?php echo order_escape($company['nome']); ?></h1><p class="muted"><?php echo order_escape($location ?: 'Cidade não informada'); ?></p></div></div>
<?php if($own): ?><a class="button-secondary" href="<?php echo $provider?'dashboard_tercerizada.php':'dashboard_contratante.php'; ?>#perfil">Editar meu perfil</a><?php elseif($provider&&$_SESSION['user_role']==='contratante'): ?><a class="button" href="solicitacao_orcamento.php?terceirizada_id=<?php echo (int)$id; ?>">Solicitar orçamento</a><?php endif; ?></section>
<section class="surface"><h2>Apresentação</h2><p class="text-block"><?php echo order_escape($company['descricao'] ?: 'Este perfil ainda não tem uma apresentação.'); ?></p></section>
<?php if($provider): ?>
<section class="surface"><h2>Serviços oferecidos</h2><?php if($services): ?><div class="chips"><?php foreach($services as $service): ?><span class="tag"><?php echo order_escape($catalog[$service]??$service); ?></span><?php endforeach; ?></div><?php else: ?><p><?php echo order_escape($company['area_atuacao']?:'O prestador ainda não detalhou seus serviços.'); ?></p><?php endif; ?>
<?php if($company['experiencia_anos']!==null): ?><p class="form-help" style="margin-top:16px">Experiência informada: <?php echo (int)$company['experiencia_anos']===0?'menos de 1 ano':(int)$company['experiencia_anos'].' anos'; ?>.</p><?php endif; ?></section>
<section class="surface"><h2>Regiões e atendimento</h2><dl class="detail-list"><div><dt>Atende em</dt><dd><?php echo order_escape($regions?implode(' · ',array_map(static fn($city)=>$city['cidade'].' / '.$city['uf'],$regions)):($company['regiao']?:'Consulte o prestador')); ?></dd></div>
<div><dt>Modalidades</dt><dd><?php echo order_escape(implode(' · ',array_intersect_key(attendance_options(),array_flip(selected_options($company['modalidades']??''))))?:'Consulte o prestador'); ?></dd></div>
<div><dt>Dias e horários</dt><dd><?php echo order_escape($company['horario']?:'Consulte o prestador'); ?></dd></div>
<?php if($company['tipo_empresa']==='terceirizada'): ?><div><dt>Equipe</dt><dd><?php echo (int)$company['num_funcionarios']>0?(int)$company['num_funcionarios'].' colaboradores':'Não informada'; ?></dd></div><?php endif; ?>
<div><dt>Formas de pagamento</dt><dd><?php echo order_escape(implode(' · ',array_intersect_key(payment_options(),array_flip(selected_options($company['pagamentos']??''))))?:'A combinar na proposta'); ?></dd></div>
<div><dt>Nota fiscal</dt><dd><?php echo !empty($company['emite_nota'])?'O prestador informa que emite nota fiscal.':'Consulte o prestador.'; ?></dd></div></dl><p class="form-help" style="margin-top:18px">Informações declaradas pelo prestador. Combine valores, disponibilidade e condições no orçamento.</p></section>
<?php endif; ?>
<?php if($contact): ?><section class="surface"><h2>Contato</h2><dl class="detail-list"><div><dt>Telefone / WhatsApp</dt><dd><?php echo order_escape($company['telefone']?:'Não informado'); ?></dd></div><?php if(!empty($company['responsavel'])): ?><div><dt>Responsável pelo atendimento</dt><dd><?php echo order_escape($company['responsavel']); ?></dd></div><?php endif; ?><?php if($safeWebsite): ?><div><dt>Site ou página profissional</dt><dd><a href="<?php echo order_escape($website); ?>" target="_blank" rel="noopener noreferrer nofollow"><?php echo order_escape($website); ?></a></dd></div><?php endif; ?></dl></section><?php endif; ?>
<section class="surface"><h2><?php echo $privateAddress?'Cidade e estado':'Localização'; ?></h2><p class="text-block"><?php
if($privateAddress)echo order_escape($location?:'Não informado');
else{$parts=[];if(!empty($company['logradouro']))$parts[]=trim($company['logradouro'].(!empty($company['numero'])?', '.$company['numero']:'').(!empty($company['complemento'])?' — '.$company['complemento']:''));if(!empty($company['bairro']))$parts[]=$company['bairro'];if($location)$parts[]=$location;if(!empty($company['cep']))$parts[]='CEP: '.$company['cep'];echo order_escape(implode("\n",$parts)?:'Não informado');}
?></p>
<?php if($company['tipo_empresa']==='mei'&&!$own): ?><p class="form-help">Este prestador informa sua região de atendimento. O endereço residencial permanece privado.</p><?php endif; ?>
<?php if($hasMap): ?><div data-provider-map<?php echo app_tile_attributes(); ?> class="profile-map" tabindex="-1" aria-label="Localização da empresa no mapa"></div><script type="application/json" id="mapProviders"><?php echo json_encode([['usuario_id'=>(int)$id,'nome_empresa'=>$company['nome'],'latitude'=>$company['latitude'],'longitude'=>$company['longitude']]],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script><p class="form-help">Ponto informado pela empresa.</p><a href="https://www.openstreetmap.org/?mlat=<?php echo rawurlencode($company['latitude']); ?>&amp;mlon=<?php echo rawurlencode($company['longitude']); ?>#map=17/<?php echo rawurlencode($company['latitude']); ?>/<?php echo rawurlencode($company['longitude']); ?>" target="_blank" rel="noopener" class="button-secondary">Abrir no OpenStreetMap</a><?php endif; ?>
</section></article></main></body></html>
