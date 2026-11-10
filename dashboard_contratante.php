<?php
require_once __DIR__ . '/backend/security.php';



require_once __DIR__ . '/backend/conexao.php';
require_role($pdo, ['contratante']);
$user_id = $_SESSION['user_id'];
$cnpj_logado = $_SESSION['user_cnpj'] ?? '';

// 1. Busca dados do perfil do Contratante
$stmt = $pdo->prepare("SELECT u.email, u.tipo_pessoa, e.* FROM usuarios u JOIN empresas e ON u.id = e.usuario_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$dados_bd = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$dados_bd) abort_request(404, 'Perfil indisponível.');
$is_pf = $dados_bd['tipo_pessoa'] === 'fisica';
$tipo_label = account_type_label($dados_bd['tipo_empresa'], $dados_bd['tipo_pessoa']);

$dados_perfil = [
    'nome' => htmlspecialchars($dados_bd['nome'] ?? 'Contratante'),
    'email' => htmlspecialchars($dados_bd['email']),
    'descricao' => htmlspecialchars($dados_bd['descricao'] ?? ''),
    'complemento' => htmlspecialchars($dados_bd['complemento'] ?? ''),
    'telefone' => htmlspecialchars($dados_bd['telefone'] ?? ''),
    'responsavel' => htmlspecialchars($dados_bd['responsavel'] ?? ''),
    'foto_path' => htmlspecialchars(safe_photo($dados_bd['foto_path'] ?? null)),
    'cep' => htmlspecialchars($dados_bd['cep'] ?? ''),
    'logradouro' => htmlspecialchars($dados_bd['logradouro'] ?? ''),
    'numero' => htmlspecialchars($dados_bd['numero'] ?? ''),
    'bairro' => htmlspecialchars($dados_bd['bairro'] ?? ''),
    'cidade' => htmlspecialchars($dados_bd['cidade'] ?? ''),
    'estado' => htmlspecialchars($dados_bd['estado'] ?? ''),
];

// 2. Busca lista de Terceirizadas para o MAPA e FEED
$stmtTerceirizadas = $pdo->prepare("
    SELECT u.id AS usuario_id,e.nome AS nome_empresa,e.descricao AS descricao_servicos,
           e.regiao AS regioes_atendidas,e.foto_path,e.tipo_empresa,e.area_atuacao,e.cidade,e.estado,
           CASE WHEN e.tipo_empresa='terceirizada' THEN e.latitude ELSE NULL END AS latitude,
           CASE WHEN e.tipo_empresa='terceirizada' THEN e.longitude ELSE NULL END AS longitude
    FROM usuarios u JOIN empresas e ON u.id=e.usuario_id
    WHERE u.tipo_conta='empresa' AND e.tipo_empresa IN ('terceirizada','mei') AND u.status='ativo'
    ORDER BY e.nome,u.id
");
$stmtTerceirizadas->execute();
$terceirizadas = $stmtTerceirizadas->fetchAll(PDO::FETCH_ASSOC);
foreach ($terceirizadas as &$prestador) $prestador['foto_path'] = safe_photo($prestador['foto_path']);
unset($prestador);

// 3. Busca lista de Pedidos de Orçamento feitos pelo Contratante
$stmtPedidos = $pdo->prepare("
    SELECT s.*, e.nome AS nome_terceirizada, e.foto_path
    FROM solicitacoes s
    JOIN empresas e ON s.terceirizada_id = e.usuario_id
    WHERE s.contratante_id = ?
    ORDER BY s.data_solicitacao DESC
");
$stmtPedidos->execute([$user_id]);
$meus_pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiConnect | Área do Contratante</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/contratante.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        /* Estilos específicos para a vista de Mapa */
        .map-view-container {
            display: flex;
            gap: 20px;
            height: 70vh; /* Ocupa 70% da altura do ecrã */
            margin-top: 15px;
        }
        .map-wrapper {
            flex: 1; /* O mapa ocupa o restante do espaço */
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ccc;
            z-index: 1; /* Mantém atrás dos menus */
        }
        #mapaServicos {
            width: 100%;
            height: 100%;
        }
        .sidebar-list {
            flex: 0 0 380px; /* Largura fixa para a barra lateral */
            overflow-y: auto; /* Adiciona scroll se houver muitas empresas */
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding-right: 10px;
        }

        /* Personalização do Scrollbar da Sidebar */
        .sidebar-list::-webkit-scrollbar { width: 8px; }
        .sidebar-list::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .sidebar-list::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        .sidebar-list::-webkit-scrollbar-thumb:hover { background: #555; }

        /* Ajuste fino no cartão da terceirizada para caber na sidebar */
        .terceirizada-card { margin-bottom: 0; cursor: pointer; transition: transform 0.2s; }
        .terceirizada-card:hover { transform: scale(1.02); border-color: var(--secondary-yellow); }
    </style>
    <link rel="stylesheet" href="css/perfis.css">
</head>
<body>
    <div class="dashboard-header contratante-header">
        <div class="header-container">
            <h1 class="logo-title">ServiConnect <span class="badge-role">Contratante</span></h1>

            <nav class="main-nav">
                <a href="#busca" id="buscaLink" class="nav-item nav-active"><i class="fa-solid fa-map-location-dot"></i> Mapa de Serviços</a>
                <a href="#pedidos" id="pedidosLink" class="nav-item"><i class="fa-solid fa-file-invoice-dollar"></i> Meus Pedidos</a>
                <a href="#perfil" id="perfilLink" class="nav-item"><i class="fa-solid fa-building-user"></i> Meu Perfil</a>
            </nav>

            <div class="user-control">
                <img src="<?php echo $dados_perfil['foto_path']; ?>" alt="Perfil" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 2px solid var(--secondary-yellow);">
                <span class="user-display">Olá, <?php echo $dados_perfil['nome']; ?></span>
                <form action="logout.php" method="post" style="display:inline;margin:0"><?php echo csrf_field(); ?><button type="submit" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</button></form>
            </div>
        </div>
    </div>

    <main class="main-content-dashboard">

        <section id="busca" class="content-section active-section">
            <h2 class="section-title"><i class="fa-solid fa-handshake"></i> Encontre o Serviço Perfeito</h2>

            <div class="map-view-container">
                <div class="sidebar-list account-provider-list">
                    <?php if (empty($terceirizadas)): ?>
                        <div class="widget-card"><p>Nenhum prestador disponível no momento.</p></div>
                    <?php else: ?>
                        <?php foreach ($terceirizadas as $index => $t): ?>
                            <article class="terceirizada-card widget-card">
                                <div class="profile-info">
                                    <img src="<?php echo htmlspecialchars($t['foto_path']); ?>" alt="Foto de <?php echo htmlspecialchars($t['nome_empresa']); ?>" class="terceirizada-logo" width="50" height="50">
                                    <div class="text-info">
                                        <h3 style="margin:0 0 6px;font-size:1.1rem"><?php echo htmlspecialchars($t['nome_empresa']); ?></h3>
                                        <span class="profile-type"><?php echo htmlspecialchars(account_type_label($t['tipo_empresa'],'juridica')); ?></span>
                                        <p class="provider-service"><?php echo htmlspecialchars($t['area_atuacao'] ?: 'Serviços a consultar'); ?></p>
                                        <p class="service-area"><?php echo htmlspecialchars(trim(($t['cidade'] ?? '').(!empty($t['estado']) ? ' / '.$t['estado'] : '')) ?: 'Localização não informada'); ?></p>
                                    </div>
                                </div>
                                <div class="provider-actions">
                                    <a href="perfil_empresa.php?id=<?php echo (int)$t['usuario_id']; ?>" class="profile-action">Ver perfil</a>
                                    <a href="solicitacao_orcamento.php?terceirizada_id=<?php echo (int)$t['usuario_id']; ?>" class="profile-action profile-action-primary">Orçamento</a>
                                    <?php if ($t['latitude'] !== null && $t['longitude'] !== null): ?><button type="button" class="map-focus profile-action" onclick="focarNoMapa(<?php echo (int)$index; ?>)">Ver no mapa</button><?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="map-wrapper">
                    <p style="margin:8px">O mapa exibe empresas com localização informada. MEIs aparecem na lista, com sua região de atendimento.</p>
                    <div id="mapaServicos"></div>
                </div>
            </div>
        </section>

        <section id="pedidos" class="content-section" style="display: none;">
            <h2 class="section-title"><i class="fa-solid fa-file-invoice-dollar"></i> Orçamentos Solicitados</h2>

            <?php if (empty($meus_pedidos)): ?>
                <div class="widget-card">
                    <p style="text-align: center; color: #666; padding: 20px;">Você ainda não solicitou nenhum orçamento. Navegue pelo Mapa de Serviços para começar!</p>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 20px;">
                    <?php foreach ($meus_pedidos as $pedido): ?>
                        <div class="widget-card" style="border-left: 5px solid <?php
                            if ($pedido['status'] === 'aberta') echo '#ffc107'; // Amarelo
                            elseif ($pedido['status'] === 'orçada' || $pedido['status'] === 'respondida') echo '#28a745'; // Verde
                            else echo '#dc3545'; // Vermelho (recusada)
                        ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                                <div style="display: flex; gap: 15px; align-items: center;">
                                    <img src="<?php echo htmlspecialchars($pedido['foto_path'] ?? 'img/default_avatar.svg'); ?>" alt="Logo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                    <div>
                                        <h3 style="margin: 0; font-size: 1.2em;">Pedido para: <a href="perfil_empresa.php?id=<?php echo $pedido['terceirizada_id']; ?>" style="color: var(--primary-blue); text-decoration: none;"><?php echo htmlspecialchars($pedido['nome_terceirizada']); ?></a></h3>
                                        <p style="margin: 5px 0 0 0; font-size: 0.85em; color: #666;"><i class="fa-solid fa-calendar-day"></i> Solicitado em: <?php echo date('d/m/Y', strtotime($pedido['data_solicitacao'])); ?></p>
                                    </div>
                                </div>

                                <div style="text-align: right; min-width: 150px;">
                                    <?php if ($pedido['status'] === 'aberta'): ?>
                                        <span style="display: inline-block; background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 50px; font-size: 0.9em; font-weight: bold;"><i class="fa-solid fa-clock"></i> Aguardando Resposta</span>
                                    <?php elseif ($pedido['status'] === 'orçada' || $pedido['status'] === 'respondida'): ?>
                                        <span style="display: inline-block; background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 50px; font-size: 0.9em; font-weight: bold;"><i class="fa-solid fa-check-circle"></i> Respondido</span>
                                        <p style="margin: 5px 0 0 0; font-weight: bold; color: var(--primary-blue); font-size: 1.1em;">Valor: R$ <?php echo number_format($pedido['valor_orcamento'], 2, ',', '.'); ?></p>
                                    <?php elseif ($pedido['status'] === 'recusada'): ?>
                                        <span style="display: inline-block; background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 50px; font-size: 0.9em; font-weight: bold;"><i class="fa-solid fa-xmark-circle"></i> Recusado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee;">
                                <p style="margin: 0; font-size: 0.9em;"><strong>Serviço:</strong> <?php echo htmlspecialchars($pedido['area_servico_solicitada']); ?> | <strong>Local:</strong> <?php echo htmlspecialchars($pedido['localizacao_servico']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="perfil" class="content-section" style="display:none">
            <h2 class="section-title">Meu perfil de contratante</h2>
            <?php if (($_GET['status'] ?? '') === 'perfil_atualizado'): ?><div class="profile-feedback" role="status">Perfil atualizado com sucesso.</div><?php endif; ?>
            <form id="formPerfilContratante" action="backend/controllers/PerfilContratanteController.php" method="post" enctype="multipart/form-data" class="widget-card account-profile-form">
                <?php echo csrf_field(); ?>
                <div class="profile-identity">
                    <p><strong>Tipo de conta:</strong> <?php echo htmlspecialchars($tipo_label); ?></p>
                    <p><strong><?php echo $is_pf ? 'CPF' : 'CNPJ'; ?>:</strong> <?php echo htmlspecialchars(format_document($cnpj_logado)); ?></p>
                    <p class="profile-help">O documento e o tipo de conta são os dados de identificação do cadastro.</p>
                </div>
                <div class="profile-photo-editor">
                    <img src="<?php echo $dados_perfil['foto_path']; ?>" alt="Foto atual do seu perfil">
                    <div class="profile-field"><label for="fotoContratante"><?php echo $is_pf ? 'Foto do perfil' : 'Foto ou logotipo'; ?></label><input type="file" id="fotoContratante" name="foto_perfil" accept="image/jpeg,image/png,image/webp" aria-describedby="fotoAjuda"><p class="profile-help" id="fotoAjuda">JPG, PNG ou WebP, até 2 MB.</p></div>
                </div>
                <fieldset>
                    <legend>Apresentação e contato</legend>
                    <div class="profile-form-grid">
                        <div class="profile-field profile-field-wide"><label for="nomeContratante"><?php echo $is_pf ? 'Nome completo' : 'Nome da empresa'; ?></label><input id="nomeContratante" name="nome" value="<?php echo $dados_perfil['nome']; ?>" maxlength="150" required autocomplete="<?php echo $is_pf ? 'name' : 'organization'; ?>"></div>
                        <?php if (!$is_pf): ?><div class="profile-field"><label for="responsavelContratante">Responsável pelo contato</label><input id="responsavelContratante" name="responsavel" value="<?php echo $dados_perfil['responsavel']; ?>" maxlength="100" autocomplete="name"></div><?php endif; ?>
                        <div class="profile-field"><label for="telefoneContratante">Telefone ou WhatsApp de contato</label><input type="tel" id="telefoneContratante" name="telefone" value="<?php echo $dados_perfil['telefone']; ?>" maxlength="20" autocomplete="tel"><?php if ($is_pf): ?><p class="profile-help">Compartilhado com os prestadores que recebem seus pedidos e com a administração da plataforma.</p><?php endif; ?></div>
                        <div class="profile-field profile-field-wide"><label for="textoPerfil">Apresentação (opcional)</label><textarea id="textoPerfil" name="texto_perfil" maxlength="10000" rows="4" placeholder="Conte brevemente sobre você ou sua empresa."><?php echo $dados_perfil['descricao']; ?></textarea></div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Endereço principal</legend>
                    <?php if ($is_pf): ?><p class="profile-help">Seu perfil mostra apenas cidade e estado para outros usuários. O endereço completo fica disponível para você.</p><?php endif; ?>
                    <div class="profile-form-grid">
                        <?php foreach ([['cep','CEP',9,'postal-code'],['logradouro','Rua ou avenida',100,'address-line1'],['numero','Número',10,'off'],['complemento','Complemento (opcional)',100,'address-line2'],['bairro','Bairro',100,'address-level3'],['cidade','Cidade',100,'address-level2'],['estado','UF',2,'address-level1']] as [$key,$label,$max,$autocomplete]): ?>
                        <div class="profile-field"><label for="<?php echo $key; ?>"><?php echo $label; ?></label><input id="<?php echo $key; ?>" name="<?php echo $key; ?>" maxlength="<?php echo $max; ?>" value="<?php echo $dados_perfil[$key]; ?>" autocomplete="<?php echo $autocomplete; ?>"<?php if ($key==='cep') echo ' inputmode="numeric"'; ?>></div>
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
        const cep = document.getElementById('cep');
        const status = document.getElementById('cepStatus');
        cep.addEventListener('blur', async () => {
            const code = cep.value.replace(/\D/g, '');
            if (code.length !== 8) return;
            status.textContent = 'Consultando CEP…';
            try {
                const response = await fetch('https://viacep.com.br/ws/' + code + '/json/');
                if (!response.ok) throw new Error('Consulta indisponível');
                const data = await response.json();
                if (cep.value.replace(/\D/g, '') !== code) return;
                if (data.erro) throw new Error('CEP não encontrado');
                for (const [id,key] of [['logradouro','logradouro'],['bairro','bairro'],['cidade','localidade'],['estado','uf']]) document.getElementById(id).value = data[key] || '';
                status.textContent = 'Endereço preenchido. Confira os dados e informe o número.';
            } catch (_) { status.textContent = 'Não foi possível consultar o CEP. Você pode preencher o endereço manualmente.'; }
        });
    });
    </script>

    <script>
        const empresas = <?php echo json_encode($terceirizadas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        let map;
        let marcadores = [];

        document.addEventListener('DOMContentLoaded', () => {
            // Lógica de troca de abas com a nova aba MEUS PEDIDOS
            const buscaLink = document.getElementById('buscaLink');
            const pedidosLink = document.getElementById('pedidosLink');
            const perfilLink = document.getElementById('perfilLink');

            const buscaSection = document.getElementById('busca');
            const pedidosSection = document.getElementById('pedidos');
            const perfilSection = document.getElementById('perfil');

            function switchView(view) {
                buscaSection.style.display = 'none';
                pedidosSection.style.display = 'none';
                perfilSection.style.display = 'none';

                buscaLink.classList.remove('nav-active');
                pedidosLink.classList.remove('nav-active');
                perfilLink.classList.remove('nav-active');

                if (view === 'busca') {
                    buscaSection.style.display = 'block';
                    buscaLink.classList.add('nav-active');
                    if (map) { setTimeout(() => { map.invalidateSize(); }, 100); }
                } else if (view === 'pedidos') {
                    pedidosSection.style.display = 'block';
                    pedidosLink.classList.add('nav-active');
                } else {
                    perfilSection.style.display = 'block';
                    perfilLink.classList.add('nav-active');
                }
            }

            buscaLink.addEventListener('click', (e) => { e.preventDefault(); switchView('busca'); });
            pedidosLink.addEventListener('click', (e) => { e.preventDefault(); switchView('pedidos'); });
            perfilLink.addEventListener('click', (e) => { e.preventDefault(); switchView('perfil'); });

            // Verifica qual aba abrir dependendo da URL
            if(window.location.hash === '#perfil') {
                switchView('perfil');
            } else if (window.location.hash === '#pedidos' || window.location.search.includes('solicitacao_enviada')) {
                // Se a URL tiver ?status=solicitacao_enviada, já abre a tela de pedidos direto!
                switchView('pedidos');
            } else {
                switchView('busca'); // Default
            }

            // INICIALIZANDO O MAPA LEAFLET
            if (typeof L === 'undefined') {
                document.getElementById('mapaServicos').textContent = 'Mapa indisponível. Você pode acessar os prestadores pela lista.';
                return;
            }
            map = L.map('mapaServicos').setView([-23.5398, -46.3686], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Adicionando os marcadores das empresas
            empresas.forEach((empresa, index) => {
                const lat = empresa.latitude === null ? NaN : Number(empresa.latitude);
                const lng = empresa.longitude === null ? NaN : Number(empresa.longitude);
                if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                    marcadores[index] = null;
                    return;
                }
                const marker = L.marker([lat, lng]).addTo(map);
                const popup = document.createElement('div');
                const image = document.createElement('img');
                image.src = empresa.foto_path;
                image.alt = 'Foto do prestador';
                image.style.cssText = 'width:50px;height:50px;object-fit:cover';
                const title = document.createElement('h4');
                title.textContent = empresa.nome_empresa;
                const link = document.createElement('a');
                link.href = 'perfil_empresa.php?id=' + encodeURIComponent(empresa.usuario_id);
                link.textContent = 'Ver perfil';
                popup.append(image, title, link);
                marker.bindPopup(popup);
                marcadores[index] = marker;
            });
        });

        function focarNoMapa(index) {
            let marker = marcadores[index];
            if(marker) {
                map.flyTo(marker.getLatLng(), 16);
                marker.openPopup();
            }
        }
    </script>
</body>
</html>
