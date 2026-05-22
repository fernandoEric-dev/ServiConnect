<?php
// dashboard_tercerizada.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'terceirizada') {
    header('Location: login.php');
    exit;
}

require_once 'backend/conexao.php';
$user_id = $_SESSION['user_id'];
$cnpj_logado = $_SESSION['user_cnpj'];

// Busca dados do perfil para o formulário
$stmt = $pdo->prepare("SELECT u.email, e.* FROM usuarios u JOIN empresas e ON u.id = e.usuario_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$dados_bd = $stmt->fetch(PDO::FETCH_ASSOC);

$dados_perfil = [
    'nome' => htmlspecialchars($dados_bd['nome'] ?? 'Empresa'),
    'email' => htmlspecialchars($dados_bd['email']),
    'descricao' => htmlspecialchars($dados_bd['descricao'] ?? ''),
    'foto_path' => htmlspecialchars($dados_bd['foto_path'] ?? 'img/default_avatar.png'),
    'num_funcionarios' => htmlspecialchars($dados_bd['num_funcionarios'] ?? 0),
    'area_atuacao' => htmlspecialchars($dados_bd['area_atuacao'] ?? $dados_bd['tipo_empresa']),
    'regiao' => htmlspecialchars($dados_bd['regiao'] ?? ''),
    'horario' => htmlspecialchars($dados_bd['horario'] ?? ''),
    'cep' => htmlspecialchars($dados_bd['cep'] ?? ''),
    'logradouro' => htmlspecialchars($dados_bd['logradouro'] ?? ''),
    'numero' => htmlspecialchars($dados_bd['numero'] ?? ''),
    'complemento' => htmlspecialchars($dados_bd['complemento'] ?? ''),
    'bairro' => htmlspecialchars($dados_bd['bairro'] ?? ''),
    'cidade' => htmlspecialchars($dados_bd['cidade'] ?? ''),
    'estado' => htmlspecialchars($dados_bd['estado'] ?? ''),
];

// Busca Solicitações Recebidas trazendo também o ID do contratante para ver o perfil
$stmtSolicitacoes = $pdo->prepare("
    SELECT s.id AS solicitacao_id, s.contratante_id, s.data_solicitacao, s.descricao_servico, s.localizacao_servico, s.area_servico_solicitada, s.numero_funcionarios, e.nome AS nome_contratante
    FROM solicitacoes s
    JOIN empresas e ON s.contratante_id = e.usuario_id
    WHERE s.terceirizada_id = :terceirizada_id AND s.status = 'aberta'
    ORDER BY s.data_solicitacao DESC
");
$stmtSolicitacoes->execute([':terceirizada_id' => $user_id]);
$solicitacoes_recebidas = $stmtSolicitacoes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiConnect | Área da Terceirizada</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/terceirizada.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-header terceirizada-header">
        <div class="header-container">
            <h1 class="logo-title">ServiConnect <span class="badge-role">Terceirizada</span></h1>
            <nav class="main-nav">
                <a href="#pedidos" id="feedLink" class="nav-item nav-active"><i class="fa-solid fa-envelope"></i> Pedidos</a>
                <a href="#perfil" id="perfilLink" class="nav-item"><i class="fa-solid fa-user-circle"></i> Meu Perfil</a>
            </nav>
            <div class="user-control">
                <img src="<?php echo $dados_perfil['foto_path']; ?>" alt="Perfil" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 2px solid #28a745;">
                <span class="user-display">Olá, <?php echo $dados_perfil['nome']; ?></span>
                <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
            </div>
        </div>
    </div>

    <main class="main-content-dashboard">
        <section id="feed" class="content-section active-section">
            <h2 class="section-title"><i class="fa-solid fa-envelope-open-text"></i> Solicitações de Orçamento</h2>
            <?php if (empty($solicitacoes_recebidas)): ?>
                <div class="widget-card requests-list">
                    <p>🎉 Nenhuma nova solicitação de orçamento pendente no momento.</p>
                </div>
            <?php else: ?>
                <div class="requests-list">
                    <?php foreach ($solicitacoes_recebidas as $s): ?>
                        <div class="solicitacao-card widget-card">
                            <h3>Pedido de <a href="perfil_empresa.php?id=<?php echo $s['contratante_id']; ?>" style="color: #0056b3; text-decoration: none; font-weight: bold;"><?php echo htmlspecialchars($s['nome_contratante']); ?></a></h3>
                            <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($s['data_solicitacao'])); ?></p>
                            <p><strong>Serviço:</strong> <?php echo htmlspecialchars($s['area_servico_solicitada']); ?> (<?php echo htmlspecialchars($s['numero_funcionarios']); ?> Funcionários)</p>
                            <p><strong>Local:</strong> <?php echo htmlspecialchars($s['localizacao_servico']); ?></p>
                            <p class="description-short"><?php echo substr(htmlspecialchars($s['descricao_servico']), 0, 150); ?>...</p>
                            
                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <a href="perfil_empresa.php?id=<?php echo $s['contratante_id']; ?>" class="btn-primary" style="background: #333; text-decoration: none; padding: 10px 15px; border-radius: 4px; color: #fff; font-size: 0.9em;">
                                    <i class="fa-solid fa-building"></i> Ver Perfil do Contratante
                                </a>
                                <a href="responder_orcamento.php?id=<?php echo $s['solicitacao_id']; ?>" class="btn-primary request-btn action-respond terceirizada-btn" style="margin: 0; padding: 10px 15px;">
                                    <i class="fa-solid fa-reply"></i> Responder Orçamento
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="perfil" class="content-section" style="display: none;">
            <h2 class="section-title"><i class="fa-solid fa-id-card-clip"></i> Gerenciar Perfil Público</h2>
            <form action="backend/controllers/PerfilTercerizadaController.php" method="post" id="formEdicaoPerfil" enctype="multipart/form-data" class="widget-card">
                <input type="hidden" name="acao" value="atualizar_perfil">
                <input type="hidden" name="foto_atual" value="<?php echo $dados_perfil['foto_path']; ?>">

                <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <p><strong>Empresa:</strong> <?php echo $dados_perfil['nome']; ?> | <strong>CNPJ:</strong> <?php echo $cnpj_logado; ?></p>
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label>Foto / Logotipo da Empresa</label>
                    <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                        <img src="<?php echo $dados_perfil['foto_path']; ?>" alt="Sua Foto" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 1px solid #ccc;">
                        <input type="file" name="foto_perfil" accept="image/*">
                    </div>
                </div>
                <div>
                    <label>Texto de Perfil</label>
                    <textarea name="texto_perfil" rows="4" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"><?php echo $dados_perfil['descricao']; ?></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top:10px;">
                    <div style="flex: 1;">
                        <label>Área de Atuação</label>
                        <input type="text" name="area_atuacao" value="<?php echo $dados_perfil['area_atuacao']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                    <div style="flex: 1;">
                        <label>Regiões Atendidas</label>
                        <input type="text" name="regiao" value="<?php echo $dados_perfil['regiao']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                </div>
                
                <h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Endereço Base</h3>
                <div style="display: flex; gap: 10px; margin-top:10px;">
                    <div style="flex: 1;"><label>CEP</label><input type="text" name="cep_empresa" value="<?php echo $dados_perfil['cep']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 2;"><label>Rua/Avenida</label><input type="text" name="logradouro_empresa" value="<?php echo $dados_perfil['logradouro']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>
                <div style="display: flex; gap: 10px; margin-top:10px;">
                    <div style="flex: 1;"><label>Número</label><input type="text" name="numero_empresa" value="<?php echo $dados_perfil['numero']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 2;"><label>Bairro</label><input type="text" name="bairro_empresa" value="<?php echo $dados_perfil['bairro']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>
                <div style="display: flex; gap: 10px; margin-top:10px;">
                    <div style="flex: 3;"><label>Cidade</label><input type="text" name="cidade_empresa" value="<?php echo $dados_perfil['cidade']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                    <div style="flex: 1;"><label>UF</label><input type="text" name="estado_empresa" value="<?php echo $dados_perfil['estado']; ?>" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></div>
                </div>

                <button type="submit" class="btn btn-header terceirizada-btn" style="width: 100%; margin-top: 30px; padding: 15px; font-size: 1.1em;">
                    <i class="fa-solid fa-save"></i> Salvar Perfil
                </button>
            </form>
        </section>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const feedLink = document.getElementById('feedLink');
            const perfilLink = document.getElementById('perfilLink');
            const feedSection = document.getElementById('feed');
            const perfilSection = document.getElementById('perfil');

            function switchView(view) {
                if (view === 'feed') {
                    feedSection.style.display = 'block';
                    perfilSection.style.display = 'none';
                    feedLink.classList.add('nav-active');
                    perfilLink.classList.remove('nav-active');
                } else {
                    feedSection.style.display = 'none';
                    perfilSection.style.display = 'block';
                    perfilLink.classList.add('nav-active');
                    feedLink.classList.remove('nav-active');
                }
            }
            
            feedLink.addEventListener('click', (e) => { e.preventDefault(); switchView('feed'); });
            perfilLink.addEventListener('click', (e) => { e.preventDefault(); switchView('perfil'); });

            if(window.location.hash === '#perfil') {
                switchView('perfil');
            }
        });
    </script>
</body>
</html>