<?php
require_once __DIR__ . '/backend/conexao.php';
require_once __DIR__ . '/backend/chat.php';

$viewer = chat_require_user($pdo);
$activeNav = 'conversas';
$id = chat_id($_GET['id'] ?? null);
$provider = chat_id($_GET['prestador'] ?? null);
$before = chat_id($_GET['antes'] ?? null);
$conversation = null;
$feedback = null;
$old = [];

try {
    if (isset($_GET['id']) || isset($_GET['prestador'])) {
        $conversation = chat_context($pdo, $viewer, $id, $provider);
        $target = $id ? 'id=' . $id : 'prestador=' . $provider;
        if (($_SESSION['chat_feedback']['target'] ?? '') === $target) {
            $feedback = $_SESSION['chat_feedback'];
            $old = $feedback['data'];
            unset($_SESSION['chat_feedback']);
        }
        if (!$id && $conversation['id'] && !$feedback) {
            header('Location: /conversas.php?id=' . $conversation['id'], true, 303);
            exit;
        }
        $id = (int)$conversation['id'];
        $result = $id ? chat_messages($pdo, $id, $viewer['id'], $before) : ['messages' => [], 'more' => false];
        $messages = $result['messages'];
        $last = $messages ? $messages[count($messages) - 1]['id'] : '0';
    } else {
        $threads = chat_inbox($pdo, $viewer, $before);
        $hasMore = count($threads) > 20;
        $threads = array_slice($threads, 0, 20);
    }
} catch (Throwable $e) {
    $problem = chat_problem($e);
    abort_request($problem->status, $problem->getMessage());
}

