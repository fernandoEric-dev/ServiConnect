<?php
// dashboard_contratante.php
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

// 2. Busca lista de Terceirizadas para o FEED
$stmtTerceirizadas = $pdo->prepare("
    SELECT u.id as usuario_id, e.nome AS nome_empresa, e.descricao AS descricao_servicos, e.regiao AS regioes_atendidas, e.foto_path
    FROM usuarios u JOIN empresas e ON u.id = e.usuario_id
    WHERE u.tipo_conta = 'empresa' AND e.tipo_empresa = 'terceirizada'
");
$stmtTerceirizadas->execute();
$terceirizadas = $stmtTerceirizadas->fetchAll(PDO::FETCH_ASSOC);
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
</head>
<body>
    <div class="dashboard-header contratante-header">
        <div class="header-container">
            <h1 class="logo-title">ServiConnect <span class="badge-role">Contratante</span></h1>
            
            <nav class="main-nav">
                <a href="#busca" id="buscaLink" class="nav-item nav-active"><i class="fa-solid fa-magnifying-glass"></i> Buscar Serviços</a>
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
            <div class="search-module widget-card">
                <p class="search-tip">Empresas terceirizadas disponíveis para contratação:</p>
                <div class="terceirizadas-list">
                    <?php if (empty($terceirizadas)): ?>
                        <p>Nenhuma empresa terceirizada encontrada no momento.</p>
                    <?php else: ?>
                        <?php foreach ($terceirizadas as $t): ?>
                            <div class="terceirizada-card widget-card">
                                <div class="profile-info">
                                    <?php $foto_terc = !empty($t['foto_path']) ? $t['foto_path'] : 'img/default_avatar.png'; ?>
                                    <img src="<?php echo htmlspecialchars($foto_terc); ?>" alt="Logo" class="terceirizada-logo" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                                    <div class="text-info">
                                        <h4><?php echo htmlspecialchars($t['nome_empresa']); ?></h4>
                                        <p class="service-area"><i class="fa-solid fa-location-dot"></i> Região: <?php echo htmlspecialchars($t['regioes_atendidas']); ?></p>
                                        <p class="description-short"><?php echo substr(htmlspecialchars($t['descricao_servicos']), 0, 100); ?>...</p>
                                    </div>
                                </div>
                                <a href="solicitacao_orcamento.php?terceirizada_id=<?php echo $t['usuario_id']; ?>" class="btn-primary request-btn contratante-btn">
                                    <i class="fa-solid fa-comments-dollar"></i> Solicitar Orçamento
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="perfil" class="content-section" style="display: none;">
            <h2 class="section-title"><i class="fa-solid fa-building"></i> Dados da Sua Empresa</h2>
            
            <form action="backend/controllers/PerfilContratanteController.php" method="post" enctype="multipart/form-data" class="widget-card">
                <div style="background-color: rgba(255, 196, 0, 0.1); padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid var(--secondary-yellow);">
                    <p><strong>Empresa:</strong> <?php echo $dados_perfil['nome']; ?> | <strong>CNPJ:</strong> <?php echo $cnpj_logado; ?></p>
                    <p style="font-size: 0.9em; color: #555; margin-top: 5px;">Mantenha os seus dados atualizados para que as prestadoras de serviço saibam onde realizar o trabalho.</p>
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label>Logótipo ou Foto da Empresa</label>
                    <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                        <img src="<?php echo $dados_perfil['foto_path']; ?>" alt="Sua Foto" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 1px solid #ccc;">
                        <input type="file" name="foto_perfil" accept="image/*">
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label>Pessoa Responsável (Contato)</label>
                        <input type="text" name="responsavel" value="<?php echo $dados_perfil['responsavel']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                    <div style="flex: 1;">
                        <label>Telefone / WhatsApp</label>
                        <input type="text" name="telefone" value="<?php echo $dados_perfil['telefone']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                </div>
                
                <h3 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Endereço Principal</h3>
                
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;"><label>CEP</label><input type="text" name="cep" value="<?php echo $dados_perfil['cep']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 2;"><label>Rua/Avenida</label><input type="text" name="logradouro" value="<?php echo $dados_perfil['logradouro']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;"><label>Número</label><input type="text" name="numero" value="<?php echo $dados_perfil['numero']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 2;"><label>Bairro</label><input type="text" name="bairro" value="<?php echo $dados_perfil['bairro']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 3;"><label>Cidade</label><input type="text" name="cidade" value="<?php echo $dados_perfil['cidade']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 1;"><label>UF</label><input type="text" name="estado" value="<?php echo $dados_perfil['estado']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>

                <button type="submit" class="btn btn-header contratante-btn" style="width: 100%; margin-top: 20px; padding: 15px; font-size: 1.1em;">
                    <i class="fa-solid fa-save"></i> Guardar Dados da Empresa
                </button>
            </form>
        </section>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const buscaLink = document.getElementById('buscaLink');
            const perfilLink = document.getElementById('perfilLink');
            const buscaSection = document.getElementById('busca');
            const perfilSection = document.getElementById('perfil');

            function switchView(view) {
                if (view === 'busca') {
                    buscaSection.style.display = 'block';
                    perfilSection.style.display = 'none';
                    buscaLink.classList.add('nav-active');
                    perfilLink.classList.remove('nav-active');
                } else {
                    buscaSection.style.display = 'none';
                    perfilSection.style.display = 'block';
                    perfilLink.classList.add('nav-active');
                    buscaLink.classList.remove('nav-active');
                }
            }
            
            buscaLink.addEventListener('click', (e) => { e.preventDefault(); switchView('busca'); });
            perfilLink.addEventListener('click', (e) => { e.preventDefault(); switchView('perfil'); });

            // Se a URL tiver #perfil (ex: após salvar), abre a aba perfil direto
            if(window.location.hash === '#perfil') {
                switchView('perfil');
            }
        });
    </script>
</body>
</html>