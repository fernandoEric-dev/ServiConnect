<?php
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo,['prestador']);
require_once __DIR__ . '/backend/app.php';
require_once __DIR__ . '/backend/profile.php';
$stmt=$pdo->prepare('SELECT u.email,u.tipo_pessoa,e.* FROM usuarios u JOIN empresas e ON e.usuario_id=u.id WHERE u.id=?');$stmt->execute([$_SESSION['user_id']]);$profile=$stmt->fetch();
if(!$profile)abort_request(404,'Perfil indisponível.');
$listing=order_list($pdo,(int)$_SESSION['user_id'],'prestador',['estado'=>'aberta']);
$countStmt=$pdo->prepare('SELECT status,COUNT(*) AS total FROM solicitacoes WHERE terceirizada_id=? GROUP BY status');$countStmt->execute([$_SESSION['user_id']]);$counts=array_column($countStmt->fetchAll(),'total','status');
$activeNav='';
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Área do prestador | ServiConnect</title><link rel="stylesheet" href="css/app.css?v=4"><link rel="stylesheet" href="vendor/leaflet/leaflet.css"><script src="vendor/leaflet/leaflet.js" defer></script><script src="js/maps.js?v=4" defer></script><script src="js/profile-editor.js?v=4" defer></script><script src="js/app.js?v=4" defer></script></head><body data-default-panel="pedidos">
<?php require __DIR__ . '/backend/views/app-header.php'; ?>
<main class="app-container"><section id="pedidos" data-panel><div class="page-intro"><p class="eyebrow">ÁREA DO PRESTADOR</p><h1>Olá, <?php echo order_escape($profile['nome']); ?></h1><p>Acompanhe seus pedidos e apresente seus serviços aos próximos clientes.</p></div>
<div class="metric-grid"><a href="orcamentos.php?estado=aberta"><strong><?php echo (int)($counts['aberta']??0); ?></strong>Novos pedidos</a><a href="orcamentos.php?estado=or%C3%A7ada"><strong><?php echo (int)($counts['orçada']??0)+(int)($counts['respondida']??0)+(int)($counts['em_negociacao']??0); ?></strong>Propostas enviadas</a><a href="orcamentos.php?estado=em_execucao"><strong><?php echo (int)($counts['em_execucao']??0); ?></strong>Em execução</a></div>
<div class="order-card-top" style="margin-bottom:18px"><h2>Solicitações aguardando proposta</h2><a href="orcamentos.php">Ver todo o histórico</a></div>
<?php if(!$listing['items']): ?><div class="surface empty-state"><h3>Nenhuma solicitação pendente</h3><p>Seus pedidos anteriores continuam no histórico. Mantenha o perfil completo para seus serviços aparecerem nas buscas.</p><div class="actions"><a class="button" href="#perfil">Completar meu perfil</a><a href="orcamentos.php" class="button-secondary">Ver meus orçamentos</a></div></div><?php else: ?><div class="order-list"><?php foreach(array_slice($listing['items'],0,5) as $item): ?><article class="surface order-card"><div class="order-card-top"><div><p class="eyebrow">PEDIDO #<?php echo (int)$item['id']; ?></p><h3><?php echo order_escape($item['area_servico_solicitada']); ?></h3><p>Cliente: <?php echo order_escape($item['nome_outro']); ?></p></div><span class="status-badge status-aberta">Aguardando proposta</span></div><p><?php echo order_escape(mb_substr($item['descricao_servico'],0,180)); ?></p><a class="button-secondary" href="orcamento.php?id=<?php echo (int)$item['id']; ?>">Ver pedido e responder</a></article><?php endforeach; ?></div><?php endif; ?>
</section><section id="perfil" data-panel><a href="#pedidos" class="app-back">← Voltar aos pedidos</a><?php require __DIR__ . '/backend/views/perfil-editor.php'; ?></section></main><footer class="app-footer">ServiConnect · Seu trabalho, novas conexões.</footer></body></html>