function chat_form_fields(int $id, int $provider, string $action, array $old): void {
    $key = $old['chave_envio'] ?? '';
    if (($old['acao'] ?? '') !== $action || !is_string($key) || !preg_match('/^[a-f0-9]{32}$/D', $key)) {
        $key = bin2hex(random_bytes(16));
    }
    echo csrf_field();
    ?>
    <input type="hidden" name="conversa_id" value="<?php echo $id; ?>">
    <input type="hidden" name="prestador_id" value="<?php echo $provider; ?>">
    <input type="hidden" name="acao" value="<?php echo $action; ?>">
    <input type="hidden" name="chave_envio" value="<?php echo order_escape($key); ?>">
    <?php
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conversas | ServiConnect</title>
    <link rel="stylesheet" href="css/app.css?v=4">
    <link rel="stylesheet" href="css/chat.css?v=1">
    <script src="js/chat.js?v=1" defer></script>
</head>
<body>
<?php require __DIR__ . '/backend/views/app-header.php'; ?>
<main class="app-container app-narrow">
<?php if (!$conversation): ?>
    <div class="page-intro"><h1>Conversas</h1></div>
    <?php if (!$threads): ?>
        <section class="surface empty-state">
            <h2><?php echo $before ? 'Nenhuma conversa nesta página' : 'Você ainda não tem conversas'; ?></h2>
            <?php if ($before): ?>
                <a class="button-secondary" href="conversas.php">Voltar às mais recentes</a>
            <?php elseif ($viewer['role'] === 'contratante'): ?>
                <p>Abra o perfil de um prestador para tirar dúvidas sobre o serviço.</p>
                <a class="button" href="busca.php">Buscar prestadores</a>
            <?php else: ?>
                <p>As mensagens de clientes interessados nos seus serviços aparecem aqui.</p>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <ul class="chat-inbox surface" aria-label="Conversas recentes">
            <?php foreach ($threads as $thread): ?>
                <li>
                    <a href="conversas.php?id=<?php echo (int)$thread['id']; ?>" class="chat-thread">
                        <img class="chat-avatar" src="<?php echo order_escape(safe_photo($thread['foto_path'])); ?>" width="48" height="48" alt="" loading="lazy">
                        <div class="chat-thread-copy">
                            <strong><?php echo order_escape($thread['nome']); ?></strong>
                            <span><?php echo (int)$thread['autor_id'] === $viewer['id'] ? 'Você: ' : ''; ?><?php echo order_escape(mb_strimwidth($thread['mensagem'], 0, 120, '…')); ?></span>
                        </div>
                        <time><?php echo app_date($thread['criado_em'], true); ?></time>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($hasMore || $before): ?>
            <nav class="pagination" aria-label="Páginas de conversas">
                <?php if ($before): ?><a class="button-secondary" href="conversas.php">Mais recentes</a><?php endif; ?>
                <?php if ($hasMore): ?><a class="button-secondary" rel="next" href="conversas.php?antes=<?php echo end($threads)['mensagem_id']; ?>">Conversas anteriores</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <a class="app-back" href="conversas.php">← Todas as conversas</a>
    <section class="surface chat" aria-label="Conversa" data-chat-id="<?php echo $id; ?>" data-cursor="<?php echo $last; ?>" data-older="<?php echo $before ? '1' : '0'; ?>">
        <header class="chat-header">
            <img class="chat-avatar" src="<?php echo order_escape(safe_photo($conversation['peer_photo'])); ?>" width="48" height="48" alt="">
            <div>
                <h1 id="chatPeer"><?php echo order_escape($conversation['peer_name']); ?></h1>
                <?php if ($conversation['peer_available']): ?>
                    <a href="perfil_empresa.php?id=<?php echo $conversation['peer_id']; ?>">Ver perfil</a>
                <?php else: ?>
                    <span class="muted">Conta indisponível</span>
                <?php endif; ?>
            </div>
        </header>
        <?php if ($result['more'] || $before): ?>
            <nav class="chat-history" aria-label="Histórico da conversa">
                <?php if ($result['more']): ?><a href="conversas.php?id=<?php echo $id; ?>&amp;antes=<?php echo $messages[0]['id']; ?>">Mensagens anteriores</a><?php endif; ?>
                <?php if ($before): ?><a href="conversas.php?id=<?php echo $id; ?>">Voltar às mais recentes</a><?php endif; ?>
            </nav>
        <?php endif; ?>
        <div class="chat-scroll" id="mensagens" tabindex="0" aria-label="Mensagens da conversa">
            <p class="chat-empty muted"<?php if ($messages) echo ' hidden'; ?>><?php echo $before ? 'Nenhuma mensagem nesta página.' : 'Envie uma mensagem para começar.'; ?></p>
            <ol class="chat-messages" role="log" aria-label="Mensagens" aria-live="polite" aria-relevant="additions">
                <?php foreach ($messages as $message): ?>
                    <li class="chat-message<?php if ($message['mine']) echo ' chat-mine'; ?>">
                        <p><?php echo order_escape($message['text']); ?></p>
                        <?php if ($message['order']): ?><a href="orcamento.php?id=<?php echo $message['order']; ?>">Ver proposta</a><?php endif; ?>
                        <div class="chat-meta">
                            <span><?php echo $message['mine'] ? 'Você' : order_escape($conversation['peer_name']); ?></span>
                            <time datetime="<?php echo $message['datetime']; ?>"><?php echo $message['time']; ?></time>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <p class="chat-feedback<?php if ($feedback) echo ' chat-error'; ?>" role="status"<?php if (!$feedback) echo ' hidden'; ?>><?php echo order_escape($feedback['message'] ?? ''); ?></p>
        <?php if ($conversation['peer_available'] && !$before): ?>
            <form action="backend/controllers/ChatController.php" method="post" class="chat-composer" data-chat-send>
                <?php chat_form_fields($id, (int)$conversation['prestador_id'], 'mensagem', $old); ?>
                <div class="field">
                    <label for="chatMessage">Mensagem</label>
                    <textarea id="chatMessage" name="mensagem" rows="2" maxlength="2000" required placeholder="Escreva sua mensagem"><?php echo app_value($old, 'mensagem'); ?></textarea>
                </div>
                <button class="button" type="submit">Enviar</button>
            </form>
            <?php if ($viewer['role'] === 'prestador' && $id): ?>
                <details class="chat-proposal"<?php if (($old['acao'] ?? '') === 'proposta') echo ' open'; ?>>
                    <summary>Enviar proposta</summary>
                    <form action="backend/controllers/ChatController.php" method="post" class="chat-proposal-form" data-chat-send>
                        <?php chat_form_fields($id, 0, 'proposta', $old); ?>
                        <div class="form-grid">
                            <div class="field"><label for="chatService">Nome do serviço</label><input id="chatService" name="area_servico_solicitada" maxlength="100" required value="<?php echo app_value($old, 'area_servico_solicitada'); ?>"></div>
                            <div class="field"><label for="chatLocation">Local do atendimento (opcional)</label><input id="chatLocation" name="localizacao_servico" maxlength="255" value="<?php echo app_value($old, 'localizacao_servico'); ?>"></div>
                            <div class="field full-width"><label for="chatDescription">Descrição do serviço</label><textarea id="chatDescription" name="descricao_servico" rows="3" maxlength="10000" required><?php echo app_value($old, 'descricao_servico'); ?></textarea></div>
                            <?php $order = []; require __DIR__ . '/backend/views/proposta-campos.php'; ?>
                        </div>
                        <button class="button" type="submit">Enviar proposta</button>
                    </form>
                </details>
            <?php elseif ($viewer['role'] === 'contratante'): ?>
                <div class="chat-proposal"><a href="solicitacao_orcamento.php?terceirizada_id=<?php echo (int)$conversation['prestador_id']; ?>">Solicitar orçamento</a></div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>
</main>
</body>
</html>
