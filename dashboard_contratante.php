<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'contratante') {
    header('Location: login.php');
    exit;
}

require_once 'backend/conexao.php';
$user_id = $_SESSION['user_id'];
$cnpj_logado = $_SESSION['user_cnpj'] ?? '';

// 1. Busca dados do perfil do Contratante
$stmt = $pdo->prepare("SELECT u.email, e.* FROM usuarios u JOIN empresas e ON u.id = e.usuario_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$dados_bd = $stmt->fetch(PDO::FETCH_ASSOC);

$dados_perfil = [
    'nome' => htmlspecialchars($dados_bd['nome'] ?? 'Contratante'),
    'email' => htmlspecialchars($dados_bd['email']),
    'telefone' => htmlspecialchars($dados_bd['telefone'] ?? ''),
    'responsavel' => htmlspecialchars($dados_bd['responsavel'] ?? ''),
    'foto_path' => htmlspecialchars($dados_bd['foto_path'] ?? 'img/default_avatar.png'),
    'cep' => htmlspecialchars($dados_bd['cep'] ?? ''),
    'logradouro' => htmlspecialchars($dados_bd['logradouro'] ?? ''),
    'numero' => htmlspecialchars($dados_bd['numero'] ?? ''),
    'bairro' => htmlspecialchars($dados_bd['bairro'] ?? ''),
    'cidade' => htmlspecialchars($dados_bd['cidade'] ?? ''),
    'estado' => htmlspecialchars($dados_bd['estado'] ?? ''),
];

// 2. Busca lista de Terceirizadas para o MAPA e FEED
$stmtTerceirizadas = $pdo->prepare("
    SELECT u.id as usuario_id, e.nome AS nome_empresa, e.descricao AS descricao_servicos, 
           e.regiao AS regioes_atendidas, e.foto_path, e.latitude, e.longitude, e.logradouro, e.bairro
    FROM usuarios u JOIN empresas e ON u.id = e.usuario_id
    WHERE u.tipo_conta = 'empresa' AND e.tipo_empresa = 'terceirizada'
");
$stmtTerceirizadas->execute();
$terceirizadas = $stmtTerceirizadas->fetchAll(PDO::FETCH_ASSOC);

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
                <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
            </div>
        </div>
    </div>

    <main class="main-content-dashboard">
        
        <section id="busca" class="content-section active-section">
            <h2 class="section-title"><i class="fa-solid fa-handshake"></i> Encontre o Serviço Perfeito</h2>
            
            <div class="map-view-container">
                <div class="sidebar-list">
                    <?php if (empty($terceirizadas)): ?>
                        <div class="widget-card"><p>Nenhuma empresa terceirizada encontrada na sua região.</p></div>
                    <?php else: ?>
                        <?php foreach ($terceirizadas as $index => $t): ?>
                            <div class="terceirizada-card widget-card" onclick="focarNoMapa(<?php echo $index; ?>)">
                                <div class="profile-info">
                                    <?php $foto_terc = !empty($t['foto_path']) ? $t['foto_path'] : 'img/default_avatar.png'; ?>
                                    <img src="<?php echo htmlspecialchars($foto_terc); ?>" alt="Logo" class="terceirizada-logo" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid #ddd;">
                                    <div class="text-info">
                                        <h4 style="margin:0 0 5px 0; font-size:1.1em;"><?php echo htmlspecialchars($t['nome_empresa']); ?></h4>
                                        <p class="service-area" style="margin:0; font-size:0.85em; color:#555;">
                                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($t['bairro'] ?? 'Região'); ?>
                                        </p>
                                    </div>
                                </div>
                                <div style="display:flex; gap:5px; margin-top:12px;">
                                    <a href="perfil_empresa.php?id=<?php echo $t['usuario_id']; ?>" class="btn-primary" style="flex:1; text-align:center; background:#333; font-size:0.8em; padding:8px;"><i class="fa-solid fa-eye"></i> Perfil</a>
                                    <a href="solicitacao_orcamento.php?terceirizada_id=<?php echo $t['usuario_id']; ?>" class="btn-primary request-btn contratante-btn" style="flex:2; text-align:center; font-size:0.8em; padding:8px;"><i class="fa-solid fa-comments-dollar"></i> Orçamento</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="map-wrapper">
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
                                    <img src="<?php echo htmlspecialchars($pedido['foto_path'] ?? 'img/default_avatar.png'); ?>" alt="Logo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
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

        <section id="perfil" class="content-section" style="display: none;">
            <h2 class="section-title"><i class="fa-solid fa-building"></i> Dados da Sua Empresa</h2>
            
            <form id="formCadastroEmpregado" action="backend/controllers/PerfilContratanteController.php" method="post" enctype="multipart/form-data" class="widget-card">
                <div style="background-color: rgba(255, 196, 0, 0.1); padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid var(--secondary-yellow);">
                    <p><strong>Empresa:</strong> <?php echo $dados_perfil['nome']; ?> | <strong>CNPJ:</strong> <?php echo $cnpj_logado; ?></p>
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label>Logótipo ou Foto da Empresa</label>
                    <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                        <img src="<?php echo $dados_perfil['foto_path']; ?>" alt="Sua Foto" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 1px solid #ccc;">
                        <input type="file" name="foto_perfil" accept="image/*">
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;"><label>Responsável (Contato)</label><input type="text" name="responsavel" value="<?php echo $dados_perfil['responsavel']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 1;"><label>Telefone / WhatsApp</label><input type="text" name="telefone" value="<?php echo $dados_perfil['telefone']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>
                
                <h3 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Endereço Principal</h3>
                
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;"><label>CEP</label><input type="text" id="cep" name="cep" value="<?php echo $dados_perfil['cep']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 2;"><label>Rua/Avenida</label><input type="text" id="logradouro" name="logradouro" value="<?php echo $dados_perfil['logradouro']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;"><label>Número</label><input type="text" id="numero" name="numero" value="<?php echo $dados_perfil['numero']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 2;"><label>Bairro</label><input type="text" id="bairro" name="bairro" value="<?php echo $dados_perfil['bairro']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 3;"><label>Cidade</label><input type="text" id="cidade" name="cidade" value="<?php echo $dados_perfil['cidade']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 1;"><label>UF</label><input type="text" id="estado" name="estado" value="<?php echo $dados_perfil['estado']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>

                <button type="submit" class="btn btn-header contratante-btn" style="width: 100%; margin-top: 20px; padding: 15px; font-size: 1.1em;">
                    <i class="fa-solid fa-save"></i> Guardar Dados da Empresa
                </button>
            </form>
        </section>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputCep = document.getElementById('cepEmpresa') || document.getElementById('cep');
            
            if (inputCep) {
                inputCep.addEventListener('input', function() {
                    let cepLimpo = this.value.replace(/\D/g, ''); 
                    
                    if (cepLimpo.length === 8) {
                        let isEmpresa = this.id === 'cepEmpresa';
                        let fLogradouro = document.getElementById(isEmpresa ? 'logradouroEmpresa' : 'logradouro');
                        let fBairro = document.getElementById(isEmpresa ? 'bairroEmpresa' : 'bairro');
                        let fCidade = document.getElementById(isEmpresa ? 'cidadeEmpresa' : 'cidade');
                        let fEstado = document.getElementById(isEmpresa ? 'estadoEmpresa' : 'estado');
                        let fNumero = document.getElementById(isEmpresa ? 'numeroEmpresa' : 'numero');

                        fLogradouro.value = 'Buscando...';
                        fBairro.value = 'Buscando...';
                        fCidade.value = 'Buscando...';
                        fEstado.value = 'Buscando...';

                        fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`)
                        .then(resposta => resposta.json())
                        .then(dados => {
                            if (!dados.erro) {
                                fLogradouro.value = dados.logradouro || '';
                                fBairro.value = dados.bairro || '';
                                fCidade.value = dados.localidade || '';
                                fEstado.value = dados.uf || '';
                                fNumero.focus(); 
                            } else {
                                alert('CEP não encontrado. Verifique se digitou corretamente.');
                                fLogradouro.value = ''; fBairro.value = ''; fCidade.value = ''; fEstado.value = '';
                            }
                        })
                        .catch(() => {
                            alert('Erro de conexão ao buscar o CEP.');
                            fLogradouro.value = ''; fBairro.value = ''; fCidade.value = ''; fEstado.value = '';
                        });
                    }
                });
            }
        });
    </script>
    
    <script>
        const empresas = <?php echo json_encode($terceirizadas); ?>;
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
            map = L.map('mapaServicos').setView([-23.5398, -46.3686], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Adicionando os marcadores das empresas
            empresas.forEach((empresa, index) => {
                let lat = empresa.latitude ? parseFloat(empresa.latitude) : -23.5398 + (Math.random() - 0.5) * 0.04;
                let lng = empresa.longitude ? parseFloat(empresa.longitude) : -46.3686 + (Math.random() - 0.5) * 0.04;

                let marker = L.marker([lat, lng]).addTo(map);
                
                let foto = empresa.foto_path ? empresa.foto_path : 'img/default_avatar.png';
                let popupContent = `
                    <div style="text-align: center; width: 150px;">
                        <img src="${foto}" style="width: 50px; height: 50px; border-radius: 5px; object-fit: cover;">
                        <h4 style="margin: 5px 0;">${empresa.nome_empresa}</h4>
                        <a href="perfil_empresa.php?id=${empresa.usuario_id}" style="color: #0056b3; font-size: 12px; font-weight: bold; text-decoration: none;">Ver Perfil</a>
                    </div>
                `;
                marker.bindPopup(popupContent);
                marcadores.push(marker);
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