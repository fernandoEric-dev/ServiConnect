<?php
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo, ['contratante','prestador']);
require_once __DIR__ . '/backend/app.php';
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
try { $order = order_fetch($pdo, $id, (int)$_SESSION['user_id']); } catch (OrderProblem $e) { abort_request($e->status, $e->getMessage()); }
$isClient = (int)$order['contratante_id'] === (int)$_SESSION['user_id']; $activeNav = 'orcamentos';
$old = app_old(); $state = $order['status']; $pendingOffer = in_array($state, ['orçada','respondida','em_negociacao'], true);
$eventCount = $pdo->prepare('SELECT COUNT(*) FROM solicitacoes_eventos WHERE solicitacao_id=?'); $eventCount->execute([$id]);
$eventPages = max(1,(int)ceil((int)$eventCount->fetchColumn()/30));
$eventPage = isset($_GET['historico_pagina']) && is_scalar($_GET['historico_pagina']) ? (int)$_GET['historico_pagina'] : $eventPages; $eventPage = min($eventPages,max(1,$eventPage));
$events = $pdo->prepare('SELECT * FROM solicitacoes_eventos WHERE solicitacao_id=? ORDER BY id LIMIT 30 OFFSET ' . (($eventPage-1)*30)); $events->execute([$id]); $events = $events->fetchAll();
$eventLabels = ['pedido_criado'=>'Solicitação enviada','historico_iniciado'=>'Histórico ativado','enviar_proposta'=>'Proposta enviada','revisar_proposta'=>'Proposta revisada','recusar_pedido'=>'Pedido recusado','aceitar_proposta'=>'Proposta aceita','recusar_proposta'=>'Proposta recusada','iniciar_servico'=>'Serviço iniciado','finalizar_servico'=>'Finalização informada','confirmar_conclusao'=>'Conclusão confirmada','solicitar_ajuste'=>'Ajuste solicitado','cancelar'=>'Pedido cancelado','mensagem'=>'Mensagem'];
$frequencies = ['unico'=>'Uma única vez','diario'=>'Diário','semanal'=>'Semanal','quinzenal'=>'Quinzenal','mensal'=>'Mensal','a_combinar'=>'A combinar'];
$otherId = $isClient ? $order['terceirizada_id'] : $order['contratante_id']; $otherName = $isClient ? $order['nome_prestador'] : $order['nome_contratante'];
function order_form_fields(array $order): void { echo csrf_field() . '<input type="hidden" name="ui" value="1"><input type="hidden" name="solicitacao_id" value="' . (int)$order['id'] . '"><input type="hidden" name="versao" value="' . (int)$order['versao'] . '">'; }
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Orçamento #<?php echo $id; ?> | ServiConnect</title><link rel="stylesheet" href="css/app.css?v=4"></head><body>
<?php require __DIR__ . '/backend/views/app-header.php'; ?>
<main class="app-container"><a class="app-back" href="orcamentos.php">← Todos os meus orçamentos</a>
<div class="page-intro"><p class="eyebrow">PEDIDO #<?php echo $id; ?></p><h1><?php echo order_escape($order['area_servico_solicitada'] ?: 'Detalhes do serviço'); ?></h1><p><?php echo $isClient ? 'Prestador: ' : 'Contratante: '; ?><?php if ($otherId): ?><a href="perfil_empresa.php?id=<?php echo (int)$otherId; ?>"><?php echo order_escape($otherName ?: 'Perfil indisponível'); ?></a><?php else: ?>Perfil indisponível<?php endif; ?></p><span class="status-badge status-<?php echo order_escape($state ?? ''); ?>"><?php echo order_escape(order_status($state)); ?></span></div>
<?php app_feedback(); ?>
<div class="actions" style="margin-bottom:24px"><a href="#proposta" class="button-secondary">Ver proposta</a><a href="#acoes" class="button-secondary">Próximos passos</a><a href="#historico" class="button-secondary">Histórico e mensagens</a></div>
<div class="columns"><div class="stack">
<section class="surface"><h2>O que foi solicitado</h2><dl class="detail-list">
<div><dt>Descrição</dt><dd><?php echo order_escape($order['descricao_servico']); ?></dd></div>
<div><dt>Local do atendimento · visível aos participantes</dt><dd><?php echo order_escape($order['localizacao_servico'] ?: 'A combinar'); ?></dd></div>
<div><dt>Frequência</dt><dd><?php echo $frequencies[$order['frequencia'] ?? ''] ?? 'Consulte a descrição do pedido'; ?></dd></div>
<div><dt>Data desejada</dt><dd><?php echo app_date($order['data_preferida']); ?></dd></div>
<?php if ($order['tipo_empresa'] !== 'mei'): ?><div><dt>Quantidade de profissionais solicitada</dt><dd><?php echo (int)$order['numero_funcionarios'] ?: 'A combinar'; ?></dd></div><?php endif; ?>
<div><dt>Pedido criado em</dt><dd><?php echo app_date($order['data_solicitacao'],true); ?></dd></div>
</dl></section>
<section class="surface" id="proposta"><h2>Proposta do prestador</h2>
<?php if ($order['valor_orcamento'] !== null): ?><p class="quote-price"><?php echo order_brl($order['valor_orcamento']); ?></p><dl class="detail-list">
<?php foreach (['mensagem_proposta'=>'Apresentação da proposta','prazo_execucao'=>'Prazo de execução','condicoes_pagamento'=>'Condições de pagamento','itens_inclusos'=>'O que está incluído','itens_exclusos'=>'O que não está incluído'] as $field=>$label): ?><div><dt><?php echo $label; ?></dt><dd><?php echo order_escape($order[$field] ?: 'A combinar entre as partes'); ?></dd></div><?php endforeach; ?>
<div><dt>Validade da proposta</dt><dd><?php echo $order['validade_proposta'] ? app_date($order['validade_proposta']) : 'Não informada pelo prestador'; ?></dd></div></dl>
<?php if ($pendingOffer && order_expired($order)): ?><div class="notice notice-info" style="margin-top:18px">A validade terminou. Peça uma revisão ao prestador antes de aceitar.</div><?php endif; ?>
<?php else: ?><p class="muted">O prestador ainda não enviou um valor para este pedido.</p><?php endif; ?>
<p class="form-help" style="margin-top:18px">A ServiConnect registra propostas e acompanhamento. O pagamento e a execução são combinados diretamente entre os participantes.</p></section>
<section class="surface" id="historico"><h2>Histórico e mensagens</h2>
<?php if (!$events): ?><p class="muted">Este pedido ainda não tem movimentos registrados.</p><?php else: ?><ol class="timeline">
<?php foreach ($events as $event): ?><li><p class="timeline-title"><?php echo order_escape($eventLabels[$event['tipo']] ?? 'Atualização do pedido'); ?></p><time datetime="<?php echo order_escape(str_replace(' ','T',$event['criado_em'])); ?>"><?php echo app_date($event['criado_em'],true); ?></time><p class="muted" style="font-size:.8rem;margin:4px 0"><?php echo order_escape($event['autor_nome']); ?> · <?php echo order_escape($event['papel']); ?></p>
<?php if ($event['mensagem']): ?><p class="timeline-message"><?php echo order_escape($event['mensagem']); ?></p><?php endif; ?>
<?php $snapshot = selected_options($event['dados_json']); if ($snapshot): ?><details><summary>Ver condições registradas neste movimento</summary><dl class="detail-list" style="margin-top:12px"><?php foreach (['valor_orcamento'=>'Valor','prazo_execucao'=>'Prazo','condicoes_pagamento'=>'Pagamento','itens_inclusos'=>'Incluído','itens_exclusos'=>'Não incluído','mensagem_proposta'=>'Apresentação','validade_proposta'=>'Validade'] as $field=>$label): ?><?php if (isset($snapshot[$field]) && $snapshot[$field] !== ''): ?><div><dt><?php echo $label; ?></dt><dd><?php echo $field === 'valor_orcamento' ? order_brl($snapshot[$field]) : order_escape($snapshot[$field]); ?></dd></div><?php endif; ?><?php endforeach; ?></dl></details><?php endif; ?></li><?php endforeach; ?>
</ol><?php endif; ?>
<?php if ($eventPages>1): ?><nav class="pagination" aria-label="Páginas do histórico"><?php if ($eventPage>1): ?><a class="button-secondary" href="orcamento.php?id=<?php echo $id; ?>&historico_pagina=<?php echo $eventPage-1; ?>#historico">Movimentos anteriores</a><?php endif; ?><?php if ($eventPage<$eventPages): ?><a class="button-secondary" href="orcamento.php?id=<?php echo $id; ?>&historico_pagina=<?php echo $eventPage+1; ?>#historico">Mais recentes</a><?php endif; ?></nav><?php endif; ?>
<form action="backend/controllers/OrcamentoAcaoController.php" method="post" style="margin-top:24px"><?php order_form_fields($order); ?><input type="hidden" name="acao" value="mensagem"><div class="field"><label for="mensagemPedido">Mensagem sobre este pedido</label><textarea id="mensagemPedido" name="mensagem" rows="3" maxlength="2000" required placeholder="Tire uma dúvida ou registre uma combinação."><?php echo app_value($old,'mensagem'); ?></textarea><small>Apenas você e o outro participante têm acesso a este histórico.</small></div><button class="button-secondary" type="submit" style="margin-top:12px">Enviar mensagem</button></form>
</section></div>
<aside id="acoes" class="surface order-side quote-actions"><h2>Próximos passos</h2><div class="actions-stacked">
<?php if (!$isClient && ($state === 'aberta' || $pendingOffer)): ?>
<form action="backend/controllers/RespostaOrcamentoController.php" method="post" class="stack" style="gap:16px"><?php order_form_fields($order); ?><input type="hidden" name="action" value="<?php echo $state === 'aberta' ? 'orcamento' : 'revisao'; ?>">
<h3><?php echo $state === 'aberta' ? 'Preparar proposta' : 'Revisar proposta'; ?></h3>
<?php require __DIR__ . '/backend/views/proposta-campos.php'; ?>
<button class="button" type="submit"><?php echo $state === 'aberta' ? 'Enviar proposta' : 'Enviar revisão da proposta'; ?></button></form>
<?php endif; ?>
<?php if ($isClient && $pendingOffer && !order_expired($order) && $order['valor_orcamento'] !== null): ?><details><summary>Revisar e aceitar proposta</summary><form action="backend/controllers/OrcamentoAcaoController.php" method="post"><?php order_form_fields($order); ?><input type="hidden" name="acao" value="aceitar_proposta"><p>Valor total: <strong><?php echo order_brl($order['valor_orcamento']); ?></strong>. Confira os itens incluídos, o prazo e as condições de pagamento.</p><label class="check-label"><input type="checkbox" name="confirmacao" value="1" required><span>Li e concordo com o valor e as condições desta proposta.</span></label><button class="button" type="submit" style="margin-top:16px">Aceitar proposta</button></form></details><?php endif; ?>
<?php
$actions=[];
if (!$isClient && $state==='aberta') $actions[]=['recusar_pedido','Recusar este pedido',false,'button-danger'];
if ($isClient && $pendingOffer) $actions[]=['recusar_proposta','Recusar proposta',false,'button-danger'];
if (!$isClient && $state==='aceita') $actions[]=['iniciar_servico','Informar início do serviço',false,'button'];
if (!$isClient && $state==='em_execucao') $actions[]=['finalizar_servico','Informar finalização do serviço',false,'button'];
if ($isClient && $state==='aguardando_confirmacao') { $actions[]=['confirmar_conclusao','Confirmar conclusão',false,'button']; $actions[]=['solicitar_ajuste','Solicitar um ajuste',true,'button-secondary']; }
$canCancel=$isClient ? in_array($state,['aberta','orçada','respondida','em_negociacao','aceita'],true) : in_array($state,['aceita','em_execucao'],true);
if ($canCancel) $actions[]=['cancelar','Cancelar pedido',true,'button-danger'];
foreach ($actions as [$action,$label,$reason,$class]): ?>
<details><summary><?php echo $label; ?></summary><form action="backend/controllers/OrcamentoAcaoController.php" method="post"><?php order_form_fields($order); ?><input type="hidden" name="acao" value="<?php echo $action; ?>"><div class="field"><label for="motivo_<?php echo $action; ?>"><?php echo $reason ? 'Informe o motivo' : 'Observação (opcional)'; ?></label><textarea id="motivo_<?php echo $action; ?>" name="mensagem" rows="3" maxlength="2000"<?php if ($reason) echo ' required'; ?>></textarea></div><button type="submit" class="<?php echo $class; ?>" style="margin-top:12px"><?php echo $label; ?></button></form></details>
<?php endforeach; ?>
<?php if (!$actions && !(!$isClient && ($state==='aberta' || $pendingOffer)) && !($isClient && $pendingOffer)): ?><p class="muted"><?php echo in_array($state,['concluida','fechada','cancelada','recusada','recusada_cliente'],true) ? 'Pedido encerrado. As propostas e os movimentos continuam disponíveis no histórico.' : 'Aguarde a próxima atualização do outro participante. Você pode enviar uma mensagem abaixo.'; ?></p><?php endif; ?>
<a class="button-secondary" href="#historico">Ver histórico e enviar mensagem</a>
</div></aside></div></main></body></html>
