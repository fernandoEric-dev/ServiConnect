<?php
// dashboard_terceirizada.php
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
    'nome' => htmlspecialchars($dados_bd['nome_empresa'] ?? 'Empresa'),
    'email' => htmlspecialchars($dados_bd['email']),
    'descricao' => htmlspecialchars($dados_bd['descricao_servicos'] ?? ''),
    'foto_path' => htmlspecialchars($dados_bd['foto_path'] ?? 'img/default_avatar.png'),
    'num_funcionarios' => htmlspecialchars($dados_bd['num_funcionarios'] ?? 0),
    'area_atuacao' => htmlspecialchars($dados_bd['area_atuacao'] ?? $dados_bd['tipo_empresa']),
    'regiao' => htmlspecialchars($dados_bd['regioes_atendidas'] ?? ''),
    'horario' => htmlspecialchars($dados_bd['horario_funcionamento'] ?? ''),
    'cep' => htmlspecialchars($dados_bd['cep'] ?? ''),
    'logradouro' => htmlspecialchars($dados_bd['logradouro'] ?? ''),
    'numero' => htmlspecialchars($dados_bd['numero'] ?? ''),
    'complemento' => htmlspecialchars($dados_bd['complemento'] ?? ''),
    'bairro' => htmlspecialchars($dados_bd['bairro'] ?? ''),
    'cidade' => htmlspecialchars($dados_bd['cidade'] ?? ''),
    'estado' => htmlspecialchars($dados_bd['estado'] ?? ''),
];

// Busca Solicitações Recebidas
$stmtSolicitacoes = $pdo->prepare("
    SELECT s.id AS solicitacao_id, s.data_solicitacao, s.descricao_servico, s.localizacao_servico, s.area_servico_solicitada, s.numero_funcionarios, e.nome AS nome_contratante
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
                            <h3>Pedido de <?php echo htmlspecialchars($s['nome_contratante']); ?></h3>
                            <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($s['data_solicitacao'])); ?></p>
                            <p><strong>Serviço:</strong> <?php echo htmlspecialchars($s['area_servico_solicitada']); ?> (<?php echo htmlspecialchars($s['numero_funcionarios']); ?> Funcionários)</p>
                            <p><strong>Local:</strong> <?php echo htmlspecialchars($s['localizacao_servico']); ?></p>
                            <p class="description-short"><?php echo substr(htmlspecialchars($s['descricao_servico']), 0, 150); ?>...</p>
                            <a href="responder_orcamento.php?id=<?php echo $s['solicitacao_id']; ?>" class="btn-primary request-btn action-respond terceirizada-btn">
                                <i class="fa-solid fa-reply"></i> Responder Orçamento
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="perfil" class="content-section" style="display: none;">
            <h2 class="section-title"><i class="fa-solid fa-id-card-clip"></i> Gerenciar Perfil Público</h2>
            <form action="backend/controllers/PerfilTerceirizadaController.php" method="post" id="formEdicaoPerfil" enctype="multipart/form-data" class="widget-card">
                <input type="hidden" name="acao" value="atualizar_perfil">
                <input type="hidden" name="foto_atual" value="<?php echo $dados_perfil['foto_path']; ?>">

                <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <p><strong>Empresa:</strong> <?php echo $dados_perfil['nome']; ?> | <strong>CNPJ:</strong> <?php echo $cnpj_logado; ?></p>
                </div>

                <div class="input-group">
                    <label>Foto / Logotipo da Empresa</label>
                    <input type="file" name="foto_perfil" accept="image/*">
                </div>
                <div>
                    <label>Texto de Perfil</label>
                    <textarea name="texto_perfil" rows="4"><?php echo $dados_perfil['descricao']; ?></textarea>
                </div>
                <div>
                    <label>Área de Atuação</label>
                    <input type="text" name="area_atuacao" value="<?php echo $dados_perfil['area_atuacao']; ?>">
                </div>
                <div>
                    <label>Regiões Atendidas</label>
                    <input type="text" name="regiao" value="<?php echo $dados_perfil['regiao']; ?>">
                </div>
                
                <h3 style="margin-top: 30px;">Endereço Base</h3>
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;"><label>CEP</label><input type="text" name="cep_empresa" value="<?php echo $dados_perfil['cep']; ?>"></div>
                    <div style="flex: 2;"><label>Rua/Avenida</label><input type="text" name="logradouro_empresa" value="<?php echo $dados_perfil['logradouro']; ?>"></div>
                </div>
                <div style="display: flex; gap: 10px; margin-top:10px;">
                    <div style="flex: 1;"><label>Número</label><input type="text" name="numero_empresa" value="<?php echo $dados_perfil['numero']; ?>"></div>
                    <div style="flex: 2;"><label>Bairro</label><input type="text" name="bairro_empresa" value="<?php echo $dados_perfil['bairro']; ?>"></div>
                </div>
                <div style="display: flex; gap: 10px; margin-top:10px;">
                    <div style="flex: 3;"><label>Cidade</label><input type="text" name="cidade_empresa" value="<?php echo $dados_perfil['cidade']; ?>"></div>
                    <div style="flex: 1;"><label>UF</label><input type="text" name="estado_empresa" value="<?php echo $dados_perfil['estado']; ?>"></div>
                </div>

                <button type="submit" class="btn btn-header terceirizada-btn" style="width: 100%; margin-top: 30px;">
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
        });
    </script>
</body>
</html>