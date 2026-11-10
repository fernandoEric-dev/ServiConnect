<?php
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo);
$profileId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$profileId) abort_request(404, 'Perfil indisponível.');
$stmt = $pdo->prepare("SELECT u.tipo_pessoa,e.* FROM usuarios u JOIN empresas e ON e.usuario_id=u.id WHERE u.id=? AND u.status='ativo'");
$stmt->execute([$profileId]);
$empresa = $stmt->fetch();
if (!$empresa) abort_request(404, 'Perfil indisponível.');
$ownProfile = ($_SESSION['user_role'] ?? '') !== 'admin' && (int)$_SESSION['user_id'] === (int)$empresa['usuario_id'];
$isProvider = in_array($empresa['tipo_empresa'], ['mei','terceirizada'], true);
$privateAddress = !$ownProfile && ($empresa['tipo_pessoa'] === 'fisica' || $empresa['tipo_empresa'] === 'mei');
$canSeeContact = $ownProfile || $isProvider || $empresa['tipo_pessoa'] === 'juridica' || $_SESSION['user_role'] === 'admin';
if (!$canSeeContact && $_SESSION['user_role'] === 'prestador') {
    $contactPermission = $pdo->prepare('SELECT 1 FROM solicitacoes WHERE contratante_id=? AND terceirizada_id=? LIMIT 1');
    $contactPermission->execute([$profileId, $_SESSION['user_id']]);
    $canSeeContact = (bool)$contactPermission->fetchColumn();
}
$label = account_type_label($empresa['tipo_empresa'], $empresa['tipo_pessoa']);
$foto = safe_photo($empresa['foto_path'] ?? null);
$location = trim(($empresa['cidade'] ?? '') . (!empty($empresa['estado']) ? ' / '.$empresa['estado'] : ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($empresa['nome']); ?> | ServiConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/perfis.css">
</head>
<body>
<main class="profile-container">
    <a href="dashboard.php" class="profile-action profile-back">Voltar ao meu painel</a>
    <article class="widget-card">
        <div class="profile-card-header">
            <img src="<?php echo htmlspecialchars($foto); ?>" alt="Foto de <?php echo htmlspecialchars($empresa['nome']); ?>" class="profile-avatar">
            <div><h1><?php echo htmlspecialchars($empresa['nome']); ?></h1><span class="profile-type"><?php echo htmlspecialchars($label); ?></span></div>
        </div>
        <div class="profile-info-grid">
            <section class="profile-info-item profile-info-wide"><h2>Apresentação</h2><p><?php echo htmlspecialchars($empresa['descricao'] ?: 'Este perfil ainda não tem uma apresentação.'); ?></p></section>
            <?php if ($isProvider): ?>
                <section class="profile-info-item"><h2>Serviços oferecidos</h2><p><?php echo htmlspecialchars($empresa['area_atuacao'] ?: 'Não informado'); ?></p></section>
                <section class="profile-info-item"><h2>Regiões atendidas</h2><p><?php echo htmlspecialchars($empresa['regiao'] ?: 'Não informado'); ?></p></section>
                <section class="profile-info-item"><h2>Dias e horários de atendimento</h2><p><?php echo htmlspecialchars($empresa['horario'] ?: 'Consulte o prestador'); ?></p></section>
                <?php if ($empresa['tipo_empresa'] === 'terceirizada'): ?><section class="profile-info-item"><h2>Equipe</h2><p><?php echo (int)$empresa['num_funcionarios'] > 0 ? (int)$empresa['num_funcionarios'].' colaboradores' : 'Não informado'; ?></p></section><?php endif; ?>
            <?php endif; ?>
            <?php if ($canSeeContact): ?>
                <section class="profile-info-item"><h2>Contato</h2><p><?php echo htmlspecialchars($empresa['telefone'] ?: 'Não informado'); ?></p></section>
                <?php if (!empty($empresa['responsavel'])): ?><section class="profile-info-item"><h2>Responsável pelo atendimento</h2><p><?php echo htmlspecialchars($empresa['responsavel']); ?></p></section><?php endif; ?>
            <?php endif; ?>
            <section class="profile-info-item profile-info-wide"><h2><?php echo $privateAddress ? 'Cidade e estado' : 'Localização'; ?></h2>
                <p><?php
                    if ($privateAddress) {
                        echo htmlspecialchars($location ?: 'Não informado');
                    } elseif (!empty($empresa['logradouro'])) {
                        $address = $empresa['logradouro'];
                        if (!empty($empresa['numero'])) $address .= ', '.$empresa['numero'];
                        if (!empty($empresa['complemento'])) $address .= ' — '.$empresa['complemento'];
                        $parts = [$address];
                        if (!empty($empresa['bairro'])) $parts[] = $empresa['bairro'];
                        if ($location !== '') $parts[] = $location;
                        if (!empty($empresa['cep'])) $parts[] = 'CEP: '.$empresa['cep'];
                        echo htmlspecialchars(implode("\n", $parts));
                    } else {
                        echo htmlspecialchars($location ?: 'Não informado');
                    }
                ?></p>
            </section>
        </div>
        <?php if ($isProvider && ($_SESSION['user_role'] ?? '') === 'contratante'): ?><div class="profile-actions"><a class="profile-action profile-action-primary" href="solicitacao_orcamento.php?terceirizada_id=<?php echo (int)$empresa['usuario_id']; ?>">Solicitar orçamento</a></div><?php endif; ?>
    </article>
</main>
</body>
</html>
