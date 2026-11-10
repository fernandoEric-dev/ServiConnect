<div class="field">
    <label for="valorOrcamento">Valor total em reais</label>
    <input id="valorOrcamento" name="valor_orcamento" inputmode="decimal" maxlength="16" required placeholder="Ex.: 250,00" value="<?php echo app_value($old, 'valor_orcamento', $order['valor_orcamento'] ?? ''); ?>">
</div>
<div class="field">
    <label for="prazoExecucao">Prazo de execução</label>
    <input id="prazoExecucao" name="prazo_execucao" maxlength="150" required placeholder="Ex.: 2 dias após combinar a data" value="<?php echo app_value($old, 'prazo_execucao', $order['prazo_execucao'] ?? ''); ?>">
</div>
<div class="field">
    <label for="validadeProposta">Proposta válida até</label>
    <input type="date" id="validadeProposta" name="validade_proposta" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo app_value($old, 'validade_proposta', !empty($order['validade_proposta']) && !order_expired($order) ? $order['validade_proposta'] : date('Y-m-d', strtotime('+7 days'))); ?>">
</div>
<?php
$proposalNotes = [
    'condicoes_pagamento' => ['Pagamento', 'Ex.: Pix, após a conclusão.'],
    'itens_inclusos' => ['O que está incluído', 'Ex.: mão de obra, ferramentas e retirada dos resíduos.'],
    'itens_exclusos' => ['O que não está incluído (opcional)', 'Ex.: compra de materiais.'],
    'mensagem_proposta' => ['Observações da proposta (opcional)', 'Detalhes importantes para o cliente.'],
];
foreach ($proposalNotes as $field => [$label, $placeholder]):
?>
    <div class="field full-width">
        <label for="<?php echo $field; ?>"><?php echo $label; ?></label>
        <textarea id="<?php echo $field; ?>" name="<?php echo $field; ?>" rows="3" maxlength="<?php echo $field === 'condicoes_pagamento' ? 1000 : 3000; ?>" placeholder="<?php echo order_escape($placeholder); ?>"<?php if (in_array($field, ['condicoes_pagamento', 'itens_inclusos'], true)) echo ' required'; ?>><?php echo app_value($old, $field, $order[$field] ?? ''); ?></textarea>
    </div>
<?php endforeach; ?>
