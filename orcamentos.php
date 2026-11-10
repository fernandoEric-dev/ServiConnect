<?php
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo, ['contratante','prestador']);
require_once __DIR__ . '/backend/app.php';
$role = $_SESSION['user_role']; $activeNav = 'orcamentos';
$listing = order_list($pdo, (int)$_SESSION['user_id'], $role, $_GET);
$stateLabels = [''=>'Todos os pedidos','aberta'=>'Aguardando proposta','orçada'=>'Propostas recebidas','aceita'=>'Propostas aceitas','em_execucao'=>'Em execução','aguardando_confirmacao'=>'Aguardando confirmação','concluida'=>'Concluídos / encerrados','recusada'=>'Recusados','cancelada'=>'Cancelados'];
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Meus orçamentos | ServiConnect</title><link rel="stylesheet" href="css/app.css?v=4"></head><body>
<?php require __DIR__ . '/backend/views/app-header.php'; ?>
<main class="app-container"><div class="page-intro"><p class="eyebrow"><?php echo $role === 'contratante' ? 'SERVIÇOS CONTRATADOS' : 'PEDIDOS DOS SEUS CLIENTES'; ?></p><h1>Meus orçamentos</h1><p>Da primeira proposta à conclusão: acompanhe as condições, as mensagens e o histórico de cada pedido.</p></div>
<?php app_feedback(); ?>
<form action="orcamentos.php" method="get" class="surface order-filter" role="search" aria-label="Filtrar orçamentos">
<div class="field"><label for="orderSearch">Serviço, nome ou número do pedido</label><input id="orderSearch" name="q" maxlength="100" value="<?php echo order_escape($listing['q']); ?>" placeholder="Ex.: jardinagem ou #123"></div>
<div class="field"><label for="orderState">Situação</label><select id="orderState" name="estado"><?php foreach ($stateLabels as $value=>$label): ?><option value="<?php echo order_escape($value); ?>"<?php if ($value === $listing['filter']) echo ' selected'; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div>
<div class="actions"><button type="submit" class="button">Filtrar</button><a href="orcamentos.php" class="button-secondary">Limpar</a></div>
</form>
<p class="muted" role="status"><?php echo $listing['total']; ?> <?php echo $listing['total'] === 1 ? 'pedido encontrado' : 'pedidos encontrados'; ?> · Página <?php echo $listing['page']; ?> de <?php echo $listing['pages']; ?></p>
<?php if (!$listing['items']): ?><div class="surface empty-state"><h2>Nenhum pedido nesta lista</h2><p><?php echo $role === 'contratante' ? 'Encontre um prestador e envie uma solicitação ou remova os filtros para ver seus pedidos anteriores.' : 'Remova os filtros ou complete seu perfil para apresentar seus serviços aos clientes.'; ?></p><a class="button" href="<?php echo $role === 'contratante' ? 'busca.php' : 'dashboard_tercerizada.php#perfil'; ?>"><?php echo $role === 'contratante' ? 'Buscar prestadores' : 'Completar meu perfil'; ?></a></div>
<?php else: ?><div class="order-list">
<?php foreach ($listing['items'] as $order): ?><article class="surface order-card">
<div class="order-card-top"><div><p class="eyebrow">PEDIDO #<?php echo (int)$order['id']; ?></p><h2><?php echo order_escape($order['area_servico_solicitada'] ?: 'Serviço a combinar'); ?></h2><p><?php echo $role === 'contratante' ? 'Prestador: ' : 'Contratante: '; ?><?php echo order_escape($order['nome_outro'] ?: 'Perfil indisponível'); ?></p></div><span class="status-badge status-<?php echo order_escape($order['status'] ?? ''); ?>"><?php echo order_escape(order_status($order['status'])); ?></span></div>
<div class="order-meta"><span>Solicitado em <strong><?php echo app_date($order['data_solicitacao']); ?></strong></span><span>Proposta: <strong><?php echo order_brl($order['valor_orcamento']); ?></strong></span><?php if (order_expired($order) && in_array($order['status'], ['orçada','respondida'], true)): ?><span class="tag">Validade da proposta encerrada</span><?php endif; ?></div>
<div class="actions"><a class="button-secondary" href="orcamento.php?id=<?php echo (int)$order['id']; ?>">Ver detalhes e histórico</a></div></article><?php endforeach; ?></div><?php endif; ?>
<?php if ($listing['pages'] > 1): ?><nav class="pagination" aria-label="Paginação dos orçamentos"><?php if ($listing['page']>1): ?><a class="button-secondary" rel="prev" href="orcamentos.php?<?php echo order_escape(http_build_query(['q'=>$listing['q'],'estado'=>$listing['filter'],'pagina'=>$listing['page']-1])); ?>">Anterior</a><?php endif; ?><?php if ($listing['page']<$listing['pages']): ?><a class="button-secondary" rel="next" href="orcamentos.php?<?php echo order_escape(http_build_query(['q'=>$listing['q'],'estado'=>$listing['filter'],'pagina'=>$listing['page']+1])); ?>">Próxima</a><?php endif; ?></nav><?php endif; ?>
</main><footer class="app-footer">ServiConnect · Propostas e histórico em um só lugar.</footer></body></html>
