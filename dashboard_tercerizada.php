<?php
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo, ['prestador']);
$user_id = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT u.email,u.tipo_pessoa,e.* FROM usuarios u JOIN empresas e ON e.usuario_id=u.id WHERE u.id=?');
$stmt->execute([$user_id]);
$dados_bd = $stmt->fetch();
if (!$dados_bd) abort_request(404, 'Perfil indisponível.');
$is_mei = $dados_bd['tipo_empresa'] === 'mei';
$tipo_label = account_type_label($dados_bd['tipo_empresa'], $dados_bd['tipo_pessoa']);
$dados_perfil = [];
foreach (['nome','responsavel','telefone','descricao','area_atuacao','regiao','horario','cep','logradouro','numero','complemento','bairro','cidade','estado'] as $key) {
    $dados_perfil[$key] = htmlspecialchars($dados_bd[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
$foto = htmlspecialchars(safe_photo($dados_bd['foto_path'] ?? null), ENT_QUOTES, 'UTF-8');
$stmt = $pdo->prepare("SELECT s.id AS solicitacao_id,s.contratante_id,s.data_solicitacao,s.descricao_servico,s.localizacao_servico,s.area_servico_solicitada,s.numero_funcionarios,e.nome AS nome_contratante,e.foto_path FROM solicitacoes s JOIN empresas e ON e.usuario_id=s.contratante_id WHERE s.terceirizada_id=? AND s.status='aberta' ORDER BY s.data_solicitacao DESC");
$stmt->execute([$user_id]);
$solicitacoes_recebidas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiConnect | Área do prestador</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/tercerizada.css">
    <link rel="stylesheet" href="css/perfis.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<header class="dashboard-header terceirizada-header">
    <div class="header-container">
        <h1 class="logo-title">ServiConnect <span class="badge-role"><?php echo htmlspecialchars($tipo_label); ?></span></h1>
        <nav class="main-nav" aria-label="Área do prestador">
            <a href="#pedidos" id="feedLink" class="nav-item"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Pedidos</a>
            <a href="#perfil" id="perfilLink" class="nav-item"><i class="fa-solid fa-user-circle" aria-hidden="true"></i> Meu perfil</a>
        </nav>
        <div class="user-control">
            <span class="user-display">Olá, <?php echo $dados_perfil['nome']; ?></span>
            <form action="logout.php" method="post" style="display:inline;margin:0"><?php echo csrf_field(); ?><button type="submit" class="logout-btn">Sair</button></form>
        </div>
    </div>
</header>
<main class="main-content-dashboard">
    <section id="pedidos" class="content-section">
        <h2 class="section-title">Solicitações de orçamento</h2>
        <?php if (empty($solicitacoes_recebidas)): ?>
            <div class="widget-card"><p>Nenhuma solicitação pendente no momento. Complete seu perfil para apresentar seus serviços.</p><a href="#perfil">Editar meu perfil</a></div>
        <?php else: ?>
            <div class="requests-list">
            <?php foreach ($solicitacoes_recebidas as $s): ?>
                <article class="solicitacao-card widget-card">
                    <h3>Pedido de <a href="perfil_empresa.php?id=<?php echo (int)$s['contratante_id']; ?>"><?php echo htmlspecialchars($s['nome_contratante']); ?></a></h3>
                    <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($s['data_solicitacao'])); ?></p>
                    <p><strong>Serviço:</strong> <?php echo htmlspecialchars($s['area_servico_solicitada'] ?? ''); ?></p>
                    <?php if (!$is_mei && $s['numero_funcionarios']): ?><p><strong>Pessoas solicitadas:</strong> <?php echo (int)$s['numero_funcionarios']; ?></p><?php endif; ?>
                    <p><strong>Local do serviço:</strong> <?php echo htmlspecialchars($s['localizacao_servico'] ?? ''); ?></p>
                    <p class="description-short"><?php echo htmlspecialchars(mb_strimwidth($s['descricao_servico'],0,200,'…')); ?></p>
                    <div class="request-actions"><a href="perfil_empresa.php?id=<?php echo (int)$s['contratante_id']; ?>" class="profile-action">Ver contratante</a><a href="responder_orcamento.php?id=<?php echo (int)$s['solicitacao_id']; ?>" class="profile-action profile-action-primary">Responder orçamento</a></div>
                </article>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <section id="perfil" class="content-section">
        <h2 class="section-title">Meu perfil de serviços</h2>
        <?php if (($_GET['status'] ?? '') === 'perfil_atualizado'): ?><div class="profile-feedback" role="status">Perfil atualizado com sucesso.</div><?php endif; ?>
        <form action="backend/controllers/PerfilTercerizadaController.php" method="post" enctype="multipart/form-data" class="widget-card account-profile-form" id="formPerfilPrestador">
            <?php echo csrf_field(); ?>
            <div class="profile-identity">
                <p><strong>Tipo de conta:</strong> <?php echo htmlspecialchars($tipo_label); ?></p>
                <p><strong>CNPJ:</strong> <?php echo htmlspecialchars(format_document($_SESSION['user_cnpj'] ?? '')); ?></p>
                <p class="profile-help">O documento e o tipo de conta são os dados de identificação do cadastro.</p>
            </div>
            <div class="profile-photo-editor">
                <img src="<?php echo $foto; ?>" alt="Foto atual do seu perfil">
                <div class="profile-field"><label for="fotoPerfil">Foto ou logotipo</label><input type="file" id="fotoPerfil" name="foto_perfil" accept="image/jpeg,image/png,image/webp" aria-describedby="fotoAjuda"><p class="profile-help" id="fotoAjuda">JPG, PNG ou WebP, até 2 MB.</p></div>
            </div>
            <fieldset>
                <legend>Apresentação e contato</legend>
                <div class="profile-form-grid">
                    <div class="profile-field profile-field-wide"><label for="nomePrestador">Nome exibido no perfil</label><input id="nomePrestador" name="nome" maxlength="150" value="<?php echo $dados_perfil['nome']; ?>" required autocomplete="organization"></div>
                    <div class="profile-field"><label for="responsavelPrestador">Responsável pelo atendimento</label><input id="responsavelPrestador" name="responsavel" maxlength="100" value="<?php echo $dados_perfil['responsavel']; ?>" autocomplete="name"></div>
                    <div class="profile-field"><label for="telefonePrestador">Telefone ou WhatsApp de contato</label><input type="tel" id="telefonePrestador" name="telefone" maxlength="20" value="<?php echo $dados_perfil['telefone']; ?>" autocomplete="tel"><p class="profile-help">Esse contato aparece no seu perfil de serviços.</p></div>
                    <div class="profile-field profile-field-wide"><label for="textoPerfil">Apresentação dos seus serviços</label><textarea id="textoPerfil" name="texto_perfil" maxlength="10000" rows="5" placeholder="Conte o que você faz, sua experiência e os diferenciais do seu atendimento."><?php echo $dados_perfil['descricao']; ?></textarea></div>
                </div>
            </fieldset>
            <fieldset>
                <legend>Serviços e atendimento</legend>
                <div class="profile-form-grid">
                    <div class="profile-field"><label for="areaAtuacao">Serviços oferecidos</label><input id="areaAtuacao" name="area_atuacao" maxlength="100" value="<?php echo $dados_perfil['area_atuacao']; ?>" placeholder="Ex.: jardinagem e manutenção de jardins"></div>
                    <div class="profile-field"><label for="regiaoAtendida">Regiões atendidas</label><input id="regiaoAtendida" name="regiao" maxlength="255" value="<?php echo $dados_perfil['regiao']; ?>" placeholder="Ex.: Ferraz de Vasconcelos e Poá"></div>
                    <div class="profile-field"><label for="horarioAtendimento">Dias e horários de atendimento</label><input id="horarioAtendimento" name="horario" maxlength="50" value="<?php echo $dados_perfil['horario']; ?>" placeholder="Ex.: segunda a sexta, das 8h às 18h"></div>
                    <?php if (!$is_mei): ?><div class="profile-field"><label for="numFuncionarios">Quantidade de colaboradores (opcional)</label><input type="number" id="numFuncionarios" name="num_funcionarios" min="0" max="100000" value="<?php echo (int)$dados_bd['num_funcionarios']; ?>"></div><?php endif; ?>
                </div>
            </fieldset>
            <fieldset>
                <legend>Endereço base</legend>
                <?php if ($is_mei): ?><p class="profile-help">Seu perfil mostra apenas cidade e estado para outros usuários. O endereço completo fica disponível para você.</p><?php endif; ?>
                <div class="profile-form-grid">
                    <?php foreach ([['cepEmpresa','cep_empresa','cep','CEP',8,'postal-code'],['logradouroEmpresa','logradouro_empresa','logradouro','Rua ou avenida',100,'address-line1'],['numeroEmpresa','numero_empresa','numero','Número',10,'off'],['complementoEmpresa','complemento_empresa','complemento','Complemento (opcional)',100,'address-line2'],['bairroEmpresa','bairro_empresa','bairro','Bairro',100,'address-level3'],['cidadeEmpresa','cidade_empresa','cidade','Cidade',100,'address-level2'],['estadoEmpresa','estado_empresa','estado','UF',2,'address-level1']] as [$id,$name,$key,$label,$max,$autocomplete]): ?>
                    <div class="profile-field"><label for="<?php echo $id; ?>"><?php echo $label; ?></label><input id="<?php echo $id; ?>" name="<?php echo $name; ?>" maxlength="<?php echo $key==='cep' ? 9 : $max; ?>" value="<?php echo $dados_perfil[$key]; ?>" autocomplete="<?php echo $autocomplete; ?>"<?php if ($key==='cep') echo ' inputmode="numeric"'; ?>></div>
                    <?php endforeach; ?>
                </div>
                <p class="profile-help" id="cepStatus" role="status"></p>
            </fieldset>
            <button type="submit" class="profile-save contratante-btn">Salvar perfil</button>
        </form>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = {pedidos: document.getElementById('pedidos'), perfil: document.getElementById('perfil')};
    const links = {pedidos: document.getElementById('feedLink'), perfil: document.getElementById('perfilLink')};
    function showTab() {
        const active = window.location.hash === '#perfil' ? 'perfil' : 'pedidos';
        for (const key of Object.keys(tabs)) {
            tabs[key].hidden = key !== active;
            links[key].classList.toggle('nav-active', key === active);
            if (key === active) links[key].setAttribute('aria-current', 'page'); else links[key].removeAttribute('aria-current');
        }
    }
    window.addEventListener('hashchange', showTab); showTab();
    const cep = document.getElementById('cepEmpresa');
    const status = document.getElementById('cepStatus');
    cep.addEventListener('blur', async () => {
        const code = cep.value.replace(/\D/g, '');
        if (code.length !== 8) return;
        status.textContent = 'Consultando CEP…';
        try {
            const response = await fetch('https://viacep.com.br/ws/' + code + '/json/');
            if (!response.ok) throw new Error('CEP indisponível');
            const data = await response.json();
            if (cep.value.replace(/\D/g, '') !== code) return;
            if (data.erro) throw new Error('CEP não encontrado');
            for (const [id,key] of [['logradouroEmpresa','logradouro'],['bairroEmpresa','bairro'],['cidadeEmpresa','localidade'],['estadoEmpresa','uf']]) document.getElementById(id).value = data[key] || '';
            status.textContent = 'Endereço preenchido. Confira os dados e informe o número.';
        } catch (_) { status.textContent = 'Não foi possível consultar o CEP. Você pode preencher o endereço manualmente.'; }
    });
});
</script>
</body>
</html>
